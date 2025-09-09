@extends('layouts/default')

@section('title')
    {{ trans('tests.edit_test_results') }}
@endsection

@section('content')
<form method="POST" action="{{ route('test-results.update', [$asset->id, $testRun->id]) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="row">
        @foreach ($testRun->results as $result)
            <div class="col-xs-12 col-sm-6" style="margin-bottom:15px;">
                <div class="panel panel-default" data-result-id="{{ $result->id }}">
                    <div class="panel-heading">
                        {{ $result->type->name }}
                        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{ $result->type->tooltip }}"></i>
                    </div>
                    <div class="panel-body">
                        <div class="btn-group btn-group-justified btn-group-lg status-buttons" role="group">
                            <a href="#" class="btn status-btn {{ $result->status === 'pass' ? 'btn-success active' : 'btn-default' }}" data-status="pass">{{ trans('tests.pass') }}</a>
                            <a href="#" class="btn status-btn {{ $result->status === 'fail' ? 'btn-danger active' : 'btn-default' }}" data-status="fail">{{ trans('tests.fail') }}</a>
                            <a href="#" class="btn status-btn {{ $result->status === 'nvt' ? 'btn-default active' : 'btn-default' }}" data-status="nvt">{{ trans('tests.nvt') }}</a>
                        </div>
                        <input type="hidden" name="status[{{ $result->id }}]" value="{{ $result->status }}">

                        <button type="button" class="btn btn-link comment-toggle" style="margin-top:10px;">
                            <i class="fas fa-comment"></i> {{ trans('general.add_note') }}
                        </button>
                        <div class="comment-field" style="margin-top:10px; @if(!$result->note)display:none;@endif">
                            <textarea name="note[{{ $result->id }}]" class="form-control" rows="2">{{ $result->note }}</textarea>
                            <input type="file" name="images[{{ $result->id }}][]" class="form-control" accept="image/*" multiple style="margin-top:5px;">
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <button type="submit" class="btn btn-primary btn-block">{{ trans('button.save') }}</button>
</form>
@endsection

@section('scripts')
<script>
    jQuery(function($){
        $('.status-buttons .status-btn').on('click', function(e){
            e.preventDefault();
            var $btn = $(this);
            var $group = $btn.closest('.status-buttons');
            var status = $btn.data('status');
            $group.find('.status-btn').removeClass('btn-success btn-danger btn-default active');
            if(status === 'pass'){
                $btn.addClass('btn-success');
            } else if(status === 'fail'){
                $btn.addClass('btn-danger');
            } else {
                $btn.addClass('btn-default');
            }
            $btn.addClass('active');
            $group.closest('.panel-body').find('input[type="hidden"]').val(status);
        });

        $('.comment-toggle').on('click', function(){
            $(this).siblings('.comment-field').toggle();
        });
    });
</script>
@endsection
