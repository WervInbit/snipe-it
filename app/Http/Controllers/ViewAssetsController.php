<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use \Illuminate\Contracts\View\View;

/**
 * This controller handles all actions related to the ability for users
 * to view their own assets in the Snipe-IT Asset Management application.
 *
 * @version    v1.0
 */
class ViewAssetsController extends Controller
{
    /**
     * Extract custom fields that should be displayed in user view.
     *
     * @param User $user
     * @return array
     */
    private function extractCustomFields(User $user): array
    {
        $fieldArray = [];
        foreach ($user->assets as $asset) {
            if ($asset->model && $asset->model->fieldset) {
                foreach ($asset->model->fieldset->fields as $field) {
                    if ($field->display_in_user_view == '1') {
                        $fieldArray[$field->db_column] = $field->name;
                    }
                }
            }
        }
        return array_unique($fieldArray);
    }

    /**
     * Get list of users viewable by the current user.
     *
     * @param User $authUser
     * @return \Illuminate\Support\Collection
     */
    private function getViewableUsers(User $authUser): \Illuminate\Support\Collection
    {
        // SuperAdmin sees all users
        if ($authUser->isSuperUser()) {
            return User::select('id', 'first_name', 'last_name', 'username')
                ->where('activated', 1)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        // Regular manager sees only their subordinates + self
        $managedUsers = $authUser->getAllSubordinates();
        
        // If user has subordinates, show them with self at beginning
        if ($managedUsers->count() > 0) {
            return collect([$authUser])->merge($managedUsers)
                ->sortBy('last_name')
                ->sortBy('first_name');
        }
        
        // User has no subordinates, only sees themselves
        return collect([$authUser]);
    }

    /**
     * Get the selected user ID from request or default to current user.
     *
     * @param Request $request
     * @param \Illuminate\Support\Collection $subordinates
     * @param int $defaultUserId
     * @return int
     */
    private function getSelectedUserId(Request $request, \Illuminate\Support\Collection $subordinates, int $defaultUserId): int
    {
        // If no subordinates or no user_id in request, return default
        if ($subordinates->count() <= 1 || !$request->filled('user_id')) {
            return $defaultUserId;
        }

        $requestedUserId = (int) $request->input('user_id');
        
        // Validate if the requested user is allowed
        if ($subordinates->contains('id', $requestedUserId)) {
            return $requestedUserId;
        }
        
        // If invalid ID or not authorized, return default
        return $defaultUserId;
    }

    /**
     * Show user's assigned assets with optional manager view functionality.
     *
     */
    public function getIndex(Request $request) : View | RedirectResponse
    {
        $authUser = auth()->user();
        $settings = Setting::getSettings();
        $subordinates = collect();
        $selectedUserId = $authUser->id;

        // Process manager view if enabled
        if ($settings->manager_view_enabled) {
            $subordinates = $this->getViewableUsers($authUser);
            $selectedUserId = $this->getSelectedUserId($request, $subordinates, $authUser->id);
        }

        // Load the data for the user to be viewed (either auth user or selected subordinate)
        $userToView = User::with([
            'assets',
            'assets.model',
            'assets.model.fieldset.fields',
            'consumables',
            'accessories',
            'licenses'
        ])->find($selectedUserId);

        // If the user to view couldn't be found (shouldn't happen with proper logic), redirect with error
        if (!$userToView) {
            return redirect()->route('view-assets')->with('error', trans('admin/users/message.user_not_found'));
        }

        // Process custom fields for the user being viewed
        $fieldArray = $this->extractCustomFields($userToView);

        // Pass the necessary data to the view
        return view('account/view-assets', [
            'user' => $userToView, // Use 'user' for compatibility with the existing view
            'field_array' => $fieldArray,
            'settings' => $settings,
            'subordinates' => $subordinates,
            'selectedUserId' => $selectedUserId
        ]);
    }

}
