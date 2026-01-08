<!-- Serial -->
@php
    $serialIndex = $serial_index ?? null;
    $showDuplicateWarning = $show_duplicate_warning ?? false;
@endphp
<div class="form-group {{ $errors->has('serial') ? ' has-error' : '' }} js-serial-entry" @if($serialIndex) data-serial-index="{{ $serialIndex }}" @endif>
    <label for="{{ $fieldname }}" class="col-md-3 control-label">{{ trans('admin/hardware/form.serial') }} </label>
    <div class="col-md-7 col-sm-12">
        <input class="form-control js-serial-input" type="text" name="{{ $fieldname }}" id="{{ $fieldname }}" value="{{ old((isset($old_val_name) ? $old_val_name : $fieldname), $item->serial) }}"{{  (Helper::checkIfRequired($item, 'serial')) ? ' required' : '' }} maxlength="191" @if($serialIndex) data-serial-index="{{ $serialIndex }}" @endif />
        {!! $errors->first('serial', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
    @if ($showDuplicateWarning && $serialIndex)
        <div class="col-md-7 col-sm-12 col-md-offset-3">
            <div class="alert alert-warning serial-duplicate-warning hidden js-serial-warning" role="alert">
                <strong>Serial already in use.</strong>
                <div class="serial-duplicate-details js-serial-warning-details"></div>
                <label class="serial-duplicate-override">
                    <input type="checkbox" name="allow_duplicate_serials[{{ $serialIndex }}]" class="js-serial-allow" value="1" {{ old('allow_duplicate_serials.' . $serialIndex) ? 'checked' : '' }} disabled>
                    Allow duplicate serial
                </label>
            </div>
        </div>
    @endif
</div>
