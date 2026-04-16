@can('view', \App\Models\User::class)
    <div id="userBulkEditToolbar" class="pull-left bulk-edit-toolbar bulk-edit-toolbar--users" style="padding-top: 10px;">

        @if (request('status')!='deleted')

            <form
                method="POST"
                action="{{ route('users/bulkedit') }}"
                accept-charset="UTF-8"
                class="form-inline bulk-edit-toolbar__form"
                id="usersBulkForm"
            >
            @csrf

            <div id="users-toolbar" class="bulk-edit-toolbar__inner">
                <label for="bulk_actions" class="sr-only">{{ trans('general.bulk_actions') }}</label>
                <select name="bulk_actions" class="form-control select2 bulk-edit-toolbar__select" aria-label="bulk_actions">

                    @can('update', \App\Models\User::class)
                        <option value="edit">{{ trans('general.bulk_edit') }}</option>
                        <option value="send_assigned">{{ trans('admin/users/general.email_assigned') }}</option>
                    @endcan

                    @can('delete', \App\Models\User::class)
                        <option value="delete">{!! trans('general.bulk_checkin_delete') !!}</option>
                        <option value="merge">{!! trans('general.merge_users') !!}</option>
                    @endcan

                    <option value="bulkpasswordreset">{{ trans('button.send_password_link') }}</option>
                    <option value="print">{{ trans('admin/users/general.print_assigned') }}</option>
                </select>
                <button class="btn btn-primary bulk-edit-toolbar__button" id="bulkUserEditButton" disabled>{{ trans('button.go') }}</button>
            </div>
            </form>
        @endif

    </div>
@endcan
