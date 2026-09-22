<?php

namespace App\Rules;

use App\Models\Statuslabel;
use App\Services\Assets\StatusLabelAccessService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserCanSelectStatusLabel implements ValidationRule
{
    public function __construct(private readonly ?int $currentStatusId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value || (int) $value === $this->currentStatusId) {
            return;
        }

        $user = auth()->user();
        $status = Statuslabel::find($value);

        if (!$user || !$status || !app(StatusLabelAccessService::class)->canSelect($user, $status)) {
            $fail(trans('admin/statuslabels/message.not_authorized_to_select'));
            return;
        }

        if ($this->currentStatusId) {
            $current = Statuslabel::find($this->currentStatusId);
            $locked = app(\App\Services\Assets\AssetStatusTransitionGuardService::class)->isLockedStatus($current);
            if ($locked && !$user->isAdmin() && !$user->isSuperUser()) {
                $fail(trans('admin/statuslabels/message.locked_exit_admin_only'));
            }
        }
    }
}
