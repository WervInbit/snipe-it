<?php

namespace App\Http\Controllers\Licenses;

use App\Events\CheckoutableCheckedIn;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LicenseCheckinController extends Controller
{
    /**
     * Makes the form view to check a license seat back into inventory.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v1.0]
     * @param int $seatId
     * @param string $backTo
     * @return \Illuminate\Contracts\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(LicenseSeat $licenseSeat, $backTo = null)
    {
        // Check if the asset exists
        $license = License::find($licenseSeat->license_id);
        $this->authorize('checkin', $license);
        return view('licenses/checkin', compact('licenseSeat'))->with('backto', $backTo);
    }

    /**
     * Validates and stores the license checkin action.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @see LicenseCheckinController::create() method that provides the form view
     * @since [v1.0]
     * @param int $seatId
     * @param string $backTo
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request, $seatId = null, $backTo = null)
    {
        // Check if the asset exists
        if (is_null($licenseSeat = LicenseSeat::find($seatId))) {
            // Redirect to the asset management page with error
            return redirect()->route('licenses.index')->with('error', trans('admin/licenses/message.not_found'));
        }

        $license = License::find($licenseSeat->license_id);

        // LicenseSeat is not assigned, it can't be checked in
        if (is_null($licenseSeat->assigned_to) && is_null($licenseSeat->asset_id)) {
            return redirect()->route('licenses.index')->with('error', trans('admin/licenses/message.checkin.error'));
        }

        $this->authorize('checkin', $license);

        if (! $license->reassignable) {
            // Not allowed to checkin
            Session::flash('error', trans('admin/licenses/message.checkin.not_reassignable') . '.');

            return redirect()->back()->withInput();
        }

        // Declare the rules for the form validation
        $rules = [
            'notes'   => 'string|nullable',
        ];

        // Create a new validator instance from our validation rules
        $validator = Validator::make($request->all(), $rules);

        // If validation fails, we'll exit the operation now.
        if ($validator->fails()) {
            // Ooops.. something went wrong
            return redirect()->back()->withInput()->withErrors($validator);
        }

        if ($licenseSeat->asset_id !== null) {
            $return_to = Asset::find($licenseSeat->asset_id);
        } else {
            $return_to = User::withTrashed()->find($licenseSeat->assigned_to);
            if ($return_to) {
                session()->put('checkedInFrom', $return_to->id);
            }
        }

        // Update the asset data
        $licenseSeat->assigned_to = null;
        $licenseSeat->asset_id = null;
        $licenseSeat->notes = $request->input('notes');

        session()->put(['redirect_option' => $request->get('redirect_option')]);
        if ($request->get('redirect_option') === 'target'){
            session()->put(['checkout_to_type' => 'user']);
        }

        // Was the asset updated?
        if ($licenseSeat->save()) {
            event(new CheckoutableCheckedIn($licenseSeat, $return_to, auth()->user(), $request->input('notes')));


            return Helper::getRedirectOption($request, $license->id, 'Licenses')
                ->with('success', trans('admin/licenses/message.checkin.success'));
        }

        // Redirect to the license page with error
        return redirect()->route('licenses.index')->with('error', trans('admin/licenses/message.checkin.error'));
    }

    /**
     * Bulk checkin all license seats
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @see LicenseCheckinController::create() method that provides the form view
     * @since [v6.1.1]
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */

    public function bulkCheckin(Request $request, $licenseId) {

        $license = License::findOrFail($licenseId);
        $this->authorize('checkin', $license);

        if (! $license->reassignable) {
            // Not allowed to checkin
            Session::flash('error', 'License not reassignable.');

            return redirect()->back()->withInput();
        }

        $count = DB::transaction(function () use ($license, $licenseId): int {
            $licenseSeats = LicenseSeat::where('license_id', $licenseId)
                ->byAssigned()
                ->with(['asset', 'user'])
                ->lockForUpdate()
                ->get();
            $checkedIn = 0;

            foreach ($licenseSeats as $licenseSeat) {
                // Asset checkouts can also retain the asset owner's user id.
                // They still represent one seat and must produce one check-in.
                $target = $licenseSeat->asset ?? $licenseSeat->user;
                $targetDescription = $target
                    ? class_basename($target).' '.$target->getKey()
                    : 'an unavailable historical target';

                $licenseSeat->assigned_to = null;
                $licenseSeat->asset_id = null;

                if (! $licenseSeat->save()) {
                    throw new RuntimeException('Unable to bulk check in license seat '.$licenseSeat->getKey().'.');
                }

                Log::debug('Checking in '.$license->name.' from '.$targetDescription);
                $licenseSeat->logCheckin($target, trans('admin/licenses/general.bulk.checkin_all.log_msg'));
                $checkedIn++;
            }

            return $checkedIn;
        });

        return redirect()->back()->with(
            'success',
            trans_choice('admin/licenses/general.bulk.checkin_all.success', $count, ['count' => $count])
        );

    }

}
