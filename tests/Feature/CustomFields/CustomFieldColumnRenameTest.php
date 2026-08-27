<?php

namespace Tests\Feature\CustomFields;

use App\Models\CustomField;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomFieldColumnRenameTest extends TestCase
{
    public function testRenameUsesPersistedColumnInsteadOfRecomputingItFromTheOldName(): void
    {
        $field = CustomField::factory()->create(['name' => 'Original display name']);
        $generatedColumn = $field->db_column;
        $persistedColumn = '_snipeit_previously_transliterated_'.$field->id;

        Schema::table(CustomField::$table_name, function (Blueprint $table) use ($generatedColumn, $persistedColumn) {
            $table->renameColumn($generatedColumn, $persistedColumn);
        });
        DB::table('custom_fields')
            ->where('id', $field->id)
            ->update(['db_column' => $persistedColumn]);

        $field->refresh();
        $this->assertNotSame($persistedColumn, $field->convertUnicodeDbSlug($field->name));

        $field->name = 'Renamed display name';
        $this->assertTrue($field->save());

        $targetColumn = $field->convertUnicodeDbSlug();

        $this->assertFalse(Schema::hasColumn(CustomField::$table_name, $persistedColumn));
        $this->assertTrue(Schema::hasColumn(CustomField::$table_name, $targetColumn));
        $this->assertSame($targetColumn, $field->fresh()->db_column);
    }

    public function testRenameFallsBackToTheLegacySlugWhenStoredColumnMetadataIsEmpty(): void
    {
        $field = CustomField::factory()->create(['name' => 'Legacy field name']);
        $sourceColumn = $field->db_column;

        DB::table('custom_fields')
            ->where('id', $field->id)
            ->update(['db_column' => null]);

        $field->refresh();
        $field->name = 'Recovered field name';
        $this->assertTrue($field->save());

        $targetColumn = $field->convertUnicodeDbSlug();

        $this->assertFalse(Schema::hasColumn(CustomField::$table_name, $sourceColumn));
        $this->assertTrue(Schema::hasColumn(CustomField::$table_name, $targetColumn));
        $this->assertSame($targetColumn, $field->fresh()->db_column);
    }

    public function testRenameFailsClosedWhenTheStoredSourceColumnIsMissing(): void
    {
        $field = CustomField::factory()->create(['name' => 'Inconsistent field']);
        $sourceColumn = $field->db_column;

        Schema::table(CustomField::$table_name, function (Blueprint $table) use ($sourceColumn) {
            $table->dropColumn($sourceColumn);
        });

        $field->name = 'Rename must fail';

        try {
            $field->save();
            $this->fail('The rename should fail when the persisted source column is missing.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('stored database column', $exception->getMessage());
            $this->assertStringContainsString($sourceColumn, $exception->getMessage());
        }

        $this->assertDatabaseHas('custom_fields', [
            'id' => $field->id,
            'name' => 'Inconsistent field',
            'db_column' => $sourceColumn,
        ]);
    }
}
