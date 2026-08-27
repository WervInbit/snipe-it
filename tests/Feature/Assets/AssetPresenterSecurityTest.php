<?php

namespace Tests\Feature\Assets;

use App\Models\AssetModel;
use App\Models\CustomField;
use App\Models\CustomFieldset;
use App\Presenters\AssetPresenter;
use Tests\TestCase;

class AssetPresenterSecurityTest extends TestCase
{
    public function testCustomFieldColumnTitlesAreHtmlEscaped(): void
    {
        $customField = CustomField::factory()->create([
            'name' => '<img src=x onerror=alert(1)>',
        ]);
        $fieldset = CustomFieldset::factory()
            ->hasMultipleCustomFields([$customField])
            ->create();

        AssetModel::factory()->create(['fieldset_id' => $fieldset->id]);

        $columns = collect(json_decode(AssetPresenter::dataTableLayout(), true, flags: JSON_THROW_ON_ERROR));
        $column = $columns->firstWhere('field', $customField->db_column);

        $this->assertNotNull($column);
        $this->assertSame(
            '&lt;img src=x onerror=alert(1)&gt;',
            $column['title'],
        );
    }
}
