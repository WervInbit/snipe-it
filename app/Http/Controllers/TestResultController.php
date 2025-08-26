<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;

class TestResultController extends Controller
{
    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pass', 'fail', 'pending'])],
            'notes' => 'nullable|string',
        ]);

        $result = new TestResult();
        $result->status = $validated['status'];
        $result->note = $validated['notes'] ?? null;
        $result->save();

        return redirect()->route('test-runs.index', ['asset' => $asset->id])
            ->with('success', trans('general.test_result_saved'));
    }
}
