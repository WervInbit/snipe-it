<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Helpers\StorageHelper;
use App\Models\Actionlog;
use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class UploadedFilesTransformer
{
    public function transformFiles(Collection $files, $total, ?Model $parent = null)
    {
        $array = [];
        foreach ($files as $file) {
            $array[] = self::transformFile($file, $parent);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }


    public function transformFile(Actionlog $file, ?Model $parent = null)
    {
        $parent ??= $file->item;

        $array = [
            'id' => (int) $file->id,
            'icon' => Helper::filetype_icon($file->filename),
            'name' => e($file->filename),
            'item' => ($file->item_type) ? [
                'id' => (int) $file->item_id,
                'type' => str_plural(strtolower(class_basename($file->item_type))),
            ] : null,
            'filename' => e($file->filename),
            'filetype' => StorageHelper::getFiletype($file->uploads_file_path()),
            'mediatype' => StorageHelper::getMediaType($file->uploads_file_path()),
            'url' => $file->uploads_file_url(),
            'note' =>  ($file->note) ? e($file->note) : null,
            'created_by' => ($file->adminuser) ? [
                'id' => (int) $file->adminuser->id,
                'name'=> e($file->adminuser->present()->fullName),
            ] : null,
            'created_at' => Helper::getFormattedDateObject($file->created_at, 'datetime'),
            'deleted_at' => Helper::getFormattedDateObject($file->deleted_at, 'datetime'),
            'inlineable' => StorageHelper::allowSafeInline($file->uploads_file_path()),
            'exists_on_disk' => (Storage::exists($file->uploads_file_path()) ? true : false),
        ];

        $permissions_array['available_actions'] = [
            'delete' => ($parent
                && ! ($parent instanceof Maintenance)
                && Gate::allows('deleteFiles', $parent)
                && ($file->deleted_at == '')),
        ];

        $array += $permissions_array;
        return $array;
    }


}
