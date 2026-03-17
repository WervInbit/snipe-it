@php
    $images = $modelNumber->images()->get();
@endphp

<div class="row" id="model-number-images">
    <div class="col-lg-8 col-lg-offset-2 col-md-10 col-md-offset-1 col-sm-12 col-sm-offset-0">
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ __('Model Number Images') }}</h2>
            </div>
            <div class="box-body">
                <p class="text-muted">
                    {{ __('These images are used as defaults for assets on this model number when asset-level override is disabled.') }}
                </p>

                <form method="POST"
                      action="{{ route('model_numbers.images.store', $modelNumber) }}"
                      enctype="multipart/form-data"
                      class="form-horizontal">
                    @csrf
                    <div class="form-group{{ $errors->has('image') ? ' has-error' : '' }}">
                        <label class="col-md-3 control-label" for="model_number_image_upload">{{ __('Image') }}</label>
                        <div class="col-md-7">
                            <input id="model_number_image_upload" type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/gif" required>
                            {!! $errors->first('image', '<span class="alert-msg">:message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('caption') ? ' has-error' : '' }}">
                        <label class="col-md-3 control-label" for="model_number_image_caption">{{ __('Caption') }}</label>
                        <div class="col-md-7">
                            <input id="model_number_image_caption" type="text" name="caption" class="form-control" value="{{ old('caption') }}">
                            {!! $errors->first('caption', '<span class="alert-msg">:message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('sort_order') ? ' has-error' : '' }}">
                        <label class="col-md-3 control-label" for="model_number_image_sort">{{ __('Order') }}</label>
                        <div class="col-md-3">
                            <input id="model_number_image_sort" type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order') }}">
                            <span class="help-block">{{ __('Lower number appears first.') }}</span>
                            {!! $errors->first('sort_order', '<span class="alert-msg">:message</span>') !!}
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Upload Image') }}
                            </button>
                        </div>
                    </div>
                </form>

                <hr>

                @if ($images->isEmpty())
                    <p class="text-muted">{{ __('No images configured for this model number.') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Preview') }}</th>
                                    <th>{{ __('Caption') }}</th>
                                    <th style="width: 120px;">{{ __('Order') }}</th>
                                    <th>{{ __('Replace Image') }}</th>
                                    <th style="width: 180px;">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($images as $image)
                                    <tr>
                                        <td>
                                            <img src="{{ Storage::disk('public')->url($image->file_path) }}"
                                                 alt="{{ $image->caption ?: __('Model Number Image') }}"
                                                 style="max-height: 72px; max-width: 120px;"
                                                 class="img-thumbnail">
                                        </td>
                                        <td colspan="3">
                                            <form method="POST"
                                                  action="{{ route('model_numbers.images.update', [$modelNumber, $image]) }}"
                                                  enctype="multipart/form-data"
                                                  class="form-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="text"
                                                       name="caption"
                                                       class="form-control input-sm"
                                                       value="{{ $image->caption }}"
                                                       style="min-width: 220px; margin-right: 8px;">
                                                <input type="number"
                                                       name="sort_order"
                                                       min="0"
                                                       class="form-control input-sm"
                                                       value="{{ $image->sort_order }}"
                                                       style="width: 90px; margin-right: 8px;">
                                                <input type="file"
                                                       name="image"
                                                       class="form-control input-sm"
                                                       accept="image/jpeg,image/png,image/gif"
                                                       style="margin-right: 8px;">
                                                <button type="submit" class="btn btn-default btn-sm">{{ __('Save') }}</button>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="POST"
                                                  action="{{ route('model_numbers.images.destroy', [$modelNumber, $image]) }}"
                                                  onsubmit="return confirm('{{ __('Delete this image?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">{{ trans('button.delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
