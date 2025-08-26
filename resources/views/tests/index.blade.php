<div class="mb-3 text-right">
    <form method="POST" action="{{ route('test-runs.store', $asset->id) }}">
        @csrf
        <button type="submit" class="btn btn-primary">{{ trans('tests.start_new_run') }}</button>
    </form>
</div>

<table class="table table-striped">
    <thead>
        <tr>
            <th>{{ trans('general.date') }}</th>
            <th>{{ trans('table.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($runs as $run)
            <tr>
                <td>{{ $run->created_at }}</td>
                <td>
                    <a href="{{ route('test-results.edit', [$asset->id, $run->id]) }}" class="btn btn-default btn-sm">{{ trans('button.edit') }}</a>
                    <form method="POST" action="{{ route('test-runs.destroy', [$asset->id, $run->id]) }}" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit">{{ trans('button.delete') }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
