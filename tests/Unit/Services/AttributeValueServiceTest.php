<?php

namespace Tests\Unit\Services;

use App\Models\AttributeDefinition;
use App\Services\ModelAttributes\AttributeValueService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttributeValueServiceTest extends TestCase
{
    public function test_invalid_boolean_value_throws_a_field_scoped_validation_exception(): void
    {
        $definition = $this->definition(AttributeDefinition::DATATYPE_BOOL);

        try {
            app(AttributeValueService::class)->validateAndNormalize(
                $definition,
                'sometimes',
                'component_attributes'
            );
            $this->fail('Expected invalid boolean input to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('component_attributes.42', $exception->errors());
        }
    }

    public function test_unknown_datatype_throws_instead_of_returning_without_a_tuple(): void
    {
        $definition = $this->definition('unsupported');

        $this->expectException(ValidationException::class);

        app(AttributeValueService::class)->validateAndNormalize($definition, 'value');
    }

    private function definition(string $datatype): AttributeDefinition
    {
        $definition = new AttributeDefinition([
            'key' => 'test_attribute',
            'label' => 'Test Attribute',
            'datatype' => $datatype,
        ]);
        $definition->id = 42;
        $definition->setRelation('options', new Collection());

        return $definition;
    }
}
