<?php

namespace App\Http\Requests;

use App\Models\SnipeModel;
use App\Services\SafeRasterImageService;
use enshrined\svgSanitize\Sanitizer;
use Intervention\Image\Facades\Image;
use App\Http\Traits\ConvertsBase64ToFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ImageUploadRequest extends Request
{
    use ConvertsBase64ToFiles;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $safeImage = ['nullable', 'file', 'mimes:png,gif,jpg,jpeg,svg', 'max:5120'];

        return [
            'image' => $safeImage,
            'image_source' => $safeImage,
            'avatar' => $safeImage,
            'favicon' => $safeImage,
            'qr_logo' => $safeImage,
            'logo' => $safeImage,
            'email_logo' => $safeImage,
            'label_logo' => $safeImage,
            'acceptance_pdf_logo' => $safeImage,
            'default_avatar' => $safeImage,
        ];
    }

    public function response(array $errors)
    {
        return $this->redirector->back()->withInput()->withErrors($errors, $this->errorBag);
    }
    
    /** 
     * Fields that should be traited from base64 to files
     */
    protected function base64FileKeys(): array
    {
        /**
         * image_source is here just legacy reasons. Api\AssetController
         * had it once to allow encoded image uploads.
        */ 
        return [
            'image' => 'auto',
            'image_source' => 'auto'
        ];
    }

    /**
     * Handle and store any images attached to request
     * @param SnipeModel $item Item the image is associated with
     * @param string $path  location for uploaded images, defaults to uploads/plural of item type.
     * @return SnipeModel        Target asset is being checked out to.
     */
    public function handleImages($item, $w = 600, $form_fieldname = 'image', $path = null, $db_fieldname = 'image')
    {

        $type = class_basename(get_class($item));

        if (is_null($path)) {

            $path = strtolower(str_plural($type));

            if ($type == 'AssetModel') {
                $path = 'models';
            }

            if ($type == 'user') {
                $path = 'avatars';
            }

        }


        if ($this->offsetGet($form_fieldname) instanceof UploadedFile) {
           $image = $this->offsetGet($form_fieldname);
        } elseif ($this->hasFile($form_fieldname)) {
            $image = $this->file($form_fieldname);
        }

        if (isset($image)) {
            if (! Storage::disk('public')->exists($path)
                && ! Storage::disk('public')->makeDirectory($path)
            ) {
                throw new RuntimeException('Unable to create the image storage directory.');
            }

            if (! $image->isValid() || $image->getSize() > SafeRasterImageService::MAX_BYTES) {
                throw ValidationException::withMessages([
                    $form_fieldname => __('The image could not be uploaded or is larger than 5 MB.'),
                ]);
            }

            if ($image->getMimeType() === 'image/svg+xml') {
                $dirtySvg = @file_get_contents($image->getRealPath());
                $cleanSvg = is_string($dirtySvg)
                    ? (new Sanitizer())->sanitize($dirtySvg)
                    : false;

                if (! is_string($cleanSvg) || trim($cleanSvg) === '') {
                    throw ValidationException::withMessages([
                        $form_fieldname => __('The SVG image could not be sanitized safely.'),
                    ]);
                }

                $contents = $cleanSvg;
                $extension = 'svg';
            } else {
                $prepared = app(SafeRasterImageService::class)->prepare($image, $form_fieldname);
                $resized = Image::make($prepared['contents'])->resize(null, $w, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $resizedContents = (string) $resized->encode($prepared['extension']);
                $normalized = app(SafeRasterImageService::class)
                    ->prepareContents($resizedContents, $form_fieldname);
                $contents = $normalized['contents'];
                $extension = $normalized['extension'];
            }

            $itemId = $item->getKey() ?: 'new';
            $fileName = $type.'-'.$form_fieldname.'-'.$itemId.'-'.str_random(10).'.'.$extension;
            $storagePath = trim($path, '/\\');
            $filePath = $storagePath === '' ? $fileName : $storagePath.'/'.$fileName;

            if (! Storage::disk('public')->put($filePath, $contents)) {
                throw new RuntimeException('Unable to store the normalized image.');
            }

            // Keep the previous file until the caller persists the new pointer.
            // The helper cannot safely know whether a later model save succeeds.
            $item->{$db_fieldname} = $fileName;



        // If the user isn't uploading anything new but wants to delete their old image, do so
        } elseif ($this->input('image_delete') == '1') {
            $item->{$db_fieldname} = null;
        }

        return $item;
    }

    public function deleteExistingImage($item, $path = null, $db_fieldname = 'image') {

        $item->{$db_fieldname} = null;

        return $item;
    }
    
}
