<?php

namespace Tests\Feature\Console;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{
    private const VALID_PASSWORD = 'A-strong-test-password-42';

    public function test_non_interactive_creation_requires_all_options(): void
    {
        $this->artisan('snipeit:create-admin', ['--no-interaction' => true])
            ->expectsOutput('The --first_name option is required in non-interactive mode.')
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_bootstrap_refuses_when_a_deleted_user_exists(): void
    {
        User::factory()->create()->delete();

        $this->artisan('snipeit:create-admin', $this->validOptions(['--bootstrap' => true]))
            ->expectsOutput('Bootstrap refused: at least one active or deleted user already exists.')
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_creation_refuses_when_foundation_settings_do_not_exist(): void
    {
        Setting::query()->delete();
        Setting::$_cache = null;

        $this->artisan('snipeit:create-admin', $this->validOptions())
            ->expectsOutput(
                'Administrator creation refused: run migrations and the reviewed foundation seeder first.'
            )
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_email_does_not_create_an_administrator(): void
    {
        $this->artisan('snipeit:create-admin', $this->validOptions([
            '--email' => 'not-an-email',
        ]))->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_bootstrap_creates_a_standalone_superuser_without_assuming_a_group(): void
    {
        $this->assertDatabaseCount('permission_groups', 0);

        $this->artisan('snipeit:create-admin', $this->validOptions([
            '--bootstrap' => true,
            'show_in_list' => 'false',
            'autoassign_licenses' => 'false',
        ]))
            ->expectsOutput('Administrator created successfully.')
            ->assertExitCode(Command::SUCCESS);

        $user = User::query()->sole();

        $this->assertSame('Release', $user->first_name);
        $this->assertSame('Administrator', $user->last_name);
        $this->assertSame('release-admin', $user->username);
        $this->assertSame('release-admin@example.org', $user->email);
        $this->assertSame(['superuser' => 1], json_decode($user->getRawOriginal('permissions'), true));
        $this->assertTrue((bool) $user->activated);
        $this->assertFalse((bool) $user->show_in_list);
        $this->assertFalse((bool) $user->autoassign_licenses);
        $this->assertSame(0, $user->groups()->count());
    }

    public function test_invalid_boolean_argument_does_not_create_an_administrator(): void
    {
        $this->artisan('snipeit:create-admin', $this->validOptions([
            'show_in_list' => 'sometimes',
        ]))
            ->expectsOutput('The show_in_list argument must be true or false.')
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('users', 0);
    }

    private function validOptions(array $overrides = []): array
    {
        return array_merge([
            '--first_name' => 'Release',
            '--last_name' => 'Administrator',
            '--email' => 'release-admin@example.org',
            '--username' => 'release-admin',
            '--password' => self::VALID_PASSWORD,
            '--no-interaction' => true,
        ], $overrides);
    }
}
