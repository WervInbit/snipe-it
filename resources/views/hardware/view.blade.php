@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('admin/hardware/general.view') }} {{ $asset->asset_tag }}
    @parent
@stop

{{-- Page content --}}
@section('content')

@inject('qrLabels', 'App\\Services\\QrLabelService')

@php
    $qrFormats = collect(explode(',', $snipeSettings->qr_formats ?? 'png,pdf'))
        ->map(fn ($format) => strtolower(trim($format)))
        ->filter()
        ->values()
        ->all();
    $selectedTemplate = request('template', $snipeSettings->qr_label_template ?? config('qr_templates.default'));
    $qrTemplates = config('qr_templates.templates');
    $qrPng = in_array('png', $qrFormats) ? $qrLabels->url($asset, 'png', $selectedTemplate) : null;
    $qrPdf = in_array('pdf', $qrFormats) ? $qrLabels->url($asset, 'pdf', $selectedTemplate) : null;
@endphp

    <div class="row">

        @if (!$asset->model)
            <div class="col-md-12">
                <div class="callout callout-danger">
                    <p><strong>{{ trans('admin/models/message.no_association') }}</strong> {{ trans('admin/models/message.no_association_fix') }}</p>
                </div>
            </div>
        @endif

        @if ($asset->checkInvalidNextAuditDate())
            <div class="col-md-12">
                <div class="callout callout-warning">
                    <p><strong>{{ trans('general.warning',
                        [
                            'warning' => trans('admin/hardware/message.warning_audit_date_mismatch',
                                    [
                                        'last_audit_date' => Helper::getFormattedDateObject($asset->last_audit_date, 'datetime', false),
                                        'next_audit_date' => Helper::getFormattedDateObject($asset->next_audit_date, 'date', false)
                                    ]
                                    )
                        ]
                        ) }}</strong></p>
                </div>
            </div>
        @endif

        @if ($asset->deleted_at!='')
            <div class="col-md-12">
                <div class="callout callout-warning">
                    <x-icon type="warning" />
                    {{ trans('general.asset_deleted_warning') }}
                </div>
            </div>
        @endif

        @if (optional($asset->assetTests()->first())->needs_cleaning)
            <div class="col-md-12">
                <span class="badge badge-warning">{{ trans('tests.needs_cleaning') }}</span>
            </div>
        @endif

        @if ($asset->tests_completed_ok)
            <div class="col-md-12">
                <span class="badge badge-success"><x-icon type="check" /> {{ trans('tests.all_passed') }}</span>
            </div>
        @endif

        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs hidden-print">

                    <li class="active">
                        <a href="#details" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                            <x-icon type="info-circle" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">{{ trans('admin/users/general.info') }}</span>
                        </a>
                    </li>

                    @can('view', \App\Models\License::class)
                    <li>
                        <a href="#software" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                           <x-icon type="licenses" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">{{ trans('general.licenses') }}
                                {!! ($asset->licenses->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($asset->licenses->count()).'</span>' : '' !!}
                          </span>
                        </a>
                    </li>
                    @endcan

                    @can('view', \App\Models\Component::class)
                    <li>
                        <a href="#components" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                            <x-icon type="components" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">{{ trans('general.components') }}
                                {!! ($asset->components->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($asset->components->count()).'</span>' : '' !!}
                          </span>
                        </a>
                    </li>
                    @endcan

                    @can('view', \App\Models\Asset::class)
                    <li>
                        <a href="#assets" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                            <x-icon type="assets" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">
                                {{ trans('general.assets') }}
                                {!! ($asset->assignedAssets()->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($asset->assignedAssets()->count()).'</span>' : '' !!}

                          </span>
                        </a>
                    </li>
                    @endcan

                    @if ($asset->assignedAccessories->count() > 0)
                        @can('view', \App\Models\Accessory::class)
                        <li>
                            <a href="#accessories_assigned" data-toggle="tab" data-tooltip="true">

                                <span class="hidden-lg hidden-md">
                                    <i class="fas fa-keyboard fa-2x"></i>
                                </span>
                                <span class="hidden-xs hidden-sm">
                                    {{ trans('general.accessories_assigned') }}
                                    {!! ($asset->assignedAccessories()->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($asset->assignedAccessories()->count()).'</span>' : '' !!}

                                </span>
                            </a>
                        </li>
                        @endcan
                    @endif

                    <li>
                        <a href="#history" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                              <x-icon type="history" class="fa-2x "/>
                          </span>
                            <span class="hidden-xs hidden-sm">{{ trans('general.history') }}
                          </span>
                        </a>
                    </li>

                    @can('view', \App\Models\Asset::class)
                    <li>
                        <a href="#tests" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                              <i class="fas fa-vial fa-2x"></i>
                          </span>
                            <span class="hidden-xs hidden-sm">{{ trans('tests.tests') }}
                                {!! ($asset->tests()->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($asset->tests()->count()).'</span>' : '' !!}
                          </span>
                        </a>
                    </li>

                    <li>
                        <a href="#images" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                              <i class="fas fa-camera fa-2x"></i>
                          </span>
                            <span class="hidden-xs hidden-sm">{{ trans('general.images') }}
                                {!! ($asset->images->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($asset->images->count()).'</span>' : '' !!}
                          </span>
                        </a>
                    </li>

                    <li>
                        <a href="#maintenances" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                              <x-icon type="maintenances" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">{{ trans('general.maintenances') }}
                                {!! ($asset->maintenances()->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($asset->maintenances()->count()).'</span>' : '' !!}
                          </span>
                        </a>
                    </li>
                    @endcan

                    @can('files', $asset)
                    <li>
                        <a href="#files" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                            <x-icon type="files" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">{{ trans('general.files') }}
                                {!! ($asset->uploads->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($asset->uploads->count()).'</span>' : '' !!}
                          </span>
                        </a>
                    </li>
                    @endcan

                    @can('view', $asset->model)
                    <li>
                        <a href="#modelfiles" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                              <x-icon type="more-files" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">
                            {{ trans('general.additional_files') }}
                                {!! ($asset->model) && ($asset->model->uploads->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($asset->model->uploads->count()).'</span>' : '' !!}
                          </span>
                        </a>
                    </li>
                    @endcan


                    @can('update', \App\Models\Asset::class)
                        <li class="pull-right">
                            <a href="#" onclick="var f=document.getElementById('upload-form');f.style.display=f.style.display==='none'?'block':'none';return false;">
                                <span class="hidden-lg hidden-xl hidden-md">
                                    <x-icon type="paperclip" class="fa-2x" />
                                </span>
                                <span class="hidden-xs hidden-sm">
                                    <x-icon type="paperclip" />
                                    {{ trans('button.upload') }}
                                </span>
                            </a>
                        </li>
                    @endcan

                </ul>

                @can('update', \App\Models\Asset::class)
                    <form id="upload-form" method="POST" action="{{ route('ui.files.store', ['object_type' => 'assets', 'id' => $asset->id]) }}" enctype="multipart/form-data" style="display:none; margin:15px 0;">
                        @csrf
                        <input type="file" name="file[]" multiple class="form-control" accept="{{ config('filesystems.allowed_upload_mimetypes') }}">
                        <textarea class="form-control" name="notes" placeholder="{{ trans('general.notes') }}" rows="3" style="margin-top:10px;"></textarea>
                        <button type="submit" class="btn btn-primary" style="margin-top:10px;">{{ trans('button.upload') }}</button>
                    </form>
                @endcan

                <div class="tab-content">
                    <div class="tab-pane fade in active" id="details">
                    <div class="row">

                        <div class="info-stack-container">
                            <!-- Start button column -->
                            <div class="col-md-3 col-xs-12 col-sm-push-9 info-stack">

                                <div class="col-md-12 text-center">
                                    @if (($asset->image) || (($asset->model) && ($asset->model->image!='')))
                                        <div class="text-center col-md-12" style="padding-bottom: 15px;">
                                            <a href="{{ ($asset->getImageUrl()) ? $asset->getImageUrl() : null }}" data-toggle="lightbox" data-type="image">
                                                <img src="{{ ($asset->getImageUrl()) ? $asset->getImageUrl() : null }}" class="assetimg img-responsive" alt="{{ $asset->getDisplayNameAttribute() }}">
                                            </a>
                                        </div>
                                    @else
                                        <!-- generic image goes here -->
                                    @endif
                                </div>


                                @if ($asset->deleted_at=='')
                                    @can('update', $asset)
                                        <div class="col-md-12 hidden-print" style="padding-top: 5px;">
                                    <a href="{{ route('hardware.edit', $asset) }}" class="btn btn-sm btn-warning btn-social btn-block hidden-print">
                                        <x-icon type="edit" />
                                        {{ trans('admin/hardware/general.edit') }}
                                    </a>
                                </div>
                            @endcan

                            @if (config('qr_templates.enable_ui', true) && ($qrPdf || $qrPng))
                                <div class="col-md-12 hidden-print" style="padding-top: 5px;">
                                    <div class="btn-group btn-block">
                                        <button type="button" class="btn btn-sm btn-default btn-block dropdown-toggle" data-toggle="dropdown">
                                            <x-icon type="print" /> {{ trans('general.print_qr') }} <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            @if ($qrPdf)
                                                <li><a href="{{ $qrPdf }}" target="_blank">{{ trans('general.print_pdf') }}</a></li>
                                            @endif
                                            @if ($qrPng)
                                                <li><a href="{{ $qrPng }}" download>{{ trans('general.download_png') }}</a></li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            @endif


                            @can('update', \App\Models\Asset::class)
                                <div class="col-md-12 hidden-print" style="padding-top: 5px;">
                                    <button type="button" style="width: 100%" class="btn btn-sm btn-primary btn-block btn-social hidden-print" onclick="var f=document.getElementById('note-form');f.style.display=f.style.display==='none'?'block':'none';">
                                        <x-icon type="note" />
                                        {{ trans('general.add_note') }}
                                    </button>
                                    <form id="note-form" method="POST" action="{{ route('notes.store') }}" style="display:none; margin-top:10px;">
                                        @csrf
                                        <input type="hidden" name="type" value="asset">
                                        <input type="hidden" name="id" value="{{ $asset->id }}">
                                        <textarea class="form-control" name="note" required></textarea>
                                        <button type="submit" class="btn btn-primary" style="margin-top:10px;">{{ trans('general.save') }}</button>
                                    </form>
                                </div>
                            @endcan

                                @can('create', $asset)
                                    <div class="col-md-12 hidden-print" style="padding-top: 5px;">
                                        <a href="{{ route('clone/hardware', $asset->id) }}" class="btn btn-sm btn-info btn-block btn-social hidden-print">
                                            <x-icon type="clone" />
                                            {{ trans('admin/hardware/general.clone') }}
                                        </a>
                                    </div>
                                @endcan

                                {{-- Legacy TCPDF label generator hidden while QR module is active --}}

                                @can('delete', $asset)
                                    <div class="col-md-12 hidden-print" style="padding-top: 30px; padding-bottom: 30px;">

                                        @if ($asset->deleted_at=='')
                                            <button class="btn btn-sm btn-block btn-danger btn-social delete-asset" onclick="return confirm('{{ trans('general.sure_to_delete_var', ['item' => $asset->asset_tag]) }}');">
                                                <x-icon type="delete" />
                                                @if ($asset->assignedTo)
                                                    {{ trans('general.checkin_and_delete') }}
                                                @else
                                                    {{ trans('general.delete') }}
                                                @endif
                                            </button>
                                            <span class="sr-only">{{ trans('general.delete') }}</span>
                                        @else
                                            <form method="POST" action="{{ route('restore/hardware', [$asset]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-block btn-warning btn-social delete-asset">
                                                    <x-icon type="restore" />
                                                    {{ trans('general.restore') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endcan

                                @if (($asset->assignedTo) && ($asset->deleted_at==''))
                                    <div class="col-md-12" style="text-align: left">
                                        <h2>
                                            {{ trans('admin/hardware/form.checkedout_to') }}
                                            <x-icon type="long-arrow-right" />
                                        </h2>

                                        <ul class="list-unstyled" style="line-height: 25px; font-size: 14px">

                                            @if (($asset->checkedOutToUser()) && ($asset->assignedTo->present()->gravatar()))
                                                <li>
                                                    <img src="{{ $asset->assignedTo->present()->gravatar() }}" class="user-image-inline hidden-print" alt="{{ $asset->assignedTo->present()->fullName() }}">
                                                    {!! $asset->assignedTo->present()->nameUrl() !!}
                                                </li>
                                            @else
                                                <li>
                                                    <x-icon type="{{ $asset->assignedType() }}" class="fa-fw" />
                                                    {!! $asset->assignedTo->present()->nameUrl() !!}
                                                </li>
                                            @endif


                                            @if ((isset($asset->assignedTo->employee_num)) && ($asset->assignedTo->employee_num!=''))
                                                <li>
                                                    <x-icon type="employee_num" class="fa-fw"/>
                                                    {{ $asset->assignedTo->employee_num }}
                                                </li>
                                            @endif
                                            @if ((isset($asset->assignedTo->email)) && ($asset->assignedTo->email!=''))
                                                <li>
                                                    <x-icon type="email" class="fa-fw" />
                                                    <a href="mailto:{{ $asset->assignedTo->email }}">{{ $asset->assignedTo->email }}</a>
                                                </li>
                                            @endif

                                            @if ((isset($asset->assignedTo)) && ($asset->assignedTo->phone!=''))
                                                <li>
                                                    <x-icon type="phone" class="fa-fw" />
                                                    <a href="tel:{{ $asset->assignedTo->phone }}">{{ $asset->assignedTo->phone }}</a>
                                                </li>
                                            @endif

                                            @if((isset($asset->assignedTo)) && ($asset->assignedTo->department))
                                                <li>
                                                    <x-icon type="department" class="fa-fw" />
                                                    {{ $asset->assignedTo->department->name}}</li>
                                            @endif

                                            @if (isset($asset->location))
                                                <li>
                                                    <x-icon type="locations" class="fa-fw" />
                                                     {{ $asset->location->present()->fullName() }}
                                                     @if ($asset->location_note)
                                                         ({{ trans('general.see_note') }})
                                                     @endif
                                                </li>
                                                <li>{{ $asset->location->address }}
                                                    @if ($asset->location->address2!='')
                                                        {{ $asset->location->address2 }}
                                                    @endif
                                                </li>

                                                <li>{{ $asset->location->city }}
                                                    @if (($asset->location->city!='') && ($asset->location->state!=''))
                                                        ,
                                                    @endif
                                                    {{ $asset->location->state }} {{ $asset->location->zip }}
                                                </li>
                                            @endif
                                            @if ($asset->location_note)
                                                <li><strong>{{ trans('admin/hardware/form.location_note') }}:</strong> <em>{{ $asset->location_note }}</em></li>
                                            @endif
                                            <li>
                                                <x-icon type="calendar" class="fa-fw" />
                                                {{ trans('admin/hardware/form.checkout_date') }}: {{ Helper::getFormattedDateObject($asset->last_checkout, 'date', false) }}
                                            </li>
                                            @if (isset($asset->expected_checkin))
                                                <li>
                                                    <x-icon type="calendar" class="fa-fw" />
                                                    {{ trans('general.expected_checkin') }}: {{ Helper::getFormattedDateObject($asset->expected_checkin, 'date', false) }}
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif
                                @if (config('qr_templates.enable_ui', true))
                                    <div class="col-md-12 text-center" style="padding-top: 15px;">
                                        @if($qrPng)
                                            <img src="{{ $qrPng }}" class="img-thumbnail" style="height: 150px; width: 150px; margin-right: 10px;" alt="QR code for {{ $asset->getDisplayNameAttribute() }}">
                                        @endif
                                        <div class="mt-2">
                                            <form method="get" class="d-inline-block">
                                                <select name="template" onchange="this.form.submit()" class="form-control" style="display:inline-block;width:auto;">
                                                    @foreach($qrTemplates as $key => $tpl)
                                                        <option value="{{ $key }}" @selected($selectedTemplate === $key)>{{ $tpl['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                            @if($qrPdf)
                                                <a href="{{ $qrPdf }}" target="_blank" class="btn btn-default"><x-icon type="print" /> Print</a>
                                            @endif
                                            @if($qrPng)
                                                <a href="{{ $qrPng }}" download class="btn btn-default"><x-icon type="download" /> {{ trans('general.download') }}</a>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endif
                                <br><br>
                            </div>




                            <!-- End button column -->

                            <div class="col-md-9 col-xs-12 col-sm-pull-3 info-stack">

                                <div class="row-new-striped">

                                    @if ($asset->asset_tag)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('admin/hardware/form.tag') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                <span class="js-copy-assettag">{{ $asset->asset_tag  }}</span>

                                                <i class="fa-regular fa-clipboard js-copy-link hidden-print" data-clipboard-target=".js-copy-assettag" aria-hidden="true" data-tooltip="true" data-placement="top" title="{{ trans('general.copy_to_clipboard') }}">
                                                    <span class="sr-only">{{ trans('general.copy_to_clipboard') }}</span>
                                                </i>
                                            </div>
                                        </div>
                                    @endif


                                    @if ($asset->deleted_at!='')
                                        <div class="row">
                                            <div class="col-md-3">
                                                <span class="text-danger"><strong>{{ trans('general.deleted') }}</strong></span>
                                            </div>
                                            <div class="col-md-9">
                                                {{ \App\Helpers\Helper::getFormattedDateObject($asset->deleted_at, 'date', false) }}

                                            </div>
                                        </div>
                                    @endif



                                    @if ($asset->assetstatus)

                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('general.status') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                @if (($asset->assignedTo) && ($asset->deleted_at==''))
                                                    <x-icon type="circle-solid" class="text-blue" />
                                                    {{ $asset->assetstatus->name }}
                                                    <label class="label label-default">{{ trans('general.deployed') }}</label>


                                                    <x-icon type="long-arrow-right" />
                                                    <x-icon type="{{ $asset->assignedType() }}" class="fa-fw" />
                                                    {!!  $asset->assignedTo->present()->nameUrl() !!}
                                                @else
                                                    @if (($asset->assetstatus) && ($asset->assetstatus->deployable=='1'))
                                                        <x-icon type="circle-solid" class="text-green" />
                                                    @elseif (($asset->assetstatus) && ($asset->assetstatus->pending=='1'))
                                                        <x-icon type="circle-solid" class="text-orange" />
                                                    @else
                                                        <x-icon type="x" class="text-red" />
                                                    @endif
                                                    <a href="{{ route('statuslabels.show', $asset->assetstatus->id) }}">
                                                        {{ $asset->assetstatus->name }}</a>
                                                    <label class="label label-default">{{ $asset->present()->statusMeta }}</label>

                                                @endif
                                            </div>
                                        </div>
                                    @endif


                                    @if ($asset->company)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('general.company') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                <a href="{{ url('/companies/' . $asset->company->id) }}">{{ $asset->company->name }}</a>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($asset->name)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('admin/hardware/form.name') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ $asset->name }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ($asset->serial)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('admin/hardware/form.serial') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                <span class="js-copy-serial">{{ $asset->serial  }}</span>

                                                <i class="fa-regular fa-clipboard js-copy-link hidden-print" data-clipboard-target=".js-copy-serial" aria-hidden="true" data-tooltip="true" data-placement="top" title="{{ trans('general.copy_to_clipboard') }}">
                                                    <span class="sr-only">{{ trans('general.copy_to_clipboard') }}</span>
                                                </i>
                                            </div>
                                        </div>
                                    @endif

                                    @if(isset($resolvedAttributes) && $resolvedAttributes->isNotEmpty())
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ __('Specification') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                <table class="table table-condensed">
                                                    <tbody>
                                                    @foreach($resolvedAttributes as $attribute)
                                                        @php
                                                            $displayValue = $attribute->formattedValue();
                                                            $modelDisplay = $attribute->formattedModelValue();
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $attribute->definition->label }}</td>
                                                            <td>
                                                                @if($displayValue !== null && $displayValue !== '')
                                                                    {{ $displayValue }}
                                                                @else
                                                                    {{ __('Not specified') }}
                                                                @endif

                                                                @if($attribute->isOverride)
                                                                    <span class="label label-info">{{ __('Override') }}</span>
                                                                @endif

                                                                @if($attribute->isOverride && $modelDisplay)
                                                                    <span class="text-muted">({{ __('Model: :value', ['value' => $modelDisplay]) }})</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($asset->last_checkout!='')
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/table.checkout_date') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ Helper::getFormattedDateObject($asset->last_checkout, 'datetime', false) }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ((isset($audit_log)) && ($audit_log->created_at))
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.last_audit') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {!! $asset->checkInvalidNextAuditDate() ? '<i class="fas fa-exclamation-triangle text-orange" aria-hidden="true"></i>' : '' !!}
                                                {{ Helper::getFormattedDateObject($audit_log->created_at, 'datetime', false) }}
                                                @if ($audit_log->user)
                                                    (by {{ link_to_route('users.show', $audit_log->user->present()->fullname(), [$audit_log->user->id]) }})
                                                @endif

                                            </div>
                                        </div>
                                    @endif

                                    @if ($asset->next_audit_date)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.next_audit_date') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {!! $asset->checkInvalidNextAuditDate() ? '<i class="fas fa-exclamation-triangle text-orange" aria-hidden="true"></i>' : '' !!}
                                                {{ Helper::getFormattedDateObject($asset->next_audit_date, 'date', false) }}
                                            </div>
                                        </div>
                                    @endif

                                    @if (($asset->model) && ($asset->model->manufacturer))
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.manufacturer') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                <ul class="list-unstyled">
                                                    @can('view', \App\Models\Manufacturer::class)

                                                        <li>
                                                            <a href="{{ route('manufacturers.show', $asset->model->manufacturer->id) }}">
                                                                {{ $asset->model->manufacturer->name }}
                                                            </a>
                                                        </li>

                                                    @else
                                                        <li> {{ $asset->model->manufacturer->name }}</li>
                                                    @endcan

                                                    @if (($asset->model) && ($asset->model->manufacturer) &&  ($asset->model->manufacturer->url!=''))
                                                        <li>
                                                            <x-icon type="globe-us" />
                                                            <a href="{{ $asset->present()->dynamicUrl($asset->model->manufacturer->url) }}" target="_blank">
                                                                {{ $asset->present()->dynamicUrl($asset->model->manufacturer->url) }}
                                                                <x-icon type="external-link" />
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if (($asset->model) && ($asset->model->manufacturer) &&  ($asset->model->manufacturer->support_url!=''))
                                                        <li>
                                                            <x-icon type="more-info" />
                                                            <a href="{{ $asset->present()->dynamicUrl($asset->model->manufacturer->support_url) }}" target="_blank">
                                                                {{ $asset->present()->dynamicUrl($asset->model->manufacturer->support_url) }}
                                                                <x-icon type="external-link" />
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if (($asset->model) && ($asset->model->manufacturer) &&  ($asset->model->manufacturer->warranty_lookup_url!=''))
                                                        <li>
                                                            <x-icon type="maintenances" />
                                                            <a href="{{ $asset->present()->dynamicUrl($asset->model->manufacturer->warranty_lookup_url) }}" target="_blank">
                                                                {{ $asset->present()->dynamicUrl($asset->model->manufacturer->warranty_lookup_url) }}

                                                                <x-icon type="external-link" />
                                                                    <span class="sr-only">{{ trans('admin/hardware/general.mfg_warranty_lookup', ['manufacturer' => $asset->model->manufacturer->name]) }}</span></i>
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if (($asset->model) && ($asset->model->manufacturer->support_phone))
                                                        <li>
                                                            <x-icon type="phone" />
                                                            <a href="tel:{{ $asset->model->manufacturer->support_phone }}">
                                                                {{ $asset->model->manufacturer->support_phone }}
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if (($asset->model) && ($asset->model->manufacturer->support_email))
                                                        <li>
                                                            <x-icon type="email" />
                                                            <a href="mailto:{{ $asset->model->manufacturer->support_email }}">
                                                                {{ $asset->model->manufacturer->support_email }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>
                                                {{ trans('general.category') }}
                                            </strong>
                                        </div>
                                        <div class="col-md-9">
                                            @if (($asset->model) && ($asset->model->category))

                                                @can('view', \App\Models\Category::class)

                                                    <a href="{{ route('categories.show', $asset->model->category->id) }}">
                                                        {{ $asset->model->category->name }}
                                                    </a>
                                                @else
                                                    {{ $asset->model->category->name }}
                                                @endcan
                                            @else
                                                Invalid category
                                            @endif
                                        </div>
                                    </div>

                                    @if ($asset->model)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.model') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                @if ($asset->model)

                                                    @can('view', \App\Models\AssetModel::class)
                                                        <a href="{{ route('models.show', $asset->model->id) }}">
                                                            {{ $asset->model->name }}
                                                        </a>
                                                    @else
                                                        {{ $asset->model->name }}
                                                    @endcan

                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>
                                                {{ trans('admin/models/table.modelnumber') }}
                                            </strong>
                                        </div>
<div class="col-md-9">
                                            {{ $asset->displayModelNumber() ?? '' }}
                                        </div>
                                    </div>

                                    <!-- byod -->
                                    <div class="row byod">
                                        <div class="col-md-3">
                                            <strong>{{ trans('general.byod') }}</strong>
                                        </div>
                                        <div class="col-md-9">
                                            {!! ($asset->byod=='1') ? '<i class="fas fa-check text-success" aria-hidden="true"></i> '.trans('general.yes') : '<i class="fas fa-times text-danger" aria-hidden="true"></i> '.trans('general.no')  !!}
                                        </div>
                                    </div>

                                    <!-- requestable -->
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>{{ trans('admin/hardware/general.requestable') }}</strong>
                                        </div>
                                        <div class="col-md-9">
                                            {!! ($asset->requestable=='1') ? '<i class="fas fa-check text-success" aria-hidden="true"></i> '.trans('general.yes') : '<i class="fas fa-times text-danger" aria-hidden="true"></i> '.trans('general.no')  !!}
                                        </div>
                                    </div>

                                    @can('update', $asset)
                                        @if (!optional($asset->assetstatus)->name || strtolower($asset->assetstatus->name) !== 'sold')
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('admin/hardware/general.available_for_sale') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                <form method="POST" action="{{ route('hardware.toggle-sale', $asset) }}" class="form-inline" style="display:flex; align-items:center; gap:8px;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_sellable" value="0">
                                                    <label class="checkbox-inline" style="margin:0;">
                                                        <input type="checkbox" name="is_sellable" value="1" aria-label="{{ trans('admin/hardware/general.available_for_sale') }}" onchange="this.form.submit();" {{ $asset->is_sellable ? 'checked' : '' }}>
                                                    </label>
                                                    <span class="help-block" style="margin:0;">{{ trans('admin/hardware/general.available_for_sale_help') }}</span>
                                                </form>
                                            </div>
                                        </div>
                                        @endif

                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('admin/hardware/general.internal_use_only') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                <form method="POST" action="{{ route('hardware.toggle-internal', $asset) }}" class="form-inline" style="display:flex; align-items:center; gap:8px;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="byod" value="0">
                                                    <label class="checkbox-inline" style="margin:0;">
                                                        <input type="checkbox" name="byod" value="1" aria-label="{{ trans('admin/hardware/general.internal_use_only') }}" onchange="this.form.submit();" {{ $asset->byod ? 'checked' : '' }}>
                                                    </label>
                                                    <span class="help-block" style="margin:0;">{{ trans('admin/hardware/general.internal_use_only_help') }}</span>
                                                </form>
                                            </div>
                                        </div>
                                    @endcan

                                    @if (($asset->model) && ($asset->model->fieldset))
                                        @foreach($asset->model->fieldset->fields as $field)
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ $field->name }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9{{ (($field->format=='URL') && ($asset->{$field->db_column_name()}!='')) ? ' ellipsis': '' }}">
                                                    @if (!empty($asset->{$field->db_column_name()}))
                                                        {{-- Hidden span used as copy target --}}
                                                        {{-- It's tempting to break out the HTML into separate lines for this, but it results in extra spaces being added onto the end of the coipied value --}}
                                                        <span class="js-copy-{{ $field->id }} hidden-print" style="font-size: 0px;">{{ ($field->isFieldDecryptable($asset->{$field->db_column_name()}) ? Helper::gracefulDecrypt($field, $asset->{$field->db_column_name()}) : $asset->{$field->db_column_name()}) }}</span>

                                                        {{-- Clipboard icon --}}
                                                        <i class="fa-regular fa-clipboard js-copy-link hidden-print"
                                                           data-clipboard-target=".js-copy-{{ $field->id }}"
                                                           aria-hidden="true"
                                                           data-tooltip="true"
                                                           data-placement="top"
                                                           title="{{ trans('general.copy_to_clipboard') }}">
                                                            <span class="sr-only">{{ trans('general.copy_to_clipboard') }}</span>
                                                        </i>
                                                    @endif
                                                    @if (($field->field_encrypted=='1') && ($asset->{$field->db_column_name()}!=''))

                                                        <i class="fas fa-lock" data-tooltip="true" data-placement="top" title="{{ trans('admin/custom_fields/general.value_encrypted') }}" onclick="showHideEncValue(this)" id="text-{{ $field->id }}"></i>
                                                    @endif

                                                    @if ($field->isFieldDecryptable($asset->{$field->db_column_name()} ))
                                                        @can('assets.view.encrypted_custom_fields')
                                                            @php
                                                                $fieldSize = strlen(Helper::gracefulDecrypt($field, $asset->{$field->db_column_name()}))
                                                            @endphp
                                                            @if ($fieldSize > 0)
                                                                <span id="text-{{ $field->id }}-to-hide">{{ str_repeat('*', $fieldSize) }}</span>
                                                                    @if (($field->format=='URL') && ($asset->{$field->db_column_name()}!=''))
                                                                        <span class="js-copy-{{ $field->id }} hidden-print"
                                                                              id="text-{{ $field->id }}-to-show"
                                                                              style="font-size: 0px;">
                                                                            <a href="{{ Helper::gracefulDecrypt($field, $asset->{$field->db_column_name()}) }}"
                                                                                    target="_new">{{ Helper::gracefulDecrypt($field, $asset->{$field->db_column_name()}) }}</a>
                                                                        </span>
                                                                    @elseif (($field->format=='DATE') && ($asset->{$field->db_column_name()}!=''))
                                                                        <span class="js-copy-{{ $field->id }} hidden-print"
                                                                              id="text-{{ $field->id }}-to-show"
                                                                              style="font-size: 0px;">{{ \App\Helpers\Helper::gracefulDecrypt($field, \App\Helpers\Helper::getFormattedDateObject($asset->{$field->db_column_name()}, 'date', false)) }}</span>
                                                                    @else
                                                                        <span class="js-copy-{{ $field->id }} hidden-print"
                                                                              id="text-{{ $field->id }}-to-show"
                                                                              style="font-size: 0px;">{{ Helper::gracefulDecrypt($field, $asset->{$field->db_column_name()}) }}</span>
                                                                    @endif
                                                            @endif
                                                        @else
                                                            {{ strtoupper(trans('admin/custom_fields/general.encrypted')) }}
                                                        @endcan

                                                    @else
                                                        @if (($field->format=='BOOLEAN') && ($asset->{$field->db_column_name()}!=''))
                                                            {!! ($asset->{$field->db_column_name()} == 1) ? "<span class='fas fa-check-circle' style='color:green' />" : "<span class='fas fa-times-circle' style='color:red' />" !!}
                                                        @elseif (($field->format=='URL') && ($asset->{$field->db_column_name()}!=''))
                                                            <a href="{{ $asset->{$field->db_column_name()} }}" target="_new">{{ $asset->{$field->db_column_name()} }}</a>
                                                        @elseif (($field->format=='DATE') && ($asset->{$field->db_column_name()}!=''))
                                                            {{ \App\Helpers\Helper::getFormattedDateObject($asset->{$field->db_column_name()}, 'date', false) }}
                                                        @else
                                                            {!! nl2br(e($asset->{$field->db_column_name()})) !!}
                                                        @endif

                                                    @endif

                                                    @if ($asset->{$field->db_column_name()}=='')
                                                        &nbsp;
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif


                                    @if ($asset->purchase_date)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.date') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ Helper::getFormattedDateObject($asset->purchase_date, 'date', false) }}
                                                -
                                                {{ Carbon::parse($asset->purchase_date)->diff(Carbon::now())->format('%y years, %m months and %d days')}}

                                            </div>
                                        </div>
                                    @endif

                                    @if ($asset->purchase_cost)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.cost') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                @if (($asset->id) && ($asset->location))
                                                    {{ $asset->location->currency }}
                                                @elseif (($asset->id) && ($asset->location))
                                                    {{ $asset->location->currency }}
                                                @else
                                                    {{ $snipeSettings->default_currency }}
                                                @endif
                                                {{ Helper::formatCurrencyOutput($asset->purchase_cost)}}

                                            </div>
                                        </div>
                                    @endif
                                    @if(($asset->components->count() > 0) && ($asset->purchase_cost))
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/table.components_cost') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                @if (($asset->id) && ($asset->location))
                                                    {{ $asset->location->currency }}
                                                @elseif (($asset->id) && ($asset->location))
                                                    {{ $asset->location->currency }}
                                                @else
                                                    {{ $snipeSettings->default_currency }}
                                                @endif
                                                {{Helper::formatCurrencyOutput($asset->getComponentCost())}}
                                            </div>
                                        </div>
                                    @endif
                                    @if (($asset->model) && ($asset->depreciation) && ($asset->purchase_date))
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/table.current_value') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                @if (($asset->id) && ($asset->location))
                                                    {{ $asset->location->currency }}
                                                @elseif (($asset->id) && ($asset->location))
                                                    {{ $asset->location->currency }}
                                                @else
                                                    {{ $snipeSettings->default_currency }}
                                                @endif
                                                {{ Helper::formatCurrencyOutput($asset->getDepreciatedValue() )}}


                                            </div>
                                        </div>
                                    @endif
                                    @if ($asset->order_number)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.order_number') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                <a href="{{ route('hardware.index', ['order_number' => $asset->order_number]) }}">{{ $asset->order_number }}</a>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($asset->supplier)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.supplier') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                @can ('superuser')
                                                    <a href="{{ route('suppliers.show', $asset->supplier_id) }}">
                                                        {{ $asset->supplier->name }}
                                                    </a>
                                                @else
                                                    {{ $asset->supplier->name }}
                                                @endcan
                                            </div>
                                        </div>
                                    @endif


                                    @if ($asset->warranty_months)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.warranty') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ trans_choice('general.months_plural', $asset->warranty_months) }}
                                                @if (($asset->model) && ($asset->model->manufacturer) && ($asset->model->manufacturer->warranty_lookup_url!=''))
                                                    <a href="{{ $asset->present()->dynamicUrl($asset->model->manufacturer->warranty_lookup_url) }}" target="_blank">
                                                        <x-icon type="external-link" />
                                                        <span class="sr-only">{{ trans('admin/hardware/general.mfg_warranty_lookup', ['manufacturer' => $asset->model->manufacturer->name]) }}</span></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.warranty_expires') }}
                                                    @if ($asset->purchase_date)
                                                        {!! $asset->present()->warranty_expires() < date("Y-m-d") ? '<i class="fas fa-exclamation-triangle text-orange" aria-hidden="true"></i>' : '' !!}
                                                    @endif

                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                @if ($asset->purchase_date)
                                                    {{ Helper::getFormattedDateObject($asset->present()->warranty_expires(), 'date', false) }}
                                                    -
                                                    {{ Carbon::parse($asset->present()->warranty_expires())->diffForHumans(['parts' => 2]) }}
                                                @else
                                                    {{ trans('general.na_no_purchase_date') }}
                                                @endif
                                            </div>
                                        </div>

                                    @endif

                                    @if (($asset->model) && ($asset->depreciation))
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.depreciation') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ $asset->depreciation->name }}
                                                ({{ trans_choice('general.months_plural', $asset->depreciation->months) }})
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.fully_depreciated') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                @if ($asset->purchase_date)
                                                    {{ Helper::getFormattedDateObject($asset->depreciated_date()->format('Y-m-d'), 'date', false) }}
                                                    -
                                                    {{ Carbon::parse($asset->depreciated_date())->diffForHumans(['parts' => 2]) }}
                                                @else
                                                    {{ trans('general.na_no_purchase_date') }}
                                                @endif

                                            </div>
                                        </div>
                                    @endif

                                    @if (($asset->asset_eol_date) && ($asset->purchase_date))
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.eol_rate') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ (int) Carbon::parse($asset->asset_eol_date)->diffInMonths($asset->purchase_date, true) }}
                                                {{ trans('admin/hardware/form.months') }}

                                            </div>
                                        </div>
                                    @endif
                                    @if ($asset->asset_eol_date)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.eol_date') }}
                                                    @if ($asset->purchase_date)
                                                        {!! $asset->asset_eol_date < date("Y-m-d") ? '<i class="fas fa-exclamation-triangle text-orange" aria-hidden="true"></i>' : '' !!}
                                                    @endif
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                @if ($asset->asset_eol_date)
                                                    {{ Helper::getFormattedDateObject($asset->asset_eol_date, 'date', false) }}
                                                    -
                                                    {{ Carbon::parse($asset->asset_eol_date)->diffForHumans(['parts' => 2]) }}
                                                @else
                                                    {{ trans('general.na_no_purchase_date') }}
                                                @endif
                                                @if ($asset->eol_explicit =='1')
                                                        <span data-tooltip="true"
                                                                data-placement="top"
                                                                data-title="Explicit EOL"
                                                                title="Explicit EOL">
                                                                <x-icon type="warning" class="text-orange" />
                                                        </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif


                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>
                                                {{ trans('admin/hardware/form.notes') }}
                                            </strong>
                                        </div>
                                        <div class="col-md-9">
                                            {!! nl2br(Helper::parseEscapedMarkedownInline($asset->notes)) !!}
                                        </div>
                                    </div>

                                    @if ($asset->location)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.location') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                @can('superuser')
                                                    <a href="{{ route('locations.show', ['location' => $asset->location->id]) }}">
                                                        {{ $asset->location->present()->fullName() }}
                                                    </a>
                                                @else
                                                    {{ $asset->location->present()->fullName() }}
                                                @endcan
                                                @if ($asset->location_note)
                                                    ({{ trans('general.see_note') }})
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    @if ($asset->location_note)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.location_note') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                <em>{{ $asset->location_note }}</em>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($asset->defaultLoc)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.default_location') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                @can('superuser')
                                                    <a href="{{ route('locations.show', ['location' => $asset->defaultLoc->id]) }}">
                                                        {{ $asset->defaultLoc->present()->fullName() }}
                                                    </a>
                                                @else
                                                    {{ $asset->defaultLoc->present()->fullName() }}
                                                @endcan
                                            </div>
                                        </div>
                                    @endif

                                    @if ($asset->created_at!='')
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.created_at') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ Helper::getFormattedDateObject($asset->created_at, 'datetime', false) }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ($asset->updated_at!='')
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.updated_at') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ Helper::getFormattedDateObject($asset->updated_at, 'datetime', false) }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ($asset->expected_checkin!='')
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.expected_checkin') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ Helper::getFormattedDateObject($asset->expected_checkin, 'date', false) }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ($asset->last_checkin!='')
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/table.last_checkin_date') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ Helper::getFormattedDateObject($asset->last_checkin, 'datetime', false) }}
                                            </div>
                                        </div>
                                    @endif



                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>
                                                {{ trans('general.checkouts_count') }}
                                            </strong>
                                        </div>
                                        <div class="col-md-9">
                                            {{ ($asset->checkouts) ? (int) $asset->checkouts->count() : '0' }}
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>
                                                {{ trans('general.checkins_count') }}
                                            </strong>
                                        </div>
                                        <div class="col-md-9">
                                            {{ ($asset->checkins) ? (int) $asset->checkins->count() : '0' }}
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>
                                                {{ trans('general.user_requests_count') }}
                                            </strong>
                                        </div>
                                        <div class="col-md-9">
                                            {{ ($asset->userRequests) ? (int) $asset->userRequests->count() : '0' }}
                                        </div>
                                    </div>

                                </div> <!--/end striped container-->
                            </div> <!-- end col-md-9 -->
                        </div><!-- end info-stack-container -->
                        </div> <!--/.row-->
                    </div><!-- /.tab-pane -->

                    @can('view', \App\Models\License::class)
                    <div class="tab-pane fade" id="software">
                        <div class="row{{($asset->licenses->count() > 0 ) ? '' : ' hidden-print'}}">
                            <div class="col-md-12">
                                <!-- Licenses assets table -->
                                    <table class="table">
                                        <thead>
                                        <tr>
                                            <th>{{ trans('general.name') }}</th>
                                            <th><span class="line"></span>{{ trans('admin/licenses/form.license_key') }}</th>
                                            <th><span class="line"></span>{{ trans('admin/licenses/form.expiration') }}</th>
                                            <th><span class="line"></span>{{ trans('table.actions') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($asset->licenseseats as $seat)
                                            @if ($seat->license)
                                                <tr>
                                                    <td><a href="{{ route('licenses.show', $seat->license->id) }}">{{ $seat->license->name }}</a></td>
                                                    <td>
                                                        @can('viewKeys', $seat->license)
                                                            <code class="single-line"><span class="js-copy-link" data-clipboard-target=".js-copy-key-{{ $seat->id }}" aria-hidden="true" data-tooltip="true" data-placement="top" title="{{ trans('general.copy_to_clipboard') }}"><span class="js-copy-key-{{ $seat->id }}">{{ $seat->license->serial }}</span></span></code>
                                                        @else
                                                            ------------
                                                        @endcan
                                                    </td>
                                                    <td>
                                                        {{ Helper::getFormattedDateObject($seat->license->expiration_date, 'date', false) }}
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('licenses.checkin', $seat->id) }}" class="btn btn-sm bg-purple hidden-print" data-tooltip="true">{{ trans('general.checkin') }}</a>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                        </tbody>
                                    </table>
                            </div><!-- /col -->
                        </div> <!-- row -->
                    </div> <!-- /.tab-pane software -->
                    @endcan

                    @can('view', \App\Models\Component::class)
                    <div class="tab-pane fade" id="components">
                        <!-- checked out assets table -->
                        <div class="row{{($asset->components->count() > 0 ) ? '' : ' hidden-print'}}">
                            <div class="col-md-12">

                                    <table class="table table-striped">
                                        <thead>
                                        <th>{{ trans('general.name') }}</th>
                                        <th>{{ trans('general.qty') }}</th>
                                        <th>{{ trans('general.purchase_cost') }}</th>
                                        <th>{{trans('admin/hardware/form.serial')}}</th>
                                        <th>{{trans('general.checkin')}}</th>
                                        <th></th>
                                        </thead>
                                        <tbody>
                                            <?php $totalCost = 0; ?>
                                        @foreach ($asset->components as $component)


                                            @if (is_null($component->deleted_at))
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('components.show', $component->id) }}">{{ $component->name }}</a>
                                                    </td>
                                                    <td>{{ $component->pivot->assigned_qty }}</td>
                                                    <td>
                                                        @if ($component->purchase_cost!='')
                                                            {{ trans('general.cost_each', ['amount' => Helper::formatCurrencyOutput($component->purchase_cost)])  }}
                                                        @endif
                                                    </td>
                                                    <td>{{ $component->serial }}</td>
                                                    <td>
                                                        <a href="{{ route('components.checkin.show', $component->pivot->id) }}" class="btn btn-sm bg-purple hidden-print" data-tooltip="true">{{ trans('general.checkin') }}</a>
                                                    </td>

                                                        <?php $totalCost = $totalCost + ($component->purchase_cost *$component->pivot->assigned_qty) ?>
                                                </tr>
                                            @endif
                                        @endforeach
                                        </tbody>

                                        <tfoot>
                                        <tr>
                                            <td colspan="2">
                                            </td>
                                            <td>{{ $totalCost }}</td>
                                        </tr>
                                        </tfoot>
                                    </table>
                            </div>
                        </div>
                    </div> <!-- /.tab-pane components -->
                    @endcan

                    @can('view', \App\Models\Asset::class)
                    <div class="tab-pane fade" id="assets">
                        <div class="row{{($asset->assignedAssets->count() > 0 ) ? '' : ' hidden-print'}}">
                            <div class="col-md-12">

                                @include('partials.asset-bulk-actions')

                                    <!-- checked out assets table -->
                                    <div class="table-responsive">

                                        <table
                                                data-columns="{{ \App\Presenters\AssetPresenter::dataTableLayout() }}"
                                                data-cookie-id-table="assetsTable"
                                                data-id-table="assetsTable"
                                                data-side-pagination="server"
                                                data-sort-order="asc"
                                                data-toolbar="#assetsBulkEditToolbar"
                                                data-bulk-button-id="#bulkAssetEditButton"
                                                data-bulk-form-id="#assetsBulkForm"
                                                id="assetsListingTable"
                                                class="table table-striped snipe-table"
                                                data-url="{{route('api.assets.index',['assigned_to' => $asset->id, 'assigned_type' => 'App\Models\Asset']) }}"
                                                data-export-options='{
                              "fileName": "export-assets-{{ str_slug($asset->name) }}-assets-{{ date('Y-m-d') }}",
                              "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                              }'>

                                        </table>
                                    </div>


                            </div><!-- /col -->
                        </div> <!-- row -->
                    </div> <!-- /.tab-pane software -->
                    @endcan


                @can('view', \App\Models\Accessory::class)
                <div class="tab-pane" id="accessories_assigned">


                    <div class="table table-responsive">

                        <h2 class="box-title" style="float:left">
                            {{ trans('general.accessories_assigned') }}
                        </h2>

                        <table
                                data-columns="{{ \App\Presenters\AssetPresenter::assignedAccessoriesDataTableLayout() }}"
                                data-cookie-id-table="accessoriesAssignedListingTable"
                                data-id-table="accessoriesAssignedListingTable"
                                data-side-pagination="server"
                                data-sort-order="asc"
                                id="accessoriesAssignedListingTable"
                                class="table table-striped snipe-table"
                                data-url="{{ route('api.assets.assigned_accessories', ['asset' => $asset]) }}"
                                data-export-options='{
                              "fileName": "export-locations-{{ str_slug($asset->name) }}-accessories-{{ date('Y-m-d') }}",
                              "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                              }'>
                        </table>

                    </div><!-- /.table-responsive -->
                </div><!-- /.tab-pane -->
                @endcan

                    <div class="tab-pane fade" id="tests">
                        <div class="mb-3 text-right">
                            @can('tests.execute')
                                <form method="POST" action="{{ route('test-runs.store', $asset->id) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">{{ trans('tests.start_new_run') }}</button>
                                </form>
                            @endcan
                        </div>
                        <div class="row">
                            @foreach ($asset->tests as $run)
                                <div class="col-md-6 col-sm-12">
                                    <div class="panel panel-default">
                                        @php
                                            $timestamp = $run->finished_at ?: $run->created_at;
                                            $passes = $run->results->where('status', 'pass')->count();
                                            $fails = $run->results->where('status', 'fail')->count();
                                            $nvts = $run->results->where('status', 'nvt')->count();
                                        @endphp
                                        <div class="panel-heading">
                                            <a data-toggle="collapse" href="#test-run-{{ $run->id }}" aria-expanded="false" aria-controls="test-run-{{ $run->id }}">
                                                {{ optional($timestamp)->format('Y-m-d H:i') }} - {{ optional($run->user)->name }}
                                            </a>
                                            <span class="pull-right">
                                                {{ $passes }} {{ trans('tests.pass') }},
                                                {{ $fails }} {{ trans('tests.fail') }}
                                                @if ($nvts)
                                                    , {{ $nvts }} {{ trans('tests.nvt') }}
                                                @endif
                                            </span>
                                        </div>
                                        <div id="test-run-{{ $run->id }}" class="panel-collapse collapse">
                                            <div class="panel-body">
                                                <ul class="list-unstyled">
                                                    @foreach ($run->results as $result)
                                                        @php
                                                            $definition = $result->attributeDefinition;
                                                            $label = $definition?->label ?? optional($result->type)->name;
                                                            $instructions = trim((string) (optional($result->type)->instructions ?: ($definition?->instructions ?? $definition?->help_text)));
                                                            $expectedDisplay = $result->expected_value;
                                                            if ($definition && $definition->datatype === \App\Models\AttributeDefinition::DATATYPE_BOOL && $expectedDisplay !== null) {
                                                                $expectedDisplay = $expectedDisplay === '1' ? __('Yes') : __('No');
                                                            }
                                                        @endphp
                                                        <li>
                                                            {{ $label }}
                                                            @if ($instructions !== '')
                                                                <x-icon type="info-circle" class="text-muted" data-tooltip="true" data-placement="top" title="{{ $instructions }}" />
                                                            @endif:
                                                            {{ trans('tests.' . $result->status) }}
                                                            @if ($expectedDisplay !== null)
                                                                <span class="text-muted">{{ __('Expected: :value', ['value' => $expectedDisplay]) }}</span>
                                                            @endif
                                                            @if ($result->note)
                                                                <span class="text-muted">{{ $result->note }}</span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                                <div class="mt-2">
                                                    @can('update', $run)
                                                        <a href="{{ route('test-results.edit', [$asset->id, $run->id]) }}" class="btn btn-default btn-sm">{{ trans('button.edit') }}</a>
                                                    @endcan
                                                    @can('delete', $run)
                                                        <form method="POST" action="{{ route('test-runs.destroy', [$asset->id, $run->id]) }}" style="display:inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-danger btn-sm" type="submit">{{ trans('button.delete') }}</button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @can('audits.view')
                            @php
                                $audits = $asset->tests->flatMap->audits
                                    ->merge($asset->tests->flatMap->results->flatMap->audits)
                                    ->sortByDesc('created_at');
                            @endphp
                            @if($audits->isNotEmpty())
                                <button class="btn btn-default mb-2" type="button" data-toggle="collapse" data-target="#test-audit-trail">
                                    {{ trans('tests.view_audit_trail') }}
                                </button>
                                <div id="test-audit-trail" class="collapse">
                                    @include('tests.partials.audit-history', ['audits' => $audits])
                                </div>
                            @endif
                        @endcan
                    </div> <!-- /.tab-pane tests -->

                    <div class="tab-pane fade" id="images">
                        @php
                            $user = auth()->user();
                            $uploadRoles = ['superuser', 'admin', 'supervisor', 'senior-refurbisher', 'refurbisher'];
                            $deleteRoles = ['superuser', 'admin', 'supervisor', 'senior-refurbisher'];
                            $canManageImages = $user && $user->can('update', $asset) && collect($uploadRoles)->contains(fn($role) => $user->hasAccess($role));
                            $canDeleteImages = $user && $user->can('update', $asset) && collect($deleteRoles)->contains(fn($role) => $user->hasAccess($role));
                        @endphp
                        <div class="row">
                            <div class="col-12 text-muted small mb-2">{{ trans('general.cover_image_notice') }}</div>

                            @forelse ($asset->images as $image)
                                <div class="col-6 col-md-3 mb-3 text-center">
                                    <a href="{{ asset('storage/'.$image->file_path) }}" target="_blank">
                                        <img src="{{ asset('storage/'.$image->file_path) }}" class="img-fluid img-thumbnail" alt="{{ $image->caption }}">
                                    </a>
                                    <div class="mt-1">

                                        @if ($canManageImages)
                                            <form method="POST" action="{{ route('asset-images.update', [$asset, $image]) }}" class="form-inline justify-content-center">
                                                @csrf
                                                @method('PUT')
                                                <input type="text" name="caption" value="{{ $image->caption }}" class="form-control form-control-sm">
                                                <button type="submit" class="btn btn-xs btn-primary ml-1">{{ trans('general.save') }}</button>
                                            </form>

                                            @if ($canDeleteImages)
                                            <form method="POST" action="{{ route('asset-images.destroy', [$asset, $image]) }}" class="mt-1" onsubmit="return confirm('{{ trans('general.delete_confirm', ['item' => trans('general.image')]) }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-danger">{{ trans('button.delete') }}</button>
                                            </form>
                                            @endif
                                        @else
                                            {{ $image->caption }}
                                        @endif

                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted">{{ trans('general.no_asset_images') }}</div>
                            @endforelse
                        </div>

                        @if ($canManageImages)
                            @if ($asset->images->count() < 30)
                                <form id="image-upload-form" method="POST" action="{{ route('asset-images.store', $asset) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div id="image-dropzone" class="well text-center" style="cursor:pointer">
                                        {{ trans('general.drag_n_drop_help') }}
                                    </div>
                                    <input type="file" id="image-input" name="image[]" class="d-none" multiple accept="image/jpeg,image/png,image/gif">
                                    <div id="image-preview" class="row mt-3"></div>
                                    <button type="submit" id="image-upload-btn" class="btn btn-primary mt-2" disabled>{{ trans('general.image_upload') }}</button>
                                </form>
                            @else
                                <div class="alert alert-info mt-3">{{ trans('general.too_many_asset_images') }}</div>
                            @endif
                        @endif

                    </div>

                    @can('view', \App\Models\Asset::class)
                    <div class="tab-pane fade" id="maintenances">
                        <div class="row{{($asset->maintenances->count() > 0 ) ? '' : ' hidden-print'}}">
                            <div class="col-md-12">

                                <!-- Asset Maintenance table -->
                                <table
                                        data-columns="{{ \App\Presenters\MaintenancesPresenter::dataTableLayout() }}"
                                        class="table table-striped snipe-table"
                                        id="MaintenancesTable"
                                        data-buttons="maintenanceButtons"
                                        data-id-table="MaintenancesTable"
                                        data-side-pagination="server"
                                        data-toolbar="#maintenance-toolbar"
                                        data-export-options='{
                                           "fileName": "export-{{ $asset->asset_tag }}-maintenances",
                                           "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                                         }'
                                        data-url="{{ route('api.maintenances.index', array('asset_id' => $asset->id)) }}"
                                        data-cookie-id-table="MaintenancesTable"
                                        data-cookie="true">
                                </table>
                            </div> <!-- /.col-md-12 -->
                        </div> <!-- /.row -->
                    </div> <!-- /.tab-pane maintenances -->
                    @endcan


                <div class="tab-pane fade" id="history">
                        <div class="row">
                            <div class="col-md-12">
                                @php
                                    $statusEvents = $asset->statusEvents()->with(['fromStatus', 'toStatus', 'user'])->get();
                                @endphp
                                @if ($statusEvents->isNotEmpty())
                                    <div class="panel panel-default">
                                        <div class="panel-heading">
                                            {{ trans('general.status_history') }}
                                        </div>
                                        <div class="panel-body">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>{{ trans('general.date') }}</th>
                                                        <th>{{ trans('general.from') }}</th>
                                                        <th>{{ trans('general.to') }}</th>
                                                        <th>{{ trans('general.performed_by') }}</th>
                                                        <th>{{ trans('general.notes') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($statusEvents as $event)
                                                        <tr>
                                                            <td>{{ Helper::getFormattedDateObject($event->created_at, 'datetime', false) }}</td>
                                                            <td>{{ optional($event->fromStatus)->name ?? trans('general.none') }}</td>
                                                            <td>{{ optional($event->toStatus)->name ?? trans('general.none') }}</td>
                                                            <td>
                                                                @if ($event->user)
                                                                    {!! link_to_route('users.show', $event->user->present()->fullName(), [$event->user->id]) !!}
                                                                @else
                                                                    {{ trans('general.system') }}
                                                                @endif
                                                            </td>
                                                            <td>{{ $event->note }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-12">
                                <table
                                        data-columns="{{ \App\Presenters\HistoryPresenter::dataTableLayout() }}"
                                        class="table table-striped snipe-table"
                                        id="assetHistory"
                                        data-id-table="assetHistory"
                                        data-side-pagination="server"
                                        data-sort-order="desc"
                                        data-sort-name="created_at"
                                        data-export-options='{
                                             "fileName": "export-asset-{{  $asset->id }}-history",
                                             "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                                           }'
                                        data-url="{{ route('api.activity.index', ['item_id' => $asset->id, 'item_type' => 'asset']) }}"
                                        data-cookie-id-table="assetHistory"
                                        data-cookie="true">
                                </table>
                            </div>
                        </div> <!-- /.row -->
                    </div> <!-- /.tab-pane history -->

                    @can('files', $asset)
                    <div class="tab-pane fade" id="files">
                        <div class="row{{ ($asset->uploads->count() > 0 ) ? '' : ' hidden-print' }}">
                            <div class="col-md-12">
                                <x-filestable object_type="assets" :object="$asset" />
                            </div> <!-- /.col-md-12 -->
                        </div> <!-- /.row -->
                    </div> <!-- /.tab-pane files -->
                    @endcan

                    @if ($asset->model)
                        @can('files', $asset->model)
                            <div class="tab-pane fade" id="modelfiles">
                                <div class="row{{ (($asset->model) && ($asset->model->uploads->count() > 0)) ? '' : ' hidden-print' }}">
                                    <div class="col-md-12">
                                        <x-filestable object_type="models" :object="$asset->model" />
                                    </div> <!-- /.col-md-12 -->
                                </div> <!-- /.row -->
                            </div> <!-- /.tab-pane files -->
                        @endcan
                    @endif
            </div><!-- /.tab-content -->
        </div><!-- nav-tabs-custom -->
    </div>

@stop
@section('moar_scripts')
    @include ('partials.bootstrap-table')
    <script>
        (function () {
            var dropzone = document.getElementById('image-dropzone');
            if (!dropzone) return;
            var input = document.getElementById('image-input');
            var preview = document.getElementById('image-preview');
            var button = document.getElementById('image-upload-btn');
            var files = [];
            var existingCount = {{ $asset->images->count() }};
            var maxSize = 5 * 1024 * 1024; // 5MB

            function updateInput() {
                var dt = new DataTransfer();
                files.forEach(function(f){ dt.items.add(f); });
                input.files = dt.files;
                button.disabled = files.length === 0;
            }

            function addFiles(selected) {
                Array.from(selected).forEach(function(file){
                    if (files.length + existingCount >= 30) {
                        alert('{{ trans('general.too_many_asset_images') }}');
                        return;
                    }
                    if (['image/jpeg','image/png','image/gif'].indexOf(file.type) === -1) {
                        alert('{{ trans('general.invalid_image_type') }}');
                        return;
                    }
                    if (file.size > maxSize) {
                        alert('{{ trans('general.image_too_large', ['size' => '5MB']) }}');
                        return;
                    }
                    files.push(file);
                    var reader = new FileReader();
                    reader.onload = function(e){
                        var col = document.createElement('div');
                        col.className = 'col-sm-3 mb-3 text-center';
                        col.innerHTML = `<img src="${e.target.result}" class="img-thumbnail"><input type="text" name="caption[]" class="form-control mt-1" placeholder="{{ trans('general.caption') }}" required>`;
                        preview.appendChild(col);
                    };
                    reader.readAsDataURL(file);
                });
                updateInput();
            }

            dropzone.addEventListener('click', function(){ input.click(); });
            dropzone.addEventListener('dragover', function(e){ e.preventDefault(); dropzone.classList.add('dropzone-over'); });
            dropzone.addEventListener('dragleave', function(){ dropzone.classList.remove('dropzone-over'); });
            dropzone.addEventListener('drop', function(e){ e.preventDefault(); dropzone.classList.remove('dropzone-over'); addFiles(e.dataTransfer.files); });
            input.addEventListener('change', function(e){ addFiles(e.target.files); });

            document.getElementById('image-upload-form').addEventListener('submit', function(e){
                var captions = preview.querySelectorAll('input[name="caption[]"]');
                for (var i = 0; i < captions.length; i++) {
                    if (!captions[i].value.trim()) {
                        e.preventDefault();
                        alert('{{ trans('general.caption_required') }}');
                        return;
                    }
                }
            });
        })();
    </script>

@stop
