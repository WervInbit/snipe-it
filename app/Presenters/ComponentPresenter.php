<?php

namespace App\Presenters;

class ComponentPresenter extends Presenter
{
    public static function dataTableLayout()
    {
        $layout = [
            [
                'field' => 'id',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.id'),
                'visible' => false,
            ],
            [
                'field' => 'component_tag',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('general.tag'),
                'visible' => true,
            ],
            [
                'field' => 'name',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('general.name'),
                'visible' => true,
                'formatter' => 'componentsLinkFormatter',
            ],
            [
                'field' => 'serial',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/hardware/form.serial'),
                'visible' => true,
            ],
            [
                'field' => 'status',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('general.status'),
                'visible' => true,
            ],
            [
                'field' => 'installed_as',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('general.location'),
                'visible' => false,
            ],
            [
                'field' => 'category',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('general.category'),
                'visible' => true,
                'formatter' => 'categoriesLinkObjFormatter',
            ],
            [
                'field' => 'manufacturer',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('general.manufacturer'),
                'visible' => false,
                'formatter' => 'manufacturersLinkObjFormatter',
            ],
            [
                'field' => 'source_type',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('general.type'),
                'visible' => true,
            ],
            [
                'field' => 'source_asset',
                'searchable' => false,
                'sortable' => false,
                'title' => trans('general.source'),
                'visible' => true,
                'formatter' => 'hardwareLinkObjFormatter',
            ],
            [
                'field' => 'current_asset',
                'searchable' => false,
                'sortable' => false,
                'title' => trans('general.asset'),
                'visible' => true,
                'formatter' => 'hardwareLinkObjFormatter',
            ],
            [
                'field' => 'current_location',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('general.location'),
                'visible' => true,
            ],
            [
                'field' => 'held_by',
                'searchable' => false,
                'sortable' => false,
                'title' => trans('general.user'),
                'visible' => true,
                'formatter' => 'usersLinkObjFormatter',
            ],
            [
                'field' => 'updated_at',
                'searchable' => false,
                'sortable' => true,
                'visible' => true,
                'title' => trans('general.updated_at'),
                'formatter' => 'dateDisplayFormatter',
            ],
        ];

        return json_encode($layout);
    }

    public function nameUrl()
    {
        return (string) link_to_route('components.show', e($this->name), $this->id);
    }

    public function viewUrl()
    {
        return route('components.show', $this->id);
    }
}
