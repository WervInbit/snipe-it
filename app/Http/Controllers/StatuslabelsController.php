<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Statuslabel;
use App\Models\Group;
use App\Models\StatusLabelAccessRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use \Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

/**
 * This controller handles all actions related to Status Labels for
 * the Snipe-IT Asset Management application.
 *
 * @version    v1.0
 */
class StatuslabelsController extends Controller
{
    /**
     * Show a list of all the statuslabels.
     */
    public function index() : View
    {
        $this->authorize('view', Statuslabel::class);
        return view('statuslabels.index');
    }

    public function show(Statuslabel $statuslabel) : View | RedirectResponse
    {
        $this->authorize('view', Statuslabel::class);
        return view('statuslabels.view')->with('statuslabel', $statuslabel);
    }

    /**
     * Statuslabel create.
     *
     */
    public function create() : View
    {
        // Show the page
        $this->authorize('create', Statuslabel::class);

        return view('statuslabels/edit')
            ->with('item', new Statuslabel)
            ->with('statuslabel_types', Helper::statusTypeList())
            ->with('lifecycle_stages', Statuslabel::lifecycleStageOptions())
            ->with('accessGroups', Group::query()->orderBy('name')->get())
            ->with('userAccessRules', collect());
    }

    /**
     * Statuslabel create form processing.
     *
     * @param Request $request
     */
    public function store(Request $request) : RedirectResponse
    {
        $this->authorize('create', Statuslabel::class);
        // create a new model instance
        $statusLabel = new Statuslabel();

        if ($request->missing('statuslabel_types')) {
            return redirect()->back()->withInput()->withErrors(['statuslabel_types' => trans('validation.statuslabel_type')]);
        }

        $request->validate([
            'lifecycle_stage' => ['nullable', Rule::in(array_filter(array_keys(Statuslabel::lifecycleStageOptions())))],
            ...$this->accessValidationRules(),
        ]);

        $statusType = Statuslabel::getStatuslabelTypesForDB($request->input('statuslabel_types'));

        // Save the Statuslabel data
        $statusLabel->name = $request->input('name');
        $statusLabel->created_by = auth()->id();
        $statusLabel->notes = $request->input('notes');
        $statusLabel->deployable = $statusType['deployable'];
        $statusLabel->pending = $statusType['pending'];
        $statusLabel->archived = $statusType['archived'];
        $statusLabel->color = $request->input('color');
        $statusLabel->show_in_nav = $request->input('show_in_nav', 0);
        $statusLabel->default_label = $request->input('default_label', 0);
        $statusLabel->lifecycle_stage = $request->input('lifecycle_stage') ?: null;
        $statusLabel->requires_note = $request->boolean('requires_note');

        if ($statusLabel->save()) {
            $this->syncAccessRules($request, $statusLabel);
            // Redirect to the new Statuslabel  page
            return redirect()->route('statuslabels.index')->with('success', trans('admin/statuslabels/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($statusLabel->getErrors());
    }

    /**
     * Statuslabel update.
     *
     * @param  int $statuslabelId
     */
    public function edit(Statuslabel $statuslabel) : View | RedirectResponse
    {
        $this->authorize('update', Statuslabel::class);

        $statuslabel_types = ['' => trans('admin/hardware/form.select_statustype')] + ['undeployable' => trans('admin/hardware/general.undeployable')] + ['pending' => trans('admin/hardware/general.pending')] + ['archived' => trans('admin/hardware/general.archived')] + ['deployable' => trans('admin/hardware/general.deployable')];

        return view('statuslabels/edit', compact('statuslabel_types'))
            ->with('item', $statuslabel)
            ->with('use_statuslabel_type', $statuslabel)
            ->with('lifecycle_stages', Statuslabel::lifecycleStageOptions())
            ->with('accessGroups', Group::query()->orderBy('name')->get())
            ->with('userAccessRules', $statuslabel->accessRules()
                ->where('subject_type', StatusLabelAccessRule::SUBJECT_USER)
                ->with('user')
                ->orderBy('subject_id')
                ->get());
    }

    /**
     * Statuslabel update form processing page.
     *
     * @param  int $statuslabelId
     */
    public function update(Request $request, Statuslabel $statuslabel) : RedirectResponse
    {
        $this->authorize('update', Statuslabel::class);

        if (! $request->filled('statuslabel_types')) {
            return redirect()->back()->withInput()->withErrors(['statuslabel_types' => trans('validation.statuslabel_type')]);
        }

        $request->validate([
            'lifecycle_stage' => ['nullable', Rule::in(array_filter(array_keys(Statuslabel::lifecycleStageOptions())))],
            ...$this->accessValidationRules(),
        ]);

        // Update the Statuslabel data
        $statustype = Statuslabel::getStatuslabelTypesForDB($request->input('statuslabel_types'));
        $statuslabel->name = $request->input('name');
        $statuslabel->notes = $request->input('notes');
        $statuslabel->deployable = $statustype['deployable'];
        $statuslabel->pending = $statustype['pending'];
        $statuslabel->archived = $statustype['archived'];
        $statuslabel->color = $request->input('color');
        $statuslabel->show_in_nav = $request->input('show_in_nav', 0);
        $statuslabel->default_label = $request->input('default_label', 0);
        $statuslabel->lifecycle_stage = $request->input('lifecycle_stage') ?: null;
        $statuslabel->requires_note = $request->boolean('requires_note');

        // Was the asset created?
        if ($statuslabel->save()) {
            $this->syncAccessRules($request, $statuslabel);
            // Redirect to the saved Statuslabel page
            return redirect()->route('statuslabels.index')->with('success', trans('admin/statuslabels/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($statuslabel->getErrors());
    }

    /**
     * Delete the given Statuslabel.
     *
     * @param  int $statuslabelId
     */
    public function destroy($statuslabelId) : RedirectResponse
    {
        $this->authorize('delete', Statuslabel::class);
        // Check if the Statuslabel exists
        if (is_null($statuslabel = Statuslabel::find($statuslabelId))) {
            return redirect()->route('statuslabels.index')->with('error', trans('admin/statuslabels/message.not_found'));
        }

        // Check that there are no assets associated
        if ($statuslabel->assets()->count() == 0) {
            $statuslabel->delete();

            return redirect()->route('statuslabels.index')->with('success', trans('admin/statuslabels/message.delete.success'));
        }

        return redirect()->route('statuslabels.index')->with('error', trans('admin/statuslabels/message.assoc_assets'));
    }

    /**
     * @return array<string, mixed>
     */
    private function accessValidationRules(): array
    {
        return [
            'status_access_groups' => ['nullable', 'array'],
            'status_access_groups.*.view' => ['required', Rule::in([-1, 0, 1, '-1', '0', '1'])],
            'status_access_groups.*.select' => ['required', Rule::in([-1, 0, 1, '-1', '0', '1'])],
            'status_access_users' => ['nullable', 'array'],
            'status_access_users.*.view' => ['required', Rule::in([-1, 0, 1, '-1', '0', '1'])],
            'status_access_users.*.select' => ['required', Rule::in([-1, 0, 1, '-1', '0', '1'])],
            'new_status_access_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'new_status_access_user_view' => ['nullable', Rule::in([-1, 0, 1, '-1', '0', '1'])],
            'new_status_access_user_select' => ['nullable', Rule::in([-1, 0, 1, '-1', '0', '1'])],
        ];
    }

    private function syncAccessRules(Request $request, Statuslabel $statuslabel): void
    {
        $groupValues = (array) $request->input('status_access_groups', []);
        $validGroupIds = Group::query()->whereIn('id', array_keys($groupValues))->pluck('id')->all();

        $statuslabel->accessRules()
            ->where('subject_type', StatusLabelAccessRule::SUBJECT_GROUP)
            ->delete();

        foreach ($validGroupIds as $groupId) {
            $this->saveAccessRule(
                $statuslabel,
                StatusLabelAccessRule::SUBJECT_GROUP,
                (int) $groupId,
                $groupValues[$groupId] ?? []
            );
        }

        $userValues = (array) $request->input('status_access_users', []);
        foreach ($userValues as $userId => $values) {
            $rule = $statuslabel->accessRules()
                ->where('subject_type', StatusLabelAccessRule::SUBJECT_USER)
                ->where('subject_id', (int) $userId)
                ->first();
            $view = (int) ($values['view'] ?? 0);
            $select = (int) ($values['select'] ?? 0);

            if ($view === 0 && $select === 0) {
                $rule?->delete();
                continue;
            }

            StatusLabelAccessRule::query()->updateOrCreate(
                [
                    'status_label_id' => $statuslabel->id,
                    'subject_type' => StatusLabelAccessRule::SUBJECT_USER,
                    'subject_id' => (int) $userId,
                ],
                ['view_value' => $view, 'select_value' => $select]
            );
        }

        if ($request->filled('new_status_access_user_id')) {
            $this->saveAccessRule(
                $statuslabel,
                StatusLabelAccessRule::SUBJECT_USER,
                (int) $request->input('new_status_access_user_id'),
                [
                    'view' => $request->input('new_status_access_user_view', 0),
                    'select' => $request->input('new_status_access_user_select', 0),
                ]
            );
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function saveAccessRule(
        Statuslabel $statuslabel,
        string $subjectType,
        int $subjectId,
        array $values
    ): void {
        $view = (int) ($values['view'] ?? 0);
        $select = (int) ($values['select'] ?? 0);
        if ($view === 0 && $select === 0) {
            return;
        }

        StatusLabelAccessRule::query()->updateOrCreate(
            [
                'status_label_id' => $statuslabel->id,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
            ],
            ['view_value' => $view, 'select_value' => $select]
        );
    }
}
