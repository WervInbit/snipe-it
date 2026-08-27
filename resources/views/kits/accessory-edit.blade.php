@extends('layouts/edit-form', [
    'updateText' => trans('admin/kits/general.update_appended_accessory'),
    'formAction' => route('kits.accessories.update', ['kit' => $kit->id, 'accessory_id' => $item->accessory_id]),
])

{{-- Page content --}}
@section('inputFields')
@include ('partials.forms.edit.accessory-select', ['translated_name' => trans('general.accessory'), 'fieldname' => 'accessory_id', 'required' => 'true'])
<div class="form-group {{ $errors->has('quantity') ? ' has-error' : '' }}">
    <label for="quantity" class="col-md-3 control-label">{{ trans('general.quantity') }}</label>
    <div class="col-md-7 required">
        <div class="col-md-2" style="padding-left:0px">
            <input class="form-control" type="text" name="quantity" id="quantity" value="{{ old('quantity', $item->quantity) }}" />
        </div>
        {!! $errors->first('quantity', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<input type="hidden" name="pivot_id" value="{{$item->id}}">
{{-- <input class="form-control" type="text" name="quantity" id="quantity" value="{{ old('quantity', $item->quantity) }}" /> --}}

@stop
