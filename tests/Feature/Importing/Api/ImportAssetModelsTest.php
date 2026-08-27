<?php

namespace Tests\Feature\Importing\Api;

use App\Models\Category;
use App\Models\AssetModel;
use App\Models\User;
use App\Models\Import;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\TestsPermissionsRequirement;
use Tests\Support\Importing\CleansUpImportFiles;
use Tests\Support\Importing\AssetModelsImportFileBuilder as ImportFileBuilder;

class ImportAssetModelsTest extends ImportDataTestCase implements TestsPermissionsRequirement
{
    use CleansUpImportFiles;
    use WithFaker;

    protected function importFileResponse(array $parameters = []): TestResponse
    {
        if (!array_key_exists('import-type', $parameters)) {
            $parameters['import-type'] = 'assetModel';
        }

        return parent::importFileResponse($parameters);
    }

    #[Test]
    public function testRequiresPermission()
    {
        $this->actingAsForApi(User::factory()->create());

        $this->importFileResponse(['import' => 44])->assertForbidden();
    }
    
    #[Test]
    public function importAssetModels(): void
    {
        $importFileBuilder = ImportFileBuilder::new();
        $row = $importFileBuilder->firstRow();
        $import = Import::factory()->assetmodel()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id, 'send-welcome' => 0])
            ->assertOk()
            ->assertExactJson([
                'payload'  => null,
                'status'   => 'success',
                'messages' => ['redirect_url' => route('models.index')]
            ]);

        $newAssetModel = AssetModel::query()
            ->with(['category', 'primaryModelNumber', 'modelNumbers'])
            ->where('name', $row['name'])
            ->sole();

        $this->assertEquals($row['name'], $newAssetModel->name);
        $this->assertEquals($row['model_number'], $newAssetModel->model_number);
        $this->assertEquals($row['model_number'], $newAssetModel->primaryModelNumber->code);
        $this->assertCount(1, $newAssetModel->modelNumbers);

    }

    #[Test]
    public function willIgnoreUnknownColumnsWhenFileContainsUnknownColumns(): void
    {
        $row = ImportFileBuilder::new()->definition();
        $row['unknownColumnInCsvFile'] = 'foo';

        $importFileBuilder = new ImportFileBuilder([$row]);

        $this->actingAsForApi(User::factory()->superuser()->create());

        $import = Import::factory()->assetmodel()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->importFileResponse(['import' => $import->id])->assertOk();
    }

    #[Test]
    public function rowsWithTheSameModelNameCreateMultipleModelNumberPresets(): void
    {
        $modelName = 'Shared Model '.Str::random();
        $rows = ImportFileBuilder::times(2)
            ->replace(['name' => $modelName])
            ->all();
        $rows[0]['model_number'] = 'VARIANT-A';
        $rows[1]['model_number'] = 'VARIANT-B';
        $importFileBuilder = new ImportFileBuilder($rows);
        $import = Import::factory()->assetmodel()->create([
            'file_path' => $importFileBuilder->saveToImportsDirectory(),
        ]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id])->assertOk();

        $model = AssetModel::query()
            ->with(['modelNumbers', 'primaryModelNumber'])
            ->where('name', $modelName)
            ->sole();

        $this->assertSame(
            ['VARIANT-A', 'VARIANT-B'],
            $model->modelNumbers->pluck('code')->sort()->values()->all(),
        );
        $this->assertSame('VARIANT-A', $model->primaryModelNumber->code);
        $this->assertSame('VARIANT-A', $model->model_number);
    }


    #[Test]
    public function whenRequiredColumnsAreMissingInImportFile(): void
    {
        $importFileBuilder = ImportFileBuilder::new(['name' => '']);
        $import = Import::factory()->assetmodel()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());

        $this->importFileResponse(['import' => $import->id])
            ->assertInternalServerError()
            ->assertExactJson([
                'status'   => 'import-errors',
                'payload'  => null,
                'messages' => [
                    '' => [
                        'name' => [
                            'name' =>
                                [trans('validation.required', ['attribute' => 'name'])],
                        ],
                    ]
                ]
            ]);

        $newAssetModels = AssetModel::query()
            ->where('name', $importFileBuilder->firstRow()['name'])
            ->get();

        $this->assertCount(0, $newAssetModels);
    }


    #[Test]
    public function updateAssetModelFromImport(): void
    {
        $assetmodel = AssetModel::factory()->create()->refresh();
        $category = Category::find($assetmodel->category->name);
        $importFileBuilder = ImportFileBuilder::new(['name' => $assetmodel->name, 'model_number' => Str::random(), 'category' => $category]);

        $row = $importFileBuilder->firstRow();
        $import = Import::factory()->assetmodel()->create(['file_path' => $importFileBuilder->saveToImportsDirectory()]);

        $this->actingAsForApi(User::factory()->superuser()->create());
        $this->importFileResponse(['import' => $import->id, 'import-update' => true])->assertOk();

        $updatedAssetmodel = AssetModel::query()
            ->with(['primaryModelNumber', 'modelNumbers'])
            ->find($assetmodel->id);
        $updatedAttributes = [
            'name',
            'model_number',
            'primary_model_number_id',
        ];

        $this->assertEquals($row['model_number'], $updatedAssetmodel->model_number);
        $this->assertEquals($row['model_number'], $updatedAssetmodel->primaryModelNumber->code);
        $this->assertCount(1, $updatedAssetmodel->modelNumbers);

        $this->assertEquals(
            Arr::except($assetmodel->attributesToArray(), array_merge($updatedAttributes, $assetmodel->getDates())),
            Arr::except($updatedAssetmodel->attributesToArray(), array_merge($updatedAttributes, $assetmodel->getDates())),
        );
    }

}
