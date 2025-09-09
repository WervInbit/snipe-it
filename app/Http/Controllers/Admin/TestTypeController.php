<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TestType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', TestType::class);
        $testTypes = TestType::orderBy('name')->get();
        return view('settings.testtypes', compact('testTypes'));
    }

    public function update(Request $request, TestType $testtype): RedirectResponse
    {
        $this->authorize('update', $testtype);
        $validated = $request->validate([
            'tooltip' => 'nullable|string',
        ]);
        $testtype->update($validated);

        return redirect()->route('settings.testtypes.index')
            ->with('success', trans('admin/settings/message.update.success'));
    }
}
