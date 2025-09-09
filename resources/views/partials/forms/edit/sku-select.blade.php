<!-- Asset SKU -->
<div id="{{ $fieldname }}" class="form-group{{ $errors->has($fieldname) ? ' has-error' : '' }}">
    <label for="{{ $fieldname }}" class="col-md-3 control-label">{{ $translated_name }}</label>
    <div class="col-md-7">
        <select class="js-data-ajax" data-endpoint="skus" data-placeholder="{{ trans('general.select_sku') }}" name="{{ $fieldname }}" style="width: 100%" id="sku_select_id" aria-label="{{ $fieldname }}">
            @isset($selected)
                <option value="{{ $selected }}" selected="selected">{{ $selected_text ?? $selected }}</option>
            @endisset
        </select>
    </div>
    {!! $errors->first($fieldname, '<div class="col-md-8 col-md-offset-3"><span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}
</div>
