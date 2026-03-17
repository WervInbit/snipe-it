<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModelNumber;
use App\Models\ModelNumberImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModelNumberImageController extends Controller
{
    public function store(Request $request, ModelNumber $modelNumber): RedirectResponse
    {
        $this->authorize('update', $modelNumber->model);

        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $file = $request->file('image');
        $filename = $modelNumber->id.'_'.Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('model_numbers/'.$modelNumber->id, $filename, 'public');

        $sortOrder = $request->filled('sort_order')
            ? (int) $data['sort_order']
            : ((int) $modelNumber->images()->max('sort_order') + 1);

        $modelNumber->images()->create([
            'file_path' => $path,
            'caption' => $data['caption'] ?? null,
            'sort_order' => $sortOrder,
        ]);

        return back()->with('success', __('Model number image uploaded.'));
    }

    public function update(Request $request, ModelNumber $modelNumber, ModelNumberImage $modelNumberImage): RedirectResponse
    {
        if ($modelNumberImage->model_number_id !== $modelNumber->id) {
            abort(404);
        }

        $this->authorize('update', $modelNumber->model);

        $data = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($modelNumberImage->file_path);

            $file = $request->file('image');
            $filename = $modelNumber->id.'_'.Str::uuid().'.'.$file->getClientOriginalExtension();
            $modelNumberImage->file_path = $file->storeAs('model_numbers/'.$modelNumber->id, $filename, 'public');
        }

        if ($request->exists('caption')) {
            $modelNumberImage->caption = $data['caption'] ?? null;
        }

        if ($request->filled('sort_order')) {
            $modelNumberImage->sort_order = (int) $data['sort_order'];
        }

        $modelNumberImage->save();

        return back()->with('success', __('Model number image updated.'));
    }

    public function destroy(ModelNumber $modelNumber, ModelNumberImage $modelNumberImage): RedirectResponse
    {
        if ($modelNumberImage->model_number_id !== $modelNumber->id) {
            abort(404);
        }

        $this->authorize('update', $modelNumber->model);

        Storage::disk('public')->delete($modelNumberImage->file_path);
        $modelNumberImage->delete();

        return back()->with('success', __('Model number image deleted.'));
    }
}
