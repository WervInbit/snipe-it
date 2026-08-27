<?php

namespace App\Http\Controllers\Users;

use App\Events\UserMerged;
use App\Http\Controllers\Controller;
use App\Models\Accessory;
use App\Models\License;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Company;
use App\Models\CompanyableScope;
use App\Models\Group;
use App\Models\LicenseSeat;
use App\Models\ConsumableAssignment;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CurrentInventory;
use App\Services\Assets\LegacyAssetAssignmentCleanupService;
use App\Services\Users\PrintableUserInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BulkUsersController extends Controller
{
    /**
     * Returns a view that confirms the user's a bulk action will be applied to.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v1.7]
     * @param Request $request
     * @return \Illuminate\Contracts\View\View | \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Request $request, PrintableUserInventoryService $printableInventory)
    {
        $this->authorize('view', User::class);

        // Make sure there were users selected
        if (($request->filled('ids')) && (count($request->input('ids')) > 0)) {

            // Get the list of affected users
            $user_raw_array = request('ids');
            $users = User::whereIn('id', $user_raw_array)
                ->with('assets', 'manager', 'userlog', 'licenses', 'consumables', 'accessories', 'managedLocations','uploads', 'acceptances')->get();

            // bulk edit, display the bulk edit form
            if ($request->input('bulk_actions') == 'edit') {
                $this->authorize('update', User::class);
                return view('users/bulk-edit', compact('users'))
                    ->with('groups', Group::pluck('name', 'id'));

            // bulk send assigned inventory
            } elseif ($request->input('bulk_actions') == 'send_assigned') {
                    $this->authorize('update', User::class);

                if (! config('mail.enabled', true)) {
                    return redirect()->back()->with('error', trans('mail.delivery_disabled'));
                }

                $users_without_email = 0;
                foreach ($users as $user) {
                    if (empty($user->email)) {
                        $users_without_email++;
                    } else {
                        $user->notify((new CurrentInventory($user)));
                    }
                }

                if ($users_without_email == 0) {
                    return redirect()->back()->with('success', trans_choice('admin/users/general.users_notified', $users->count()));
                } else {
                    return redirect()->back()->with('warning', trans_choice('admin/users/general.users_notified_warning', $users->count(), ['no_email' => $users_without_email]));
                }




            // bulk delete, display the bulk delete confirmation form
            } elseif ($request->input('bulk_actions') == 'delete') {
                $this->authorize('delete', User::class);
                return view('users/confirm-bulk-delete')->with('users', $users);

            // merge, confirm they have at least 2 users selected and display the merge screen
            } elseif ($request->input('bulk_actions') == 'merge') {
                $this->authorize('delete', User::class);
                if (($request->filled('ids')) && (count($request->input('ids')) > 1)) {
                    return view('users/confirm-merge')->with('users', $users);
                // Not enough users selected, send them back
                } else {
                    return redirect()->back()->with('error', trans('general.not_enough_users_selected', ['count' => 2]));
                }

            // bulk password reset, just do the thing
            } elseif ($request->input('bulk_actions') == 'bulkpasswordreset') {
                $this->authorize('update', User::class);
                if (! config('mail.enabled', true)) {
                    return redirect()->back()->with('error', trans('mail.password_reset_disabled'));
                }
                $passwordResetUserIds = collect($user_raw_array)
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values();
                $passwordResetUsers = User::withoutGlobalScope(CompanyableScope::class)
                    ->whereIn('id', $passwordResetUserIds)
                    ->get();

                abort_if(
                    $passwordResetUserIds->isEmpty()
                    || $passwordResetUsers->count() !== $passwordResetUserIds->count(),
                    404
                );

                foreach ($passwordResetUsers as $user) {
                    $this->authorize('update', $user);
                }

                foreach ($passwordResetUsers as $user) {
                    if (($user->activated == '1') && ($user->email != '') && ($user->ldap_import != '1')) {
                        $credentials = ['email' => $user->email];
                        Password::sendResetLink($credentials/* , function (Message $message) {
                        $message->subject($this->getEmailSubject()); // TODO - I'm not sure if we still need this, but this second parameter is no longer accepted in later Laravel versions.
                        } */ );                                      // TODO - so hopefully this doesn't give us generic password reset messages? But it at least _works_
                    }
                }
                return redirect()->back()->with('success', trans('admin/users/message.password_resets_sent'));

            } elseif ($request->input('bulk_actions') == 'print') {
                $actor = $request->user();
                $users = collect($request->input('ids'))
                    ->map(fn ($id) => $printableInventory->findFor($actor, (int) $id))
                    ->filter()
                    ->values();

                $users->each(fn($user) => $this->authorize('view', $user));

                return view('users.print')
                    ->with('users', $users)
                    ->with('settings', Setting::getSettings());
            }
        }

        return redirect()->back()->with('error', trans('general.no_users_selected'));
    }

    /**
     * Save bulk-edited users
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v1.0]
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request)
    {
        $this->authorize('update', User::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id', 'fmcs_location'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'locale' => ['nullable', 'string', 'max:10'],
            'remote' => ['nullable', 'boolean'],
            'ldap_import' => ['nullable', 'boolean'],
            'activated' => ['nullable', 'boolean'],
            'autoassign_licenses' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'city' => ['nullable', 'string', 'max:191'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['nullable', 'integer', 'distinct', 'exists:permission_groups,id'],
            'null_location_id' => ['nullable', 'boolean'],
            'null_department_id' => ['nullable', 'boolean'],
            'null_company_id' => ['nullable', 'boolean'],
            'null_manager_id' => ['nullable', 'boolean'],
            'null_start_date' => ['nullable', 'boolean'],
            'null_end_date' => ['nullable', 'boolean'],
            'null_locale' => ['nullable', 'boolean'],
        ]);

        if (($request->has('company_id') || $request->boolean('null_company_id'))
            && ! Company::canManageUsersCompanies()
        ) {
            abort(403);
        }

        $syncGroups = $request->has('groups');
        if ($syncGroups && ! $request->user()->isSuperUser()) {
            abort(403);
        }

        $userIds = collect($validated['ids'])
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === (int) auth()->id())
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return redirect()->back()->with('error', trans('general.no_users_selected'));
        }

        $users = User::whereIn('id', $userIds)->get();
        if ($users->count() !== $userIds->count()) {
            return redirect()->back()->with('error', trans('general.no_users_selected'));
        }

        foreach ($users as $user) {
            $this->authorize('update', $user);
        }

        if ($request->filled('manager_id')
            && $userIds->contains((int) $validated['manager_id'])
        ) {
            throw ValidationException::withMessages([
                'manager_id' => trans('admin/users/message.bulk_manager_warn'),
            ]);
        }

        $updates = [];
        foreach ([
            'location_id',
            'department_id',
            'company_id',
            'manager_id',
            'locale',
            'remote',
            'ldap_import',
            'activated',
            'start_date',
            'end_date',
            'city',
            'autoassign_licenses',
        ] as $field) {
            if ($request->filled($field)) {
                $updates[$field] = $validated[$field];
            }
        }

        foreach ([
            'null_location_id' => 'location_id',
            'null_department_id' => 'department_id',
            'null_company_id' => 'company_id',
            'null_manager_id' => 'manager_id',
            'null_start_date' => 'start_date',
            'null_end_date' => 'end_date',
            'null_locale' => 'locale',
        ] as $nullFlag => $field) {
            if ($request->boolean($nullFlag)) {
                $updates[$field] = null;
            }
        }

        if (array_key_exists('company_id', $updates)) {
            $updates['company_id'] = Company::getIdForUser($updates['company_id']);
        }

        $groupIds = collect($validated['groups'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $actor = $request->user();

        DB::transaction(function () use ($actor, $groupIds, $syncGroups, $updates, $users): void {
            foreach ($users as $user) {
                $canEditAuthFields = $actor->can('canEditAuthFields', $user)
                    && $actor->can('editableOnDemo');
                $userUpdates = $updates;

                if (! $canEditAuthFields) {
                    unset($userUpdates['activated'], $userUpdates['ldap_import']);
                }

                $user->fill($userUpdates);
                if (! $user->save()) {
                    throw ValidationException::withMessages($user->getErrors()->toArray());
                }

                if ($syncGroups && $canEditAuthFields) {
                    $user->groups()->sync($groupIds);
                }
            }
        });

        return redirect()->route('users.index')
            ->with('success', trans('admin/users/message.success.update_bulk'));
    }

    /**
     * Soft-delete bulk users
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v1.0]
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(
        Request $request,
        LegacyAssetAssignmentCleanupService $legacyAssignmentCleanup
    )
    {
        $this->authorize('delete', User::class);

        if ((! $request->filled('ids')) || (count($request->input('ids')) == 0)) {
            return redirect()->back()->with('error', trans('general.no_users_selected'));
        }

        if (! $request->boolean('delete_user')) {
            return redirect()->back()->with('error', trans('admin/users/message.error.delete'));
        }

        if (config('app.lock_passwords')) {
            return redirect()->route('users.index')->with('error', trans('general.feature_disabled'));
        }

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'delete_user' => ['accepted'],
        ]);

        $requestedUserIds = collect($validated['ids'])
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === (int) auth()->id())
            ->values();

        $users = User::whereIn('id', $requestedUserIds)->get();

        if ($users->count() !== $requestedUserIds->count()) {
            abort(403);
        }

        foreach ($users as $user) {
            $this->authorize('delete', $user);
        }

        $actor = $request->user();
        if ($users->contains(
            fn (User $user) => ! $actor->can('canEditAuthFields', $user)
                || ! $actor->can('editableOnDemo')
        )) {
            return redirect()->route('users.index')
                ->with('error', trans('general.insufficient_permissions'));
        }

        $authorizedUserIds = $users->modelKeys();
        $assets = Asset::whereIn('assigned_to', $authorizedUserIds)
            ->where('assigned_type', User::class)
            ->get();
        $accessoryUserRows = DB::table('accessories_checkout')
            ->where('assigned_type', User::class)
            ->whereIn('assigned_to', $authorizedUserIds)
            ->get();
        $licenses = DB::table('license_seats')
            ->whereIn('assigned_to', $authorizedUserIds)
            ->get();
        $consumableUserRows = DB::table('consumables_users')
            ->whereIn('assigned_to', $authorizedUserIds)
            ->get();

        DB::transaction(function () use (
            $accessoryUserRows,
            $assets,
            $consumableUserRows,
            $legacyAssignmentCleanup,
            $licenses,
            $users
        ): void {
            $assets->each(
                fn (Asset $asset) => $legacyAssignmentCleanup->clear($asset)
            );
            $this->logAccessoriesCheckin($accessoryUserRows);
            $this->logLicenseCheckins($licenses);

            LicenseSeat::whereIn('id', $licenses->pluck('id'))->update(['assigned_to' => null]);
            ConsumableAssignment::whereIn('id', $consumableUserRows->pluck('id'))->delete();

            foreach ($users as $user) {
                $user->accessories()->sync([]);
                $user->delete();
            }
        });

        return redirect()->route('users.index')
            ->with('success', trans('general.bulk_checkin_delete_success'));
    }

    /**
     * Preserve check-in audit history for license seats handled by this shared
     * user cleanup flow. Assets use silent legacy-state cleanup instead.
     */
    protected function logLicenseCheckins($licenses)
    {
        foreach ($licenses as $license) {
            $logAction = new Actionlog();
            $logAction->item_id = $license->license_id;
            // We can't rely on get_class here because the licenses/accessories fetched above are not eloquent models, but simply arrays.
            $logAction->item_type = License::class;
            $logAction->target_id = $license->assigned_to;
            $logAction->target_type = User::class;
            $logAction->created_by = auth()->id();
            $logAction->note = 'Bulk checkin items';
            $logAction->logaction('checkin from');
        }
    }

    private function logAccessoriesCheckin(Collection $accessoryUserRows): void
    {
        foreach ($accessoryUserRows as $accessoryUserRow) {
            $logAction = new Actionlog();
            $logAction->item_id = $accessoryUserRow->accessory_id;
            $logAction->item_type = Accessory::class;
            $logAction->target_id = $accessoryUserRow->assigned_to;
            $logAction->target_type = User::class;
            $logAction->created_by = auth()->id();
            $logAction->note = 'Bulk checkin items';
            $logAction->logaction('checkin from');
        }
    }

    /**
     * Save bulk-edited users
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v1.0]
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function merge(Request $request, LegacyAssetAssignmentCleanupService $legacyAssignmentCleanup)
    {
        $this->authorize('update', User::class);
        $this->authorize('delete', User::class);

        if (config('app.lock_passwords')) {
            return redirect()->route('users.index')->with('error', trans('general.feature_disabled'));
        }

        $validated = $request->validate([
            'ids_to_merge' => ['required', 'array', 'min:1'],
            'ids_to_merge.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'merge_into_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $mergeIntoId = (int) $validated['merge_into_id'];
        $userIdsToMerge = collect($validated['ids_to_merge'])
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === $mergeIntoId)
            ->unique()
            ->values();

        if ($userIdsToMerge->isEmpty() || $userIdsToMerge->contains((int) auth()->id())) {
            return redirect()->back()->with('error', trans('general.no_users_selected'));
        }

        $mergeIntoUser = User::findOrFail($mergeIntoId);
        $usersToMerge = User::whereIn('id', $userIdsToMerge)
            ->with(
                'assets',
                'manager',
                'userlog',
                'licenses',
                'consumables',
                'accessories',
                'managedLocations',
                'uploads',
                'acceptances'
            )
            ->get();

        if ($usersToMerge->count() !== $userIdsToMerge->count()) {
            return redirect()->back()->with('error', trans('general.no_users_selected'));
        }

        $admin = $request->user();

        $this->authorize('update', $mergeIntoUser);
        if (
            ! $admin->can('canEditAuthFields', $mergeIntoUser)
            || ! $admin->can('editableOnDemo')
        ) {
            return redirect()->route('users.index')
                ->with('error', trans('general.insufficient_permissions'));
        }

        foreach ($usersToMerge as $userToMerge) {
            $this->authorize('delete', $userToMerge);

            if (
                ! $admin->can('canEditAuthFields', $userToMerge)
                || ! $admin->can('editableOnDemo')
            ) {
                return redirect()->route('users.index')
                    ->with('error', trans('general.insufficient_permissions'));
            }
        }

        DB::transaction(function () use (
            $admin,
            $legacyAssignmentCleanup,
            $mergeIntoUser,
            $usersToMerge
        ): void {
            foreach ($usersToMerge as $userToMerge) {
                foreach ($userToMerge->assets as $asset) {
                    Log::debug('Clearing retired asset assignment during user merge: '.$asset->asset_tag);
                    $legacyAssignmentCleanup->clear($asset);
                }

                foreach ($userToMerge->licenses as $license) {
                    Log::debug('Updating license pivot: '.$license->id.' to '.$mergeIntoUser->id);
                    $userToMerge->licenses()->updateExistingPivot($license->id, ['assigned_to' => $mergeIntoUser->id]);
                }

                foreach ($userToMerge->consumables as $consumable) {
                    Log::debug('Updating consumable pivot: '.$consumable->id.' to '.$mergeIntoUser->id);
                    $userToMerge->consumables()->updateExistingPivot($consumable->id, ['assigned_to' => $mergeIntoUser->id]);
                }

                foreach ($userToMerge->accessories as $accessory) {
                    $userToMerge->accessories()->updateExistingPivot($accessory->id, ['assigned_to' => $mergeIntoUser->id]);
                }

                foreach ($userToMerge->userlog as $log) {
                    $log->target_id = $mergeIntoUser->id;
                    $log->save();
                }

                foreach ($userToMerge->uploads as $upload) {
                    $upload->item_id = $mergeIntoUser->id;
                    $upload->save();
                }

                User::where('manager_id', $userToMerge->id)
                    ->update(['manager_id' => $mergeIntoUser->id]);

                foreach ($userToMerge->managedLocations as $managedLocation) {
                    $managedLocation->manager_id = $mergeIntoUser->id;
                    $managedLocation->save();
                }

                $userToMerge->delete();
                event(new UserMerged($userToMerge, $mergeIntoUser, $admin));
            }
        });

        return redirect()->route('users.index')->with('success', trans('general.merge_success', [
            'count' => $usersToMerge->count(),
            'into_username' => $mergeIntoUser->username,
        ]));

    }
}
