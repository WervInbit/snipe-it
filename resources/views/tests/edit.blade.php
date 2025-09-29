@extends('layouts/default')

@section('title')
    {{ trans('tests.edit_test_results') }}
@endsection

@section('content')
<form class="container" method="POST" action="{{ route('test-results.update', [$asset->id, $testRun->id]) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="row">
        @foreach ($testRun->results as $result)
            <div class="col-xs-12 col-sm-6" style="margin-bottom:15px;">
                <div class="panel panel-default" data-result-id="{{ $result->id }}">
                    <div class="panel-heading">
                        @php
                            $definition = $result->attributeDefinition;
                            $label = $definition?->label ?? optional($result->type)->name;
                            $tooltip = $definition ? ($definition->unit ? __('Unit: :unit', ['unit' => $definition->unit]) : null) : optional($result->type)->tooltip;
                            $expectedDisplay = $result->expected_value;
                            if ($definition && $definition->datatype === \App\Models\AttributeDefinition::DATATYPE_BOOL && $expectedDisplay !== null) {
                                $expectedDisplay = $expectedDisplay === '1' ? __('Yes') : __('No');
                            }
                        @endphp
                        {{ $label }}
                        @if($tooltip)
                            <i class="fas fa-info-circle" data-toggle="tooltip" title="{{ $tooltip }}"></i>
                        @endif
                        @if ($expectedDisplay !== null)
                            <span class="pull-right text-muted">{{ __('Expected: :value', ['value' => $expectedDisplay]) }}</span>
                        @endif
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
                        <button type="button" class="btn btn-link photo-button" style="margin-top:10px;">
                            <i class="fas fa-camera"></i> {{ trans('general.attach_photo') }}
                        </button>
                        <input type="file" name="photo[{{ $result->id }}]" class="photo-input" accept="image/*" capture="camera" style="display:none;">
                        <div class="comment-field" style="margin-top:10px; @if(!$result->note)display:none;@endif">
                            <textarea name="note[{{ $result->id }}]" class="form-control" rows="2">{{ $result->note }}</textarea>
                        </div>
                        <div class="photo-preview" style="margin-top:10px;">
                            @if($result->photo_path)
                                <img src="/{{ $result->photo_path }}" alt="photo" style="max-width:100%;height:auto;">
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <button type="submit" class="btn btn-primary btn-lg btn-block">{{ trans('button.save') }}</button>
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

        $('.photo-button').on('click', function(){
            $(this).siblings('input.photo-input').trigger('click');
        });

        $('.photo-input').on('change', function(){
            var input = this;
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e){
                    $(input).siblings('.photo-preview').html('<img src="'+e.target.result+'" style="max-width:100%;height:auto;"/>');
                };
                reader.readAsDataURL(input.files[0]);
            }
        });
    });
</script>
@endsection
