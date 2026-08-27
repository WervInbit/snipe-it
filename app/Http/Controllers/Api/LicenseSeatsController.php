<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Transformers\LicenseSeatsTransformer;
use App\Models\Asset;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LicenseSeatsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $licenseId
     */
    public function index(Request $request, $licenseId) : JsonResponse | array
    {

        if ($license = License::find($licenseId)) {
            $this->authorize('view', $license);

            $seats = LicenseSeat::with('license', 'user', 'asset', 'user.department')
                ->where('license_seats.license_id', $licenseId);

            if ($request->input('status') == 'available') {
                $seats->whereNull('license_seats.assigned_to');
            }

            if ($request->input('status') == 'assigned') {
                $seats->ByAssigned();
            }


            $order = $request->input('order') === 'asc' ? 'asc' : 'desc';

            if ($request->input('sort') == 'department') {
                $seats->OrderDepartments($order);
            } else {
                $seats->orderBy('updated_at', $order);
            }

            $total = $seats->count();
            $limit = app('api_limit_value');
            $offset = $this->resolveOffset($request, $total, $limit);

            $seats = $seats->skip($offset)->take($limit)->get();

            if ($seats) {
                return (new LicenseSeatsTransformer)->transformLicenseSeats($seats, $total);
            }
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/licenses/message.does_not_exist')), 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $licenseId
     * @param  int  $seatId
     */
    public function show($licenseId, $seatId) : JsonResponse | array
    {

        $this->authorize('view', License::class);
        // sanity checks:
        // 1. does the license seat exist?
        if (! $licenseSeat = LicenseSeat::find($seatId)) {
            return response()->json(Helper::formatStandardApiResponse('error', null, 'Seat not found'));
        }
        // 2. does the seat belong to the specified license?
        $license = $licenseSeat->license()->first();
        if (! $license || $license->id !== (int) $licenseId) {
            return response()->json(Helper::formatStandardApiResponse('error', null, 'Seat does not belong to the specified license'));
        }

        return (new LicenseSeatsTransformer)->transformLicenseSeat($licenseSeat);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $licenseId
     * @param  int  $seatId
     */
    public function update(Request $request, $licenseId, $seatId) : JsonResponse | array
    {
        if (! $licenseSeat = LicenseSeat::find($seatId)) {
            return response()->json(Helper::formatStandardApiResponse('error', null, 'Seat not found'));
        }

        $license = $licenseSeat->license()->first();
        if (!$license || $license->id != intval($licenseId)) {
            return response()->json(Helper::formatStandardApiResponse('error', null, 'Seat does not belong to the specified license'));
        }

        return DB::transaction(function () use ($request, $licenseId, $seatId): JsonResponse|array {
            $licenseSeat = LicenseSeat::query()->whereKey($seatId)->lockForUpdate()->first();
            if (!$licenseSeat) {
                return response()->json(Helper::formatStandardApiResponse('error', null, 'Seat not found'));
            }

            $license = $licenseSeat->license;
            if (!$license || $license->id !== (int) $licenseId) {
                return response()->json(Helper::formatStandardApiResponse('error', null, 'Seat does not belong to the specified license'));
            }

            $oldUser = $licenseSeat->user;
            $oldAsset = $licenseSeat->asset;
            $wasAssigned = $oldUser !== null || $oldAsset !== null;

            $licenseSeat->fill($request->only(['assigned_to', 'asset_id', 'notes']));
            $licenseSeat->created_by = auth()->id();

            $touched = $licenseSeat->isDirty('assigned_to') || $licenseSeat->isDirty('asset_id');
            if (!$touched) {
                $this->authorize('checkout', $license);

                return response()->json(Helper::formatStandardApiResponse(
                    'success',
                    $licenseSeat,
                    trans('admin/licenses/message.update.success')
                ));
            }

            $isCheckin = $licenseSeat->assigned_to === null && $licenseSeat->asset_id === null;
            $this->authorize($isCheckin ? 'checkin' : 'checkout', $license);

            if ($isCheckin && ! $license->reassignable) {
                return response()->json(Helper::formatStandardApiResponse(
                    'error',
                    null,
                    trans('admin/licenses/message.checkin.not_reassignable').'.'
                ));
            }

            if (!$isCheckin && $license->isInactive()) {
                return response()->json(Helper::formatStandardApiResponse(
                    'error',
                    null,
                    trans('admin/licenses/message.checkout.license_is_inactive')
                ));
            }

            if (!$isCheckin && $wasAssigned) {
                return response()->json(Helper::formatStandardApiResponse(
                    'error',
                    null,
                    trans('admin/licenses/message.checkout.unavailable')
                ));
            }

            if (!$isCheckin && $licenseSeat->assigned_to !== null && $licenseSeat->asset_id !== null) {
                return response()->json(Helper::formatStandardApiResponse(
                    'error',
                    null,
                    trans('admin/licenses/message.select_asset_or_person')
                ));
            }

            $target = $isCheckin
                ? ($oldAsset ?? $oldUser)
                : ($licenseSeat->asset_id !== null
                    ? Asset::find($licenseSeat->asset_id)
                    : User::find($licenseSeat->assigned_to));

            if (!$target) {
                return response()->json(Helper::formatStandardApiResponse('error', null, 'Target not found'));
            }

            if (! $isCheckin && ! $license->canCheckoutTo($target)) {
                return response()->json(Helper::formatStandardApiResponse(
                    'error',
                    null,
                    trans('general.error_user_company')
                ));
            }

            if (!$licenseSeat->save()) {
                return Helper::formatStandardApiResponse('error', null, $licenseSeat->getErrors());
            }

            if ($isCheckin) {
                $licenseSeat->logCheckin($target, $request->input('notes'));
            } else {
                $licenseSeat->logCheckout($request->input('notes'), $target);
            }

            return response()->json(Helper::formatStandardApiResponse(
                'success',
                $licenseSeat,
                trans('admin/licenses/message.update.success')
            ));
        });
    }
}
