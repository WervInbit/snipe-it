<?php

namespace App\Models\Traits;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Services\ComponentTagGenerator;
use App\Services\IdentifierDuplicateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

trait ChecksIdentifierDuplicates
{
    protected bool $allowDuplicateTag = false;

    protected bool $confirmedDuplicateSerial = false;

    public function allowDuplicateTag(bool $allow = true): self
    {
        $this->allowDuplicateTag = $allow;
        return $this;
    }

    public function confirmDuplicateSerial(bool $allow = true): self
    {
        $this->confirmedDuplicateSerial = $allow;
        return $this;
    }

    protected function saveWithIdentifierValidation(array $options = [])
    {
        $tagColumn = $this instanceof Asset ? 'asset_tag' : 'component_tag';
        if ($this->exists && !$this->isDirty([$tagColumn, 'serial'])) {
            return parent::save($options);
        }

        return DB::transaction(function () use ($options, $tagColumn) {
            // Serialize identifier validation and persistence, including manual identifiers.
            if (DB::table('identifier_write_locks')->where('id', 1)->update(['state' => DB::raw('1 - state')]) !== 1) {
                throw new \RuntimeException('Identifier write guard is missing. Apply the identifier migrations.');
            }
            if (!$this->exists && blank($this->{$tagColumn})) {
                $this->{$tagColumn} = $this instanceof Asset
                    ? Asset::generateTag() : app(ComponentTagGenerator::class)->generate();
            }
            $errors = [];
            $accepted = [];
            foreach (['tag' => $tagColumn, 'serial' => 'serial'] as $field => $column) {
                $value = (string) $this->{$column};
                if (
                    $this->exists && IdentifierDuplicateService::normalize($this->getRawOriginal($column))
                        === IdentifierDuplicateService::normalize($value)
                ) {
                    continue;
                }
                if (app(IdentifierDuplicateService::class)->matches($field, $value, $this, true)->isEmpty()) {
                    continue;
                }
                $allowed = $field === 'tag' ? $this->allowDuplicateTag
                    : ($this->confirmedDuplicateSerial || ($this->allowDuplicateSerial ?? false));
                // Request confirmation applies only to the exact submitted identifier.
                $submitted = request()->input($column);
                $allowed = $allowed || (is_string($submitted)
                    && IdentifierDuplicateService::normalize($submitted) === IdentifierDuplicateService::normalize($value)
                    && request()->boolean('allow_duplicate_' . $field));
                if (!$allowed) {
                    $errors[$column] = trans('identifiers.confirm_required', ['field' => trans('identifiers.' . $field)]);
                } else {
                    $accepted[] = $field;
                }
            }
            if ($errors !== []) {
                if ($this instanceof Asset) {
                    $this->setErrors(new MessageBag($errors));
                    return false;
                }
                throw ValidationException::withMessages($errors);
            }
            $saved = parent::save($options);
            if ($saved && $accepted !== []) {
                $log = new Actionlog();
                $log->item_type = static::class;
                $log->item_id = $this->getKey();
                $log->created_by = auth()->id();
                $log->action_type = 'update';
                $log->note = 'Duplicate identifier explicitly accepted: ' . implode(', ', $accepted);
                $log->save();
            }
            if ($saved) {
                $this->allowDuplicateTag = false;
                $this->confirmedDuplicateSerial = false;
                if ($this instanceof Asset) {
                    $this->allowDuplicateSerial = false;
                }
            }
            return $saved;
        });
    }
}
