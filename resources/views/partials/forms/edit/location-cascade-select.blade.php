@php
    $selectedLocation = old($fieldname, (isset($item)) ? $item->{$fieldname} : null);
    $warehouseId = null;
    $shelfId = null;
    $binId = null;
    if ($selectedLocation) {
        $loc = \App\Models\Location::find($selectedLocation);
        if ($loc) {
            if ($loc->parent && $loc->parent->parent) {
                $warehouseId = $loc->parent->parent->id;
                $shelfId = $loc->parent->id;
                $binId = $loc->id;
            } elseif ($loc->parent) {
                $warehouseId = $loc->parent->id;
                $shelfId = $loc->id;
            } else {
                $warehouseId = $loc->id;
            }
        }
    }
@endphp
<div id="{{ $fieldname }}" class="form-group{{ $errors->has($fieldname) ? ' has-error' : '' }}"{!!  (isset($style)) ? ' style="'.e($style).'"' : ''  !!}>
    <label for="{{ $fieldname }}" class="col-md-3 control-label">{{ $translated_name }}</label>
    <div class="col-md-7">
        <input type="hidden" name="{{ $fieldname }}" id="{{ $fieldname }}_id" value="{{ $selectedLocation }}">
        <select id="{{ $fieldname }}_warehouse" class="form-control" aria-label="warehouse">
            <option value="">{{ trans('general.select_location') }}</option>
        </select>
        <select id="{{ $fieldname }}_shelf" class="form-control" aria-label="shelf" style="margin-top:5px">
            <option value="">{{ trans('general.select_location') }}</option>
        </select>
        <select id="{{ $fieldname }}_bin" class="form-control" aria-label="bin" style="margin-top:5px">
            <option value="">{{ trans('general.select_location') }}</option>
        </select>
    </div>
    <div class="col-md-1 col-sm-1 text-left">
        @can('create', \App\Models\Location::class)
            @if ((!isset($hide_new)) || ($hide_new!='true'))
            <a href='{{ route('modal.show', 'location') }}' data-toggle="modal"  data-target="#createModal" data-select='{{ $fieldname }}_bin' class="btn btn-sm btn-primary">{{ trans('button.new') }}</a>
            @endif
        @endcan
    </div>
    {!! $errors->first($fieldname, '<div class="col-md-8 col-md-offset-3"><span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}
    @if (isset($help_text))
    <div class="col-md-7 col-sm-11 col-md-offset-3">
        <p class="help-block">{{ $help_text }}</p>
    </div>
    @endif
</div>
<script nonce="{{ csrf_token() }}">
    document.addEventListener('DOMContentLoaded', function() {
        const warehouseSel = document.getElementById('{{ $fieldname }}_warehouse');
        const shelfSel = document.getElementById('{{ $fieldname }}_shelf');
        const binSel = document.getElementById('{{ $fieldname }}_bin');
        const hidden = document.getElementById('{{ $fieldname }}_id');
        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');

        function loadOptions(select, parentId, selected) {
            select.innerHTML = '<option value="">{{ trans('general.select_location') }}</option>';
            fetch(baseUrl + 'api/v1/locations/selectlist?parent_id=' + parentId)
                .then(response => response.json())
                .then(data => {
                    data.results.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.text = item.text;
                        if (selected && String(selected) === String(item.id)) {
                            opt.selected = true;
                        }
                        select.appendChild(opt);
                    });
                });
        }

        function updateHidden() {
            hidden.value = binSel.value || shelfSel.value || warehouseSel.value || '';
        }

        warehouseSel.addEventListener('change', function() {
            loadOptions(shelfSel, this.value || 0);
            binSel.innerHTML = '<option value="">{{ trans('general.select_location') }}</option>';
            updateHidden();
        });

        shelfSel.addEventListener('change', function() {
            loadOptions(binSel, this.value || 0);
            updateHidden();
        });

        binSel.addEventListener('change', updateHidden);

        loadOptions(warehouseSel, 0, {{ $warehouseId ? $warehouseId : 'null' }});
        @if($shelfId)
            loadOptions(shelfSel, {{ $warehouseId ?? 0 }}, {{ $shelfId }});
        @endif
        @if($binId)
            loadOptions(binSel, {{ $shelfId ?? 0 }}, {{ $binId }});
        @endif
        updateHidden();
    });
</script>

