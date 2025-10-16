@php($searchText = strtolower($definition->label.' '.$definition->key))
<li class="list-group-item selected-attribute-item" data-attribute-id="{{ $definition->id }}" data-search-text="{{ $searchText }}">
    <div class="selected-attribute-item__body">
        <div class="selected-attribute-item__info">
            <strong>{{ $definition->label }}</strong>
            <span class="text-muted small">({{ $definition->key }})</span>
            @if($definition->isDeprecated())
                <span class="label label-warning" style="margin-left:6px;">{{ __('Deprecated') }}</span>
            @endif
            @if($definition->isHidden())
                <span class="label label-default" style="margin-left:6px;">{{ __('Hidden') }}</span>
            @endif
        </div>
        <div class="selected-attribute-item__actions btn-group btn-group-xs" role="group">
            <button type="button" class="btn btn-default js-select-assigned" title="{{ __('Edit Attribute') }}">
                <i class="fa fa-pencil"></i>
            </button>
            <button type="button" class="btn btn-default js-move-up" title="{{ __('Move Up') }}">
                <i class="fa fa-arrow-up"></i>
            </button>
            <button type="button" class="btn btn-default js-move-down" title="{{ __('Move Down') }}">
                <i class="fa fa-arrow-down"></i>
            </button>
            <button type="button" class="btn btn-danger js-remove-assigned" title="{{ __('Remove Attribute') }}">
                <i class="fa fa-times"></i>
            </button>
        </div>
    </div>
    <input type="hidden" name="attribute_order[]" value="{{ $definition->id }}" class="js-attribute-order-input">
</li>
