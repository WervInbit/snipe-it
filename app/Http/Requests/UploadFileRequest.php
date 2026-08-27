<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use App\Http\Traits\ConvertsBase64ToFiles;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UploadFileRequest extends Request
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
        $max_file_size = Helper::file_upload_max_size();

        return [
            'file' => 'nullable|array',
            'file.*' => 'required|mimes:'.config('filesystems.allowed_upload_extensions_for_validator').'|max:'.$max_file_size,
        ];
    }

    /**
     * Sanitizes (if needed) and Saves a file to the appropriate location
     * Returns the 'short' (storage-relative) filename
     */
    public function handleFile(string $dirname, string $name_prefix, $file): string
    {
        $clientExtension = $file->getClientOriginalExtension();
        $serverExtension = $file->guessExtension();
        if (! is_string($serverExtension) || $serverExtension === '') {
            throw new RuntimeException('Unable to determine a safe uploaded-file extension.');
        }

        $originalStem = basename($file->getClientOriginalName(), '.'.$clientExtension);
        $safeStem = str_slug($originalStem) ?: 'file';
        $file_name = $name_prefix.'-'.str_random(8).'-'.$safeStem.'.'.$serverExtension;

        // Check for SVG and sanitize it
        if ($file->getMimeType() === 'image/svg+xml') {
            $uploaded_file = $this->handleSVG($file);
        } else {
            $uploaded_file = @file_get_contents($file->getRealPath());
        }

        if (! is_string($uploaded_file)) {
            throw new RuntimeException('Unable to read or safely sanitize the uploaded file.');
        }

        $path = rtrim($dirname, '/\\').'/'.$file_name;
        if (! Storage::put($path, $uploaded_file)) {
            throw new RuntimeException('Unable to store the uploaded file.');
        }

        return $file_name;
    }

    public function handleSVG($file)
    {
        $sanitizer = new Sanitizer();
        $dirtySVG = @file_get_contents($file->getRealPath());
        if (! is_string($dirtySVG)) {
            throw new RuntimeException('Unable to read the uploaded SVG.');
        }

        $cleanSVG = $sanitizer->sanitize($dirtySVG);

        if (! is_string($cleanSVG) || trim($cleanSVG) === '') {
            throw new RuntimeException('Unable to safely sanitize the uploaded SVG.');
        }

        return $cleanSVG;
    }


    /**
     * Get the validation error messages that apply to the request, but
     * replace the attribute name with the name of the file that was attempted and failed
     * to make it clearer to the user which file is the bad one.
     *
     * @return array
     */
    public function attributes(): array
    {
        $attributes = [];

        if (($this->file) && (is_array($this->file))) {

            for ($i = 0; $i < count($this->file); $i++) {

                try {

                    if ($this->file[$i]) {
                        $attributes['file.'.$i] = $this->file[$i]->getClientOriginalName();
                    }

                } catch (\Exception $e) {
                    $attributes['file.'.$i] = 'Invalid file';
                }

            }
        }

        return $attributes;

    }
}
