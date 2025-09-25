<?php

namespace App\Services\ModelAttributes;

use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttributeValueService
{
    public function validateAndNormalize(AttributeDefinition $definition, $input): AttributeValueTuple
    {
        $definition->loadMissing('options');
        $raw = $input;

        switch ($definition->datatype) {
            case AttributeDefinition::DATATYPE_ENUM:
                return $this->handleEnum($definition, $input, $raw);
            case AttributeDefinition::DATATYPE_INT:
                return $this->handleInteger($definition, $input, $raw);
            case AttributeDefinition::DATATYPE_DECIMAL:
                return $this->handleDecimal($definition, $input, $raw);
            case AttributeDefinition::DATATYPE_BOOL:
                return $this->handleBoolean($definition, $input, $raw);
            case AttributeDefinition::DATATYPE_TEXT:
                return $this->handleText($definition, $input, $raw);
            default:
                throw ValidationException::withMessages([
                    $definition->key => __('Unknown attribute datatype :type', ['type' => $definition->datatype]),
                ]);
        }
    }

    private function handleEnum(AttributeDefinition $definition, $input, $raw): AttributeValueTuple
    {
        $options = $definition->options;

        if ($input instanceof AttributeOption) {
            $option = $options->firstWhere('id', $input->id);
            if (!$option) {
                $this->fail($definition, __('Selected option is not valid for this attribute.'));
            }
            return new AttributeValueTuple($option->value, $this->normalizeRaw($raw), $option->id);
        }

        if (is_numeric($input)) {
            $option = $options->firstWhere('id', (int) $input);
            if ($option) {
                return new AttributeValueTuple($option->value, $this->normalizeRaw($raw), $option->id);
            }
        }

        $value = is_string($input) ? trim($input) : $input;

        if ($value === '' || $value === null) {
            $this->fail($definition, __('Select a value for :label', ['label' => $definition->label]));
        }

        $option = $options->first(function (AttributeOption $option) use ($value) {
            return Str::lower($option->value) === Str::lower((string) $value);
        });

        if ($option) {
            return new AttributeValueTuple($option->value, $this->normalizeRaw($raw), $option->id);
        }

        if (!$definition->allow_custom_values) {
            $this->fail($definition, __('The value :value is not in the allowed options.', ['value' => $value]));
        }

        return new AttributeValueTuple((string) $value, $this->normalizeRaw($raw), null);
    }

    private function handleInteger(AttributeDefinition $definition, $input, $raw): AttributeValueTuple
    {
        if ($input === '' || $input === null) {
            $this->fail($definition, __('A value is required.'));
        }

        if (!$this->isWholeNumber($input)) {
            $this->fail($definition, __('Enter a whole number.'));
        }

        $value = (int) $input;
        $constraints = $definition->constraints;
        $this->enforceNumericConstraints($definition, $value, $constraints);

        return new AttributeValueTuple((string) $value, $this->normalizeRaw($raw), null);
    }

    private function handleDecimal(AttributeDefinition $definition, $input, $raw): AttributeValueTuple
    {
        if ($input === '' || $input === null) {
            $this->fail($definition, __('A value is required.'));
        }

        if (!is_numeric($input)) {
            $this->fail($definition, __('Enter a numeric value.'));
        }

        $value = (float) $input;
        $constraints = $definition->constraints;
        $this->enforceNumericConstraints($definition, $value, $constraints);

        $normalized = $this->trimTrailingZeros($value);

        return new AttributeValueTuple($normalized, $this->normalizeRaw($raw), null);
    }

    private function handleBoolean(AttributeDefinition $definition, $input, $raw): AttributeValueTuple
    {
        if (is_string($input)) {
            $input = strtolower($input);
            $truthy = ['1', 'true', 'yes', 'on'];
            $falsy = ['0', 'false', 'no', 'off'];
            if (in_array($input, $truthy, true)) {
                return new AttributeValueTuple('1', $this->normalizeRaw($raw), null);
            }
            if (in_array($input, $falsy, true)) {
                return new AttributeValueTuple('0', $this->normalizeRaw($raw), null);
            }
        }

        if (is_bool($input)) {
            return new AttributeValueTuple($input ? '1' : '0', $this->normalizeRaw($raw), null);
        }

        if (is_numeric($input)) {
            return new AttributeValueTuple(((int) $input) === 1 ? '1' : '0', $this->normalizeRaw($raw), null);
        }

        $this->fail($definition, __('Enter yes/no or true/false.'));
    }

    private function handleText(AttributeDefinition $definition, $input, $raw): AttributeValueTuple
    {
        $value = is_scalar($input) ? trim((string) $input) : '';
        if ($value === '') {
            $this->fail($definition, __('A value is required.'));
        }

        $constraints = $definition->constraints;
        if (!empty($constraints['regex'])) {
            $pattern = $this->compileRegex($constraints['regex']);

            if (@preg_match($pattern, $value) !== 1) {
                $this->fail($definition, __('Value does not match the expected format.'));
            }
        }

        return new AttributeValueTuple($value, $this->normalizeRaw($raw), null);
    }

    private function isWholeNumber($value): bool
    {
        if (is_int($value)) {
            return true;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', trim($value))) {
            return true;
        }

        return false;
    }

    private function compileRegex(string $pattern): string
    {
        $pattern = trim($pattern);

        if ($pattern === '') {
            return '/.*/';
        }

        $delimiter = substr($pattern, 0, 1);
        $end = strrpos($pattern, $delimiter);
        $hasDelimiters = $delimiter && !ctype_alnum($delimiter) && $end !== 0 && $end !== false;

        if ($hasDelimiters) {
            return $pattern;
        }

        return '/' . str_replace('/', '\/', $pattern) . '/';
    }

    private function enforceNumericConstraints(AttributeDefinition $definition, $value, array $constraints): void
    {
        if (array_key_exists('min', $constraints) && $constraints['min'] !== null && $value < $constraints['min']) {
            $this->fail($definition, __('Value must be at least :min.', ['min' => $constraints['min']]));
        }

        if (array_key_exists('max', $constraints) && $constraints['max'] !== null && $value > $constraints['max']) {
            $this->fail($definition, __('Value must be at most :max.', ['max' => $constraints['max']]));
        }

        if (array_key_exists('step', $constraints) && $constraints['step']) {
            $step = (float) $constraints['step'];
            if ($step > 0) {
                $mod = fmod((float) $value - (float) ($constraints['min'] ?? 0), $step);
                if ($mod > 1e-8 && ($step - $mod) > 1e-8) {
                    $this->fail($definition, __('Value must align to a step of :step.', ['step' => $step]));
                }
            }
        }
    }

    private function trimTrailingZeros(float $value): string
    {
        $normalized = rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');

        return $normalized === '' ? '0' : $normalized;
    }
    private function normalizeRaw($raw): ?string
    {
        if (is_scalar($raw) || $raw === null) {
            return $raw === null ? null : (string) $raw;
        }

        return json_encode($raw);
    }


    private function fail(AttributeDefinition $definition, string $message)
    {
        throw ValidationException::withMessages([
            $definition->key => [$message],
        ]);
    }
}
