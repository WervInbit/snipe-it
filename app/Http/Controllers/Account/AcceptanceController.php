<?php

namespace App\Http\Controllers\Account;

use App\Events\CheckoutAccepted;
use App\Events\CheckoutDeclined;
use App\Events\ItemAccepted;
use App\Events\ItemDeclined;
use App\Http\Controllers\Controller;
use App\Mail\CheckoutAcceptanceResponseMail;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\Company;
use App\Models\Contracts\Acceptable;
use App\Models\Setting;
use App\Models\User;
use App\Models\AssetModel;
use App\Models\Accessory;
use App\Models\License;
use App\Models\Component;
use App\Models\Consumable;
use App\Services\Assets\LegacyAssetAssignmentCleanupService;
use App\Services\SafeRasterImageService;
use App\Notifications\AcceptanceAssetAcceptedNotification;
use App\Notifications\AcceptanceAssetAcceptedToUserNotification;
use App\Notifications\AcceptanceAssetDeclinedNotification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\SettingsController;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use \Illuminate\Contracts\View\View;
use \Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AcceptanceController extends Controller
{
    /**
     * Show a listing of pending checkout acceptances for the current user
     */
    public function index() : View
    {
        $acceptances = CheckoutAcceptance::forUser(auth()->user())
            ->pending()
            ->actionable()
            ->get();

        return view('account/accept.index', compact('acceptances'));
    }

    /**
     * Shows a form to either accept or decline the checkout acceptance
     *
     * @param  int  $id
     */
    public function create(
        $id,
        LegacyAssetAssignmentCleanupService $legacyAssignmentCleanup
    ) : View | RedirectResponse
    {
        $acceptance = CheckoutAcceptance::find($id);


        if (is_null($acceptance)) {
            return redirect()->route('account.accept')->with('error', trans('admin/hardware/message.does_not_exist'));
        }

        if (! $acceptance->isPending()) {
            return redirect()->route('account.accept')->with('error', trans('admin/users/message.error.asset_already_accepted'));
        }

        if (! $acceptance->isCheckedOutTo(auth()->user())) {
            return redirect()->route('account.accept')->with('error', trans('admin/users/message.error.incorrect_user_accepted'));
        }

        if (! Company::isCurrentUserHasAccess($acceptance->checkoutable)) {
            return redirect()->route('account.accept')->with('error', trans('general.error_user_company'));
        }

        if ($legacyAssignmentCleanup->retirePendingAcceptance($acceptance)) {
            return redirect()->route('account.accept')
                ->with('error', trans('admin/hardware/message.legacy_assignment_disabled'));
        }

        return view('account/accept.create', compact('acceptance'));
    }

    /**
     * Stores the accept/decline of the checkout acceptance
     *
     * @param  Request $request
     * @param  int  $id
     */
    public function store(
        Request $request,
        $id,
        LegacyAssetAssignmentCleanupService $legacyAssignmentCleanup
    ) : RedirectResponse
    {
        $acceptance = CheckoutAcceptance::find($id);

        if (is_null($acceptance)) {
            return redirect()->route('account.accept')->with('error', trans('admin/hardware/message.does_not_exist'));
        }

        if (! $acceptance->isPending()) {
            return redirect()->route('account.accept')->with('error', trans('admin/users/message.error.asset_already_accepted'));
        }

        if (! $acceptance->isCheckedOutTo(auth()->user())) {
            return redirect()->route('account.accept')->with('error', trans('admin/users/message.error.incorrect_user_accepted'));
        }

        if (! Company::isCurrentUserHasAccess($acceptance->checkoutable)) {
            return redirect()->route('account.accept')->with('error', trans('general.insufficient_permissions'));
        }

        if ($legacyAssignmentCleanup->retirePendingAcceptance($acceptance)) {
            return redirect()->route('account.accept')
                ->with('error', trans('admin/hardware/message.legacy_assignment_disabled'));
        }

        if (! $request->filled('asset_acceptance')) {
            return redirect()->back()->with('error', trans('admin/users/message.error.accept_or_decline'));
        }

        if (! in_array($request->input('asset_acceptance'), ['accepted', 'declined'], true)) {
            return redirect()->back()->with('error', trans('admin/users/message.error.accept_or_decline'));
        }



        $item = $acceptance->checkoutable_type::find($acceptance->checkoutable_id);
        if (! $item) {
            return redirect()->route('account.accept')
                ->with('error', trans('admin/hardware/message.does_not_exist'));
        }

        $display_model = '';
        $pdf_view_route = '';
        $pdf_filename = 'accepted-eula-'.Str::uuid().'-'.date('Y-m-d-h-i-s').'.pdf';
        $sig_filename='';
        $signatureDataUri = null;
        $responsePersisted = false;

        try {
            if (Setting::getSettings()->require_accept_signature == '1') {
                if (! $request->filled('signature_output')) {
                    return redirect()->back()->with('error', trans('general.shitty_browser'));
                }

                $sig_filename = $this->storeValidatedSignature(
                    (string) $request->input('signature_output')
                );
                $signatureDataUri = 'data:image/png;base64,'.base64_encode(
                    Storage::get('private_uploads/signatures/'.$sig_filename)
                );
            }

        if ($request->input('asset_acceptance') == 'accepted') {

            /**
             * Check for the eula-pdfs directory
             */
            if (! Storage::exists('private_uploads/eula-pdfs')
                && ! Storage::makeDirectory('private_uploads/eula-pdfs', 775)
            ) {
                throw new RuntimeException('Unable to create the stored-EULA directory.');
            }

            $assigned_user = User::find($acceptance->assigned_to_id);
            // this is horrible
            switch($acceptance->checkoutable_type){
                case 'App\Models\Asset':
                        $pdf_view_route ='account.accept.accept-asset-eula';
                        $asset_model = AssetModel::find($item->model_id);
                        if (!$asset_model) {
                            return redirect()->back()->with('error', trans('admin/models/message.does_not_exist'));
                        }
                        $display_model = $asset_model->name;
                break;

                case 'App\Models\Accessory':
                        $pdf_view_route ='account.accept.accept-accessory-eula';
                        $accessory = Accessory::find($item->id);
                        $display_model = $accessory->name;
                break;

                case 'App\Models\LicenseSeat':
                        $pdf_view_route ='account.accept.accept-license-eula';
                        $license = License::find($item->license_id);
                        $display_model = $license->name;
                break;

                case 'App\Models\Component':
                        $pdf_view_route ='account.accept.accept-component-eula';
                        $component = Component::find($item->id);
                        $display_model = $component->name;
                break;

                case 'App\Models\Consumable':
                        $pdf_view_route ='account.accept.accept-consumable-eula';
                        $consumable = Consumable::find($item->id);
                        $display_model = $consumable->name;
                break;
            }
//            if ($acceptance->checkoutable_type == 'App\Models\Asset') {
//                $pdf_view_route ='account.accept.accept-asset-eula';
//                $asset_model = AssetModel::find($item->model_id);
//                $display_model = $asset_model->name;
//                $assigned_to = User::find($item->assigned_to)->present()->fullName;
//
//            } elseif ($acceptance->checkoutable_type== 'App\Models\Accessory') {
//                $pdf_view_route ='account.accept.accept-accessory-eula';
//                $accessory = Accessory::find($item->id);
//                $display_model = $accessory->name;
//                $assigned_to = User::find($item->assignedTo);
//
//            }

            /**
             * Gather the data for the PDF. We fire this whether there is a signature required or not,
             * since we want the moment-in-time proof of what the EULA was when they accepted it.
             */
            $branding_settings = SettingsController::getPDFBranding();

            $path_logo = "";

            // Check for the PDF logo path and use that, otherwise use the regular logo path
            if (!is_null($branding_settings->acceptance_pdf_logo)) {
                $path_logo = public_path() . '/uploads/' . $branding_settings->acceptance_pdf_logo;
            } elseif (!is_null($branding_settings->logo)) {
                $path_logo = public_path() . '/uploads/' . $branding_settings->logo;
            }
            
            $data = [
                'item_tag' => $item->asset_tag,
                'item_model' => $display_model,
                'item_serial' => $item->serial,
                'item_status' => $item->assetstatus?->name,
                'eula' => $item->getEula(),
                'note' => $request->input('note'),
                'check_out_date' => Carbon::parse($acceptance->created_at)->format('Y-m-d'),
                'accepted_date' => Carbon::parse($acceptance->accepted_at)->format('Y-m-d'),
                'assigned_to' => $assigned_user->present()->fullName,
                'company_name' => $branding_settings->site_name,
                'signature' => $signatureDataUri,
                'logo' => $path_logo,
                'date_settings' => $branding_settings->date_display_format,
                'admin' => auth()->user()->present()?->fullName,
            ];

            if ($pdf_view_route!='') {
                Log::debug($pdf_filename.' is the filename, and the route was specified.');
                $pdf = Pdf::loadView($pdf_view_route, $data);
                if (! Storage::put('private_uploads/eula-pdfs/'.$pdf_filename, $pdf->output())) {
                    throw new RuntimeException('Unable to store the accepted EULA PDF.');
                }
            }

            DB::transaction(function () use (
                &$acceptance,
                $item,
                $pdf_filename,
                $request,
                $sig_filename
            ): void {
                $acceptance = CheckoutAcceptance::query()
                    ->lockForUpdate()
                    ->findOrFail($acceptance->getKey());

                if (! $acceptance->isPending()) {
                    throw ValidationException::withMessages([
                        'asset_acceptance' => trans('admin/users/message.error.asset_already_accepted'),
                    ]);
                }

                $acceptance->accept(
                    $sig_filename,
                    $item->getEula(),
                    $pdf_filename,
                    $request->input('note')
                );
                event(new CheckoutAccepted($acceptance));
            });
            $responsePersisted = true;

            // Send the PDF to the signing user
            if (($request->input('send_copy') == '1') && ($assigned_user->email !='')) {

                // Add the attachment for the signing user into the $data array
                $data['file'] = $pdf_filename;

                try {
                    $assigned_user->notify(new AcceptanceAssetAcceptedToUserNotification($data));
                } catch (\Exception $e) {
                    Log::warning($e);
                }
            }
            try {
                $acceptance->notify(new AcceptanceAssetAcceptedNotification($data));
            } catch (\Exception $e) {
                Log::warning($e);
            }
            $return_msg = trans('admin/users/message.accepted');

        } else {

            /**
             * Check for the eula-pdfs directory
             */
            if (! Storage::exists('private_uploads/eula-pdfs')
                && ! Storage::makeDirectory('private_uploads/eula-pdfs', 775)
            ) {
                throw new RuntimeException('Unable to create the stored-EULA directory.');
            }

            // Format the data to send the declined notification
            $branding_settings = SettingsController::getPDFBranding();

            // This is the most horriblest
            switch($acceptance->checkoutable_type){
                case 'App\Models\Asset':
                    $asset_model = AssetModel::find($item->model_id);
                    $display_model = $asset_model->name;
                    $assigned_to = User::find($acceptance->assigned_to_id)->present()->fullName;
                    break;

                case 'App\Models\Accessory':
                    $accessory = Accessory::find($item->id);
                    $display_model = $accessory->name;
                    $assigned_to = User::find($acceptance->assigned_to_id)->present()->fullName;
                    break;

                case 'App\Models\LicenseSeat':
                    $assigned_to = User::find($acceptance->assigned_to_id)->present()->fullName;
                    break;

                case 'App\Models\Component':
                    $assigned_to = User::find($acceptance->assigned_to_id)->present()->fullName;
                    break;

                case 'App\Models\Consumable':
                    $consumable = Consumable::find($item->id);
                    $display_model = $consumable->name;
                    $assigned_to = User::find($acceptance->assigned_to_id)->present()->fullName;
                    break;
            }

            $data = [
                'item_tag' => $item->asset_tag,
                'item_model' => $display_model,
                'item_serial' => $item->serial,
                'item_status' => $item->assetstatus?->name,
                'note' => $request->input('note'),
                'declined_date' => Carbon::parse($acceptance->declined_at)->format('Y-m-d'),
                'signature' => $signatureDataUri,
                'assigned_to' => $assigned_to,
                'company_name' => $branding_settings->site_name,
                'date_settings' => $branding_settings->date_display_format,
            ];

            if ($pdf_view_route!='') {
                Log::debug($pdf_filename.' is the filename, and the route was specified.');
                $pdf = Pdf::loadView($pdf_view_route, $data);
                Storage::put('private_uploads/eula-pdfs/' .$pdf_filename, $pdf->output());
            }

            DB::transaction(function () use (&$acceptance, $request, $sig_filename): void {
                $acceptance = CheckoutAcceptance::query()
                    ->lockForUpdate()
                    ->findOrFail($acceptance->getKey());

                if (! $acceptance->isPending()) {
                    throw ValidationException::withMessages([
                        'asset_acceptance' => trans('admin/users/message.error.asset_already_accepted'),
                    ]);
                }

                $acceptance->decline($sig_filename, $request->input('note'));
                event(new CheckoutDeclined($acceptance));
            });
            $responsePersisted = true;
            $acceptance->notify(new AcceptanceAssetDeclinedNotification($data));
            Log::debug('New event acceptance.');
            $return_msg = trans('admin/users/message.declined');
        }

        if ($acceptance->alert_on_response_id) {
            try {
                $recipient = User::find($acceptance->alert_on_response_id);

                if ($recipient) {
                    Log::debug('Attempting to send email acceptance.');
                    Mail::to($recipient)->send(new CheckoutAcceptanceResponseMail(
                        $acceptance,
                        $recipient,
                        $request->input('asset_acceptance') === 'accepted',
                    ));
                    Log::debug('Send email notification sucess on checkout acceptance response.');
                }
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Log::warning($e);
            }
        }

            return redirect()->to('account/accept')->with('success', $return_msg);
        } catch (\Throwable $exception) {
            if (! $responsePersisted) {
                if ($sig_filename !== '') {
                    $signaturePath = 'private_uploads/signatures/'.$sig_filename;
                    if (Storage::exists($signaturePath) && ! Storage::delete($signaturePath)) {
                        Log::critical('Unable to remove an uncommitted acceptance signature.', [
                            'acceptance_id' => $acceptance->getKey(),
                            'path' => $signaturePath,
                        ]);
                    }
                }

                $eulaPath = 'private_uploads/eula-pdfs/'.$pdf_filename;
                if (Storage::exists($eulaPath) && ! Storage::delete($eulaPath)) {
                    Log::critical('Unable to remove an uncommitted accepted-EULA PDF.', [
                        'acceptance_id' => $acceptance->getKey(),
                        'path' => $eulaPath,
                    ]);
                }
            }

            throw $exception;
        }

    }

    private function storeValidatedSignature(string $dataUri): string
    {
        $maximumEncodedBytes = (int) ceil(SafeRasterImageService::MAX_BYTES * 4 / 3) + 128;
        if (strlen($dataUri) > $maximumEncodedBytes
            || ! preg_match('/\Adata:image\/png;base64,([A-Za-z0-9+\/\r\n]+={0,2})\z/', $dataUri, $matches)
        ) {
            throw ValidationException::withMessages([
                'signature_output' => __('The signature must be a valid PNG image.'),
            ]);
        }

        $decoded = base64_decode($matches[1], true);
        if (! is_string($decoded) || $decoded === '' || strlen($decoded) > SafeRasterImageService::MAX_BYTES) {
            throw ValidationException::withMessages([
                'signature_output' => __('The signature must be a valid PNG image no larger than 5 MB.'),
            ]);
        }

        $prepared = app(SafeRasterImageService::class)
            ->prepareContents($decoded, 'signature_output');

        if ($prepared['mime'] !== 'image/png') {
            throw ValidationException::withMessages([
                'signature_output' => __('The signature must be a valid PNG image.'),
            ]);
        }

        if (! Storage::exists('private_uploads/signatures')
            && ! Storage::makeDirectory('private_uploads/signatures', 775)
        ) {
            throw new RuntimeException('Unable to create the signature storage directory.');
        }

        $filename = 'siglog-'.Str::uuid().'-'.date('Y-m-d-his').'.png';
        if (! Storage::put('private_uploads/signatures/'.$filename, $prepared['contents'])) {
            throw new RuntimeException('Unable to store the normalized signature.');
        }

        return $filename;
    }

}
