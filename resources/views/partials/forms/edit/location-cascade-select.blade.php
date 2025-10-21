@php
    $selectedLocation = old($fieldname, isset($item) ? $item->{$fieldname} : null);
    $preselected = collect($selected ?? [])
        ->filter()
        ->when($selectedLocation && (! isset($selected) || empty($selected)), function ($collection) use ($selectedLocation) {
            return $collection->push($selectedLocation);
        });
@endphp

<div id="{{ $fieldname }}" class="form-group{{ $errors->has($fieldname) ? ' has-error' : '' }}"{!!  isset($style) ? ' style="'.e($style).'"' : ''  !!}>
    <label for="{{ $fieldname }}" class="col-md-3 control-label">{{ $translated_name }}</label>

    <div class="col-md-7">
        <select
            class="js-data-ajax"
            data-endpoint="locations"
            data-placeholder="{{ trans('general.select_location') }}"
            name="{{ $fieldname }}"
            id="{{ $fieldname }}_location_select"
            style="width: 100%"
            aria-label="{{ $fieldname }}"
            {!! ((isset($item)) && (Helper::checkIfRequired($item, $fieldname))) ? ' required' : '' !!}
        >
            <option value="">{{ trans('general.select_location') }}</option>
            @foreach ($preselected as $locationId)
                @php $location = \App\Models\Location::find($locationId); @endphp
                @if ($location)
                    <option value="{{ $location->id }}" selected="selected" role="option" aria-selected="true">
                        {{ $location->present()->fullName() }}
                    </option>
                @endif
            @endforeach
            @if ($preselected->isEmpty() && $selectedLocation)
                @php $location = \App\Models\Location::find($selectedLocation); @endphp
                @if ($location)
                    <option value="{{ $location->id }}" selected="selected" role="option" aria-selected="true">
                        {{ $location->present()->fullName() }}
                    </option>
                @endif
            @endif
        </select>
    </div>

    <div class="col-md-1 col-sm-1 text-left">
        @can('create', \App\Models\Location::class)
            @if (!isset($hide_new) || $hide_new != 'true')
                <a href="{{ route('modal.show', 'location') }}"
                   data-toggle="modal"
                   data-target="#createModal"
                   data-select="{{ $fieldname }}_location_select"
                   class="btn btn-sm btn-primary">
                    {{ trans('button.new') }}
                </a>
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

