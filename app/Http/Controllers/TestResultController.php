<?php

namespace App\Http\Controllers;

use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;

class TestResultController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pass', 'fail', 'pending'])],
            'note' => 'nullable|string',
        ]);

        $result = new TestResult();
        $result->status = $validated['status'];
        $result->note = $validated['note'] ?? null;
        $result->save();

        return redirect()->back()->with('success', trans('general.test_result_saved'));
    }
}
