<?php

namespace App\Http\Controllers;

use App\Helpers\StorageHelper;
use App\Http\Requests\UploadFileRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * This controller provide the health route  for
 * the Snipe-IT Asset Management application.
 *
 * @version   v1.0
 *
 * @return \Illuminate\Http\JsonResponse

 */
class UploadedFilesController extends Controller
{


    /**
     * Accepts a POST to upload a file to the server.
     *
     * @param  \App\Http\Requests\UploadFileRequest $request
     * @param  string                               $object_type the type of object to upload the file to
     * @param  int                                  $id          the ID of the object to store so we can check permisisons
     * @since  [v8.2.2]
     * @author [A. Gianotto <snipe@snipe.net>]
     */
    public function store(UploadFileRequest $request, $object_type, $id) : RedirectResponse
    {

        $object = $this->resolveUploadedFileParent($object_type, $id);
        if (!$object) {
            return redirect()->back()->withFragment('files')->with('error',trans('general.file_upload_status.invalid_object'));
        }

        $this->authorize('createFiles', $object);

        // If the file storage directory doesn't exist, create it
        if (! Storage::exists(self::$map_storage_path[$object_type])
            && ! Storage::makeDirectory(self::$map_storage_path[$object_type], 775)
        ) {
            return redirect()->back()->withFragment('files')
                ->with('error', trans_choice('general.file_upload_status.upload.error', 1));
        }


        if ($request->hasFile('file')) {
            $files = [];

            try {
                $logs = DB::transaction(function () use ($request, $object_type, $object, &$files) {
                    $logs = new Collection();

                    foreach ($request->file('file') as $file) {
                        $fileName = $request->handleFile(
                            self::$map_storage_path[$object_type],
                            self::$map_file_prefix[$object_type].'-'.$object->id,
                            $file
                        );
                        $files[] = $fileName;
                        $logs->push($object->logUpload($fileName, $request->get('notes')));
                    }

                    return $logs;
                });
            } catch (\Throwable $exception) {
                foreach ($files as $fileName) {
                    $path = rtrim(self::$map_storage_path[$object_type], '/\\').'/'.$fileName;
                    if (! Storage::delete($path)) {
                        Log::critical('Unable to remove an attachment after its log failed.', [
                            'object_type' => $object_type,
                            'object_id' => $object->getKey(),
                            'path' => $path,
                        ]);
                    }
                }

                Log::error('Attachment upload failed and was rolled back.', [
                    'object_type' => $object_type,
                    'object_id' => $object->getKey(),
                    'exception' => $exception,
                ]);

                return redirect()->back()->withFragment('files')
                    ->with('error', trans_choice('general.file_upload_status.upload.error', count($request->file('file'))));
            }

            return redirect()->back()->withFragment('files')
                ->with('success', trans_choice('general.file_upload_status.upload.success', $logs->count()));
        }

        // No files were submitted
        return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.nofiles'));
    }



    /**
     * Check for permissions and display the file.
     * This isn't currently used, but is here for future use.
     *
     * @param  \App\Http\Requests\UploadFileRequest $request
     * @param  string                               $object_type the type of object to upload the file to
     * @param  int                                  $id          the ID of the object to delete from so we can check permisisons
     * @param  $file_id     the ID of the file to show from the action_logs table
     * @since  [v8.2.2]
     * @author [A. Gianotto <snipe@snipe.net>]
     */
    public function show($object_type, $id, $file_id) : RedirectResponse | StreamedResponse
    {
        $object = $this->resolveUploadedFileParent($object_type, $id);
        if (!$object) {
            return redirect()->back()->withFragment('files')->with('error',trans('general.file_upload_status.invalid_object'));
        }

        $this->authorize('viewFiles', $object);

        // Check that the file being requested exists for the object
        if (! $log = $this->uploadedFileLogQuery($object_type, $object)->find($file_id))
        {
            return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.invalid_id'));
        }


        if (! Storage::exists(self::$map_storage_path[$object_type].'/'.$log->filename))
        {
            return redirect()->back()->withFragment('files')->with('error', trans('general.file_upload_status.file_not_found'));
        }

        return StorageHelper::showOrDownloadFile(
            self::$map_storage_path[$object_type].'/'.$log->filename,
            $log->filename
        );

    }

    /**
     * Delete the associated file
     *
     * @param  \App\Http\Requests\UploadFileRequest $request
     * @param  string                               $object_type the type of object to upload the file to
     * @param  int                                  $id          the ID of the object to delete from so we can check permisisons
     * @param  $file_id     the ID of the file to delete from the action_logs table
     * @since  [v8.2.2]
     * @author [A. Gianotto <snipe@snipe.net>]
     */
    public function destroy($object_type, $id, $file_id) : RedirectResponse
    {

        $object = $this->resolveUploadedFileParent($object_type, $id);
        if (!$object) {
            return redirect()->back()->withFragment('files')->with('error',trans('general.file_upload_status.invalid_object'));
        }

        $this->authorize('deleteFiles', $object);

        // Check for the file
        $log = $this->uploadedFileLogQuery($object_type, $object)->find($file_id);

        if ($log) {
            $path = rtrim(self::$map_storage_path[$object_type], '/\\').'/'.$log->filename;
            $contents = null;

            try {
                if (Storage::exists($path)) {
                    $contents = Storage::get($path);
                    if (! Storage::delete($path)) {
                        throw new RuntimeException('Unable to delete attachment from storage.');
                    }
                }

                DB::transaction(function () use ($log) {
                    if (! $log->delete()) {
                        throw new RuntimeException('Unable to delete attachment log.');
                    }
                });

                return redirect()->back()->withFragment('files')
                    ->with('success', trans_choice('general.file_upload_status.delete.success', 1));
            } catch (\Throwable $exception) {
                if (is_string($contents) && ! Storage::exists($path)) {
                    if (! Storage::put($path, $contents)) {
                        Log::critical('Unable to restore an attachment after database rollback.', [
                            'action_log_id' => $log->getKey(),
                            'path' => $path,
                        ]);
                    }
                }

                Log::error('Attachment delete failed and was rolled back.', [
                    'action_log_id' => $log->getKey(),
                    'exception' => $exception,
                ]);
            }
        }

        return redirect()->back()->withFragment('files')
            ->with('error', trans_choice('general.file_upload_status.delete.error', 1));

    }

}
