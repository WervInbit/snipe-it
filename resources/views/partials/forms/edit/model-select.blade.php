<!-- Asset Model -->
@php
    $selectedModelId = old($fieldname, $item->{$fieldname} ?? request($fieldname));
    $selectedModelNumberId = old('model_number_id', $item->model_number_id ?? ($selectedModelNumber->id ?? null) ?? request('model_number_id'));
    $selectedModelLabel = null;

    if ($selectedModelId) {
        $modelForSelect = \App\Models\AssetModel::with('modelNumbers', 'primaryModelNumber')->find($selectedModelId);

        if ($modelForSelect) {
            $initialModelNumber = null;

            if ($selectedModelNumberId) {
                $initialModelNumber = $modelForSelect->modelNumbers->firstWhere('id', (int) $selectedModelNumberId);
            }

            if (!$initialModelNumber) {
                $initialModelNumber = $modelForSelect->primaryModelNumber ?? $modelForSelect->modelNumbers->first();
            }

            if ($initialModelNumber) {
                $selectedModelNumberId = $initialModelNumber->id;
                $numberLabel = $initialModelNumber->label ?: $initialModelNumber->code;
                $selectedModelLabel = $modelForSelect->name;

                if ($numberLabel) {
                    $selectedModelLabel .= ' — '.$numberLabel;
                }

                if ($initialModelNumber->isDeprecated()) {
                    $selectedModelLabel .= ' ('.trans('general.deprecated').')';
                }
            } else {
                $selectedModelLabel = $modelForSelect->name;
                $selectedModelNumberId = null;
            }
        }
    }

    $initialCompositeValue = ($selectedModelId && $selectedModelNumberId)
        ? $selectedModelId.':'.$selectedModelNumberId
        : null;
@endphp

@php
    $showCreateModel = empty($hide_new);
@endphp

<div id="{{ $fieldname }}" class="form-group{{ $errors->has($fieldname) ? ' has-error' : '' }}">

    <label for="model_select_id" class="col-md-3 control-label">{{ $translated_name }}</label>

    <div class="col-md-7">
        <input type="hidden" name="{{ $fieldname }}" id="{{ $fieldname }}_hidden" value="{{ $selectedModelId }}">
        <select
            class="js-data-ajax"
            data-endpoint="models"
            data-placeholder="{{ trans('general.select_model') }}"
            data-hidden-input="#{{ $fieldname }}_hidden"
            data-model-number-target="#model_number_id"
            @if($selectedModelId) data-initial-model-id="{{ $selectedModelId }}" @endif
            @if($selectedModelNumberId) data-initial-model-number-id="{{ $selectedModelNumberId }}" @endif
            @if($selectedModelLabel) data-initial-label="{{ e($selectedModelLabel) }}" @endif
            name="{{ $fieldname }}_selector"
            style="width: 100%"
            id="model_select_id"
            aria-label="{{ $translated_name }}"
            {{  ((isset($field_req)) || ((isset($required) && ($required =='true')))) ?  ' required' : '' }}
            {{ (isset($multiple) && ($multiple=='true')) ? " multiple='multiple'" : '' }}
        >
            @if($initialCompositeValue && $selectedModelLabel)
                <option value="{{ $initialCompositeValue }}" selected="selected">
                    {{ $selectedModelLabel }}
                </option>
            @endif
        </select>
    </div>
    @if($showCreateModel)
        <div class="col-md-1 col-sm-1 text-left">
            @can('create', \App\Models\AssetModel::class)
                <a href='{{ route('modal.show', 'model') }}' data-toggle="modal"  data-target="#createModal" data-select='model_select_id' class="btn btn-sm btn-primary">{{ trans('button.new') }}</a>
                <span class="mac_spinner" style="padding-left: 10px; color: green; display:none; width: 30px;">
                    <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                </span>
            @endcan
        </div>
    @endif

    {!! $errors->first($fieldname, '<div class="col-md-8 col-md-offset-3"><span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}
</div>
