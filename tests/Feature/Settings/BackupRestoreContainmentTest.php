<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use ZipArchive;

class BackupRestoreContainmentTest extends TestCase
{
    public function test_uploaded_backup_restore_is_disabled_before_any_artisan_mutation(): void
    {
        Storage::fake('local');
        Storage::put('app/backups/disabled.zip', 'not a real archive');
        config()->set('app.allow_backup_restore', false);
        config()->set('app.lock_passwords', false);
        Artisan::shouldReceive('call')->never();

        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->post(route('settings.backups.restore', ['filename' => 'disabled.zip']))
            ->assertRedirect(route('settings.backups.index'))
            ->assertSessionHas(
                'error',
                trans('admin/settings/message.backup.restore_disabled'),
            );

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_invalid_backup_archive_is_rejected_before_any_artisan_mutation(): void
    {
        Storage::fake('local');
        Storage::put('app/backups/corrupt.zip', 'not a zip archive');
        config()->set('app.allow_backup_restore', true);
        config()->set('app.lock_passwords', false);
        Artisan::shouldReceive('call')->never();

        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->post(route('settings.backups.restore', ['filename' => 'corrupt.zip']))
            ->assertRedirect(route('settings.backups.index'))
            ->assertSessionHas(
                'error',
                trans('admin/settings/message.backup.invalid_archive'),
            );

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_missing_backup_path_is_rejected_before_any_artisan_mutation(): void
    {
        Storage::fake('local');
        config()->set('app.allow_backup_restore', true);
        config()->set('app.lock_passwords', false);
        Artisan::shouldReceive('call')->never();

        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->post(route('settings.backups.restore', ['filename' => 'missing.zip']))
            ->assertRedirect(route('settings.backups.index'))
            ->assertSessionHas(
                'error',
                trans('admin/settings/message.backup.file_not_found'),
            );

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_failed_safety_backup_aborts_before_database_wipe(): void
    {
        Storage::fake('local');
        $this->storeValidBackupArchive('app/backups/valid.zip');
        config()->set('app.allow_backup_restore', true);
        config()->set('app.lock_passwords', false);

        Artisan::shouldReceive('call')
            ->once()
            ->with('snipeit:backup', Mockery::on(
                fn (array $arguments): bool => preg_match(
                    '/^pre-restore-backup-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}$/',
                    (string) ($arguments['--filename'] ?? '')
                ) === 1
            ))
            ->andReturn(1);
        Artisan::shouldReceive('call')
            ->with('db:wipe', Mockery::any())
            ->never();

        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->post(route('settings.backups.restore', ['filename' => 'valid.zip']))
            ->assertRedirect(route('settings.backups.index'))
            ->assertSessionHas(
                'error',
                trans('admin/settings/message.backup.restore_backup_failed'),
            );

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_disabled_restore_hides_restore_and_upload_controls(): void
    {
        Storage::fake('local');
        Storage::put('app/backups/listed.zip', 'listed backup');
        config()->set('app.allow_backup_restore', false);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.backups.index'))
            ->assertOk()
            ->assertSee(trans('admin/settings/message.backup.restore_disabled'))
            ->assertDontSee(
                route('settings.backups.restore', ['filename' => 'listed.zip']),
                false,
            )
            ->assertDontSee(route('settings.backups.upload'), false)
            ->assertDontSee('backupRestoreModal', false);
    }

    public function test_disabled_restore_refuses_backup_uploads(): void
    {
        Storage::fake('local');
        config()->set('app.allow_backup_restore', false);
        config()->set('app.lock_passwords', false);

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.backups.upload'), [
                'file' => UploadedFile::fake()->create(
                    'disabled.zip',
                    1,
                    'application/zip',
                ),
            ])
            ->assertRedirect(route('settings.backups.index'))
            ->assertSessionHas(
                'error',
                trans('admin/settings/message.backup.restore_disabled'),
            );

        Storage::disk('local')->assertMissing('app/backups/disabled.zip');
    }

    private function storeValidBackupArchive(string $path): void
    {
        Storage::makeDirectory(dirname($path));

        $archive = new ZipArchive();
        $this->assertTrue(
            $archive->open(Storage::path($path), ZipArchive::CREATE | ZipArchive::OVERWRITE)
        );
        $this->assertTrue($archive->addFromString('db-dumps/database.sql', 'SELECT 1;'));
        $this->assertTrue($archive->close());
    }
}
