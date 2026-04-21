@php
    $isInstalled = $component->status === \App\Models\ComponentInstance::STATUS_INSTALLED;
    $isInTransfer = $component->status === \App\Models\ComponentInstance::STATUS_IN_TRANSFER;
    $needsVerification = $component->status === \App\Models\ComponentInstance::STATUS_NEEDS_VERIFICATION;
    $isDestructionPending = $component->status === \App\Models\ComponentInstance::STATUS_DESTRUCTION_PENDING;
    $isDestroyed = $component->status === \App\Models\ComponentInstance::STATUS_DESTROYED_RECYCLED;
@endphp

<div class="row">
    @if ($isInstalled)
        @can('move', $component)
            <div class="col-md-6">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('Remove To Tray') }}</h3>
                    </div>
                    <form method="POST" action="{{ route('components.remove_to_tray', $component) }}">
                        <div class="box-body">
                            @csrf
                            <p class="text-muted">{{ __('This clears the current asset assignment and places the component in your tray.') }}</p>
                            <div class="form-group">
                                <label for="remove_note">{{ trans('general.notes') }}</label>
                                <textarea class="form-control" id="remove_note" name="note" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-warning">{{ __('Remove To Tray') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endif

    @if (!$isInstalled && !$isDestroyed)
        @can('install', $component)
            <div class="col-md-6" id="component-install">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('Install Into Asset') }}</h3>
                    </div>
                    <form method="POST" action="{{ route('components.install', $component) }}">
                        <div class="box-body">
                            @csrf
                            <div class="form-group {{ $errors->has('asset_id') ? 'has-error' : '' }}">
                                <label for="component_install_asset_id">{{ __('Asset') }}</label>
                                <select class="form-control select2" aria-label="asset_id" name="asset_id" id="component_install_asset_id" style="width: 100%" required>
                                    <option value="">{{ trans('general.select_asset') }}</option>
                                    @foreach ($installableAssets as $asset)
                                        <option value="{{ $asset->id }}" @selected((string) old('asset_id') === (string) $asset->id)>
                                            {{ $asset->present()->fullName }}
                                        </option>
                                    @endforeach
                                </select>
                                {!! $errors->first('asset_id', '<span class="help-block">:message</span>') !!}
                            </div>
                            <div class="form-group {{ $errors->has('installed_as') ? 'has-error' : '' }}">
                                <label for="component_installed_as">{{ __('Installed As / Slot') }}</label>
                                <input type="text" class="form-control" id="component_installed_as" name="installed_as" value="{{ old('installed_as', $component->installed_as) }}">
                                {!! $errors->first('installed_as', '<span class="help-block">:message</span>') !!}
                            </div>
                            <div class="form-group {{ $errors->has('note') ? 'has-error' : '' }}">
                                <label for="component_install_note">{{ trans('general.notes') }}</label>
                                <textarea class="form-control" id="component_install_note" name="note" rows="3">{{ old('note') }}</textarea>
                                {!! $errors->first('note', '<span class="help-block">:message</span>') !!}
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary">{{ __('Install') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endif

    @if (!$isInstalled && !$isDestroyed && !$isDestructionPending)
        @can('move', $component)
            <div class="col-md-6">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('Move To Stock') }}</h3>
                    </div>
                    <form method="POST" action="{{ route('components.move_to_stock', $component) }}">
                        <div class="box-body">
                            @csrf
                            <div class="form-group">
                                <label for="stock_location_id">{{ __('Stock Location') }}</label>
                                <select class="form-control" id="stock_location_id" name="storage_location_id" required>
                                    <option value="">{{ __('Choose a location') }}</option>
                                    @foreach ($stockLocations as $location)
                                        <option value="{{ $location->id }}" @selected((string) old('storage_location_id', $component->storage_location_id) === (string) $location->id)>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="hidden" name="needs_verification" value="0">
                                    <input type="checkbox" name="needs_verification" value="1" @checked(old('needs_verification'))>
                                    {{ __('Mark as needing verification after moving') }}
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="verification_location_id">{{ __('Verification Location') }}</label>
                                <select class="form-control" id="verification_location_id" name="verification_location_id">
                                    <option value="">{{ __('Use stock location') }}</option>
                                    @foreach ($verificationLocations as $location)
                                        <option value="{{ $location->id }}" @selected((string) old('verification_location_id') === (string) $location->id)>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="move_note">{{ trans('general.notes') }}</label>
                                <textarea class="form-control" id="move_note" name="note" rows="3">{{ old('note') }}</textarea>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-default">{{ __('Move To Stock') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endif
</div>

<div class="row">
    @if (!$isInstalled && !$isDestroyed && !$isDestructionPending)
        @can('verify', $component)
            <div class="col-md-6">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $needsVerification ? __('Confirm Verification') : __('Flag Needs Verification') }}</h3>
                    </div>
                    @if ($needsVerification)
                        <form method="POST" action="{{ route('components.confirm_verification', $component) }}">
                            <div class="box-body">
                                @csrf
                                <div class="form-group">
                                    <label for="confirm_location_id">{{ __('Stock Location') }}</label>
                                    <select class="form-control" id="confirm_location_id" name="storage_location_id" required>
                                        <option value="">{{ __('Choose a location') }}</option>
                                        @foreach ($stockLocations as $location)
                                            <option value="{{ $location->id }}" @selected((string) old('storage_location_id', $component->storage_location_id) === (string) $location->id)>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_note">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="confirm_note" name="note" rows="3">{{ old('note') }}</textarea>
                                </div>
                            </div>
                            <div class="box-footer">
                                <button type="submit" class="btn btn-success">{{ __('Confirm Verification') }}</button>
                            </div>
                        </form>
                    @else
                        <form method="POST" action="{{ route('components.flag_needs_verification', $component) }}">
                            <div class="box-body">
                                @csrf
                                <div class="form-group">
                                    <label for="flag_location_id">{{ __('Verification Location') }}</label>
                                    <select class="form-control" id="flag_location_id" name="storage_location_id">
                                        <option value="">{{ __('Keep current location') }}</option>
                                        @foreach ($verificationLocations as $location)
                                            <option value="{{ $location->id }}" @selected((string) old('storage_location_id', $component->storage_location_id) === (string) $location->id)>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="flag_note">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="flag_note" name="note" rows="3">{{ old('note') }}</textarea>
                                </div>
                            </div>
                            <div class="box-footer">
                                <button type="submit" class="btn btn-warning">{{ __('Needs Verification') }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endcan
    @endif

    @if (!$isInstalled && !$isDestroyed)
        @can('move', $component)
            <div class="col-md-6">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $isDestructionPending ? __('Mark Destroyed') : __('Mark Destruction Pending') }}</h3>
                    </div>
                    @if ($isDestructionPending)
                        <form method="POST" action="{{ route('components.mark_destroyed', $component) }}">
                            <div class="box-body">
                                @csrf
                                <div class="form-group">
                                    <label for="destroy_note">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="destroy_note" name="note" rows="3">{{ old('note') }}</textarea>
                                </div>
                            </div>
                            <div class="box-footer">
                                <button type="submit" class="btn btn-danger">{{ __('Mark Destroyed') }}</button>
                            </div>
                        </form>
                    @else
                        <form method="POST" action="{{ route('components.mark_destruction_pending', $component) }}">
                            <div class="box-body">
                                @csrf
                                <div class="form-group">
                                    <label for="destruction_location_id">{{ __('Destruction Location') }}</label>
                                    <select class="form-control" id="destruction_location_id" name="storage_location_id">
                                        <option value="">{{ __('No location selected') }}</option>
                                        @foreach ($destructionLocations as $location)
                                            <option value="{{ $location->id }}" @selected((string) old('storage_location_id', $component->storage_location_id) === (string) $location->id)>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="destruction_note">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="destruction_note" name="note" rows="3">{{ old('note') }}</textarea>
                                </div>
                            </div>
                            <div class="box-footer">
                                <button type="submit" class="btn btn-danger">{{ __('Mark Destruction Pending') }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endcan
    @endif
</div>

@if (!$isInstalled)
    @can('delete', $component)
        <div class="row">
            <div class="col-md-12">
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('Delete Component') }}</h3>
                    </div>
                    <form method="POST" action="{{ route('components.destroy', $component) }}">
                        <div class="box-body">
                            @csrf
                            @method('DELETE')
                            <p class="text-muted">{{ __('Only loose or inactive components can be deleted. Installed components must be removed first.') }}</p>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endif
