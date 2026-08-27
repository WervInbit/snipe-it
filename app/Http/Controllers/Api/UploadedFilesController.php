<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadFileRequest;
use App\Http\Transformers\UploadedFilesTransformer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;


class UploadedFilesController extends Controller
{


    /**
     * List files for an object
     *
     * @param  \App\Http\Requests\UploadFileRequest $request
     * @param  string                               $object_type the type of object to upload the file to
     * @param  int                                  $id          the ID of the object to list files for
     * @since  [v8.1.17]
     * @author [A. Gianotto <snipe@snipe.net>]
     */
    public function index(Request $request, $object_type, $id) : JsonResponse | array
    {

        $object = $this->resolveUploadedFileParent($object_type, $id);
        if (!$object) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('general.file_upload_status.invalid_object')));
        }

        $this->authorize('viewFiles', $object);

        // Columns allowed for sorting
        $allowed_columns =
            [
                'id',
                'filename',
                'action_type',
                'action_date',
                'note',
                'created_at',
            ];


        $uploads = $this->uploadedFileLogQuery($object_type, $object)
            ->select('action_logs.*')
            ->with('adminuser');

        $limit = app('api_limit_value');
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowed_columns) ? $request->input('sort') : 'created_at';

        // Text search on action_logs fields
        // We could use the normal Actionlogs text scope, but it's a very heavy query since it's searching across all relations
        // and we generally won't need that here
        if ($request->filled('search')) {

            $uploads->where(
                function ($query) use ($request) {
                    $query->where('filename', 'LIKE', '%' . $request->input('search') . '%')
                        ->orWhere('note', 'LIKE', '%' . $request->input('search') . '%');
                }
            );
        }

        $total = $uploads->count();
        $offset = $this->resolveOffset($request, $total, $limit);
        $uploads = $uploads->skip($offset)->take($limit)->orderBy($sort, $order)->get();

        return (new UploadedFilesTransformer())->transformFiles($uploads, $total, $object);
    }


    /**
     * Accepts a POST to upload a file to the server.
     *
     * @param  \App\Http\Requests\UploadFileRequest $request
     * @param  string                               $object_type the type of object to upload the file to
     * @param  int                                  $id          the ID of the object to store so we can check permisisons
     * @since  [v8.1.17]
     * @author [A. Gianotto <snipe@snipe.net>]
     */
    public function store(UploadFileRequest $request, $object_type, $id) : JsonResponse
    {

        $object = $this->resolveUploadedFileParent($object_type, $id);
        if (!$object) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('general.file_upload_status.invalid_object')));
        }

        $this->authorize('createFiles', $object);

        // If the file storage directory doesn't exist, create it
        if (! Storage::exists(self::$map_storage_path[$object_type])
            && ! Storage::makeDirectory(self::$map_storage_path[$object_type], 775)
        ) {
            return response()->json(Helper::formatStandardApiResponse(
                'error',
                null,
                trans_choice('general.file_upload_status.upload.error', 1)
            ), 500);
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
                        Log::critical('Unable to remove an API attachment after its log failed.', [
                            'object_type' => $object_type,
                            'object_id' => $object->getKey(),
                            'path' => $path,
                        ]);
                    }
                }

                Log::error('API attachment upload failed and was rolled back.', [
                    'object_type' => $object_type,
                    'object_id' => $object->getKey(),
                    'exception' => $exception,
                ]);

                return response()->json(Helper::formatStandardApiResponse(
                    'error',
                    null,
                    trans_choice('general.file_upload_status.upload.error', count($request->file('file')))
                ), 500);
            }

            return response()->json(Helper::formatStandardApiResponse(
                'success',
                (new UploadedFilesTransformer())->transformFiles($logs, $logs->count(), $object),
                trans_choice('general.file_upload_status.upload.success', $logs->count())
            ));
        }

        // No files were submitted
        return response()->json(Helper::formatStandardApiResponse('error', null, trans('general.file_upload_status.nofiles')));
    }



    /**
     * Check for permissions and display the file.
     *
     * @param  \App\Http\Requests\UploadFileRequest $request
     * @param  string                               $object_type the type of object to upload the file to
     * @param  int                                  $id          the ID of the object to delete from so we can check permisisons
     * @param  $file_id     the ID of the file to delete from the action_logs table
     * @since  [v8.1.17]
     * @author [A. Gianotto <snipe@snipe.net>]
     */
    public function show($object_type, $id, $file_id) : JsonResponse | StreamedResponse
    {
        $object = $this->resolveUploadedFileParent($object_type, $id);
        if (!$object) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('general.file_upload_status.invalid_object')));
        }

        $this->authorize('viewFiles', $object);

        // Check that the file being requested exists for the object
        if (! $log = $this->uploadedFileLogQuery($object_type, $object)->find($file_id)) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('general.file_upload_status.invalid_id')), 200);
        }


        if (! Storage::exists(self::$map_storage_path[$object_type].'/'.$log->filename)) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('general.file_upload_status.file_not_found'), 200));
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
     * @since  [v8.1.17]
     * @author [A. Gianotto <snipe@snipe.net>]
     */
    public function destroy($object_type, $id, $file_id) : JsonResponse
    {

        $object = $this->resolveUploadedFileParent($object_type, $id);
        if (!$object) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('general.file_upload_status.invalid_object')));
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

                return response()->json(Helper::formatStandardApiResponse(
                    'success',
                    null,
                    trans_choice('general.file_upload_status.delete.success', 1)
                ), 200);
            } catch (\Throwable $exception) {
                if (is_string($contents) && ! Storage::exists($path)) {
                    if (! Storage::put($path, $contents)) {
                        Log::critical('Unable to restore an API attachment after database rollback.', [
                            'action_log_id' => $log->getKey(),
                            'path' => $path,
                        ]);
                    }
                }

                Log::error('API attachment delete failed and was rolled back.', [
                    'action_log_id' => $log->getKey(),
                    'exception' => $exception,
                ]);
            }
        }

        // The file doesn't seem to really exist, so report an error
        return response()->json(Helper::formatStandardApiResponse('error', null, trans_choice('general.file_upload_status.delete.error', 1)), 500);

    }
}
