@extends('layouts/default')

@section('title')
{{ $component->component_tag }} {{ $component->display_name }}
@parent
@stop

@section('header_right')
<a href="{{ route('components.tray') }}" class="btn btn-default">
    {{ __('My Tray') }}
</a>
@can('create', \App\Models\ComponentInstance::class)
<a href="{{ route('components.create') }}" class="btn btn-primary">
    {{ __('Register Component') }}
</a>
@endcan
<a href="{{ route('components.index') }}" class="btn btn-default">
    {{ trans('general.back') }}
</a>
@stop

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.details') }}</h3>
            </div>
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>{{ trans('general.tag') }}</dt>
                    <dd>{{ $component->component_tag }}</dd>

                    <dt>{{ trans('general.name') }}</dt>
                    <dd>{{ $component->display_name }}</dd>

                    <dt>{{ trans('general.status') }}</dt>
                    <dd>{{ $component->status }}</dd>

                    <dt>{{ trans('general.type') }}</dt>
                    <dd>{{ $component->source_type }}</dd>

                    <dt>{{ trans('general.condition') }}</dt>
                    <dd>{{ $component->condition_code }}</dd>

                    @if($component->serial)
                    <dt>{{ trans('admin/hardware/form.serial') }}</dt>
                    <dd>{{ $component->serial }}</dd>
                    @endif

                    @if($component->installed_as)
                    <dt>{{ trans('general.location') }}</dt>
                    <dd>{{ $component->installed_as }}</dd>
                    @endif

                    @if($component->componentDefinition)
                    <dt>{{ trans('general.category') }}</dt>
                    <dd>{{ $component->componentDefinition->category?->name }}</dd>

                    <dt>{{ trans('general.manufacturer') }}</dt>
                    <dd>{{ $component->componentDefinition->manufacturer?->name }}</dd>
                    @endif

                    @if($component->supplier)
                    <dt>{{ trans('general.supplier') }}</dt>
                    <dd>{{ $component->supplier->name }}</dd>
                    @endif

                    @if($component->received_at)
                    <dt>{{ trans('general.purchase_date') }}</dt>
                    <dd>{{ $component->received_at?->format('Y-m-d H:i') }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.current') }}</h3>
            </div>
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>{{ trans('general.asset') }}</dt>
                    <dd>
                        @if($component->currentAsset)
                        <a href="{{ route('hardware.show', $component->currentAsset) }}">{{ $component->currentAsset->present()->name() }}</a>
                        @else
                        <span class="text-muted">{{ trans('general.na') }}</span>
                        @endif
                    </dd>

                    <dt>{{ trans('general.location') }}</dt>
                    <dd>
                        @if($component->storageLocation)
                        {{ $component->storageLocation->name }}
                        @else
                        <span class="text-muted">{{ trans('general.na') }}</span>
                        @endif
                    </dd>

                    <dt>{{ trans('general.user') }}</dt>
                    <dd>
                        @if($component->heldBy)
                        <a href="{{ route('users.show', $component->heldBy) }}">{{ $component->heldBy->present()->fullName() }}</a>
                        @else
                        <span class="text-muted">{{ trans('general.na') }}</span>
                        @endif
                    </dd>

                    <dt>{{ trans('general.source') }}</dt>
                    <dd>
                        @if($component->sourceAsset)
                        <a href="{{ route('hardware.show', $component->sourceAsset) }}">{{ $component->sourceAsset->present()->name() }}</a>
                        @else
                        <span class="text-muted">{{ trans('general.na') }}</span>
                        @endif
                    </dd>
                </dl>

                @if($component->notes)
                <hr>
                <strong>{{ trans('general.notes') }}</strong>
                <div>{!! nl2br(\App\Helpers\Helper::parseEscapedMarkedownInline($component->notes)) !!}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        @include('components.partials.actions')

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.history') }}</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>{{ trans('general.date') }}</th>
                        <th>{{ trans('general.action') }}</th>
                        <th>{{ trans('general.user') }}</th>
                        <th>{{ trans('general.details') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($component->events as $event)
                        <tr>
                            <td>{{ $event->created_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $event->event_type }}</td>
                            <td>{{ $event->performedBy?->present()->fullName() ?? trans('general.na') }}</td>
                            <td>
                                @php
                                    $details = collect([
                                        $event->fromAsset ? 'From asset: '.$event->fromAsset->present()->name() : null,
                                        $event->toAsset ? 'To asset: '.$event->toAsset->present()->name() : null,
                                        $event->fromStorageLocation ? 'From location: '.$event->fromStorageLocation->name : null,
                                        $event->toStorageLocation ? 'To location: '.$event->toStorageLocation->name : null,
                                        $event->heldBy ? 'Held by: '.$event->heldBy->present()->fullName() : null,
                                        $event->relatedWorkOrder ? 'Work order: '.$event->relatedWorkOrder->work_order_number : null,
                                        $event->relatedWorkOrderTask ? 'Task: '.$event->relatedWorkOrderTask->title : null,
                                        $event->note,
                                    ])->filter()->values();
                                @endphp
                                @if($details->isEmpty())
                                <span class="text-muted">{{ trans('general.na') }}</span>
                                @else
                                @php
                                    $taskWorkOrder = $event->relatedWorkOrderTask?->workOrder;
                                @endphp
                                @if($event->relatedWorkOrder || $event->relatedWorkOrderTask)
                                    @foreach($details as $detail)
                                        @if(\Illuminate\Support\Str::startsWith($detail, 'Work order:') && $event->relatedWorkOrder)
                                        <a href="{{ route('work-orders.show', $event->relatedWorkOrder) }}">{{ $detail }}</a>
                                        @elseif(\Illuminate\Support\Str::startsWith($detail, 'Task:') && $taskWorkOrder && $event->relatedWorkOrderTask)
                                        <a href="{{ route('work-orders.show', $taskWorkOrder) }}#task-{{ $event->relatedWorkOrderTask->id }}">{{ $detail }}</a>
                                        @else
                                        {{ $detail }}
                                        @endif
                                        @if(!$loop->last)
                                        <span> | </span>
                                        @endif
                                    @endforeach
                                @else
                                {!! e($details->implode(' | ')) !!}
                                @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">{{ trans('general.no_results') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.file_uploads') }}</h3>
            </div>
            <div class="box-body">
                <x-filestable object_type="component-instances" :object="$component" />
            </div>
        </div>
    </div>
</div>
@stop
