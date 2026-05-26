@php($warnings = collect($warnings ?? []))

@if($warnings->isNotEmpty())
    <div class="alert alert-warning" data-testid="component-definition-hierarchy-overlap-warning">
        <strong>{{ __('Hierarchy overlap warning') }}</strong>
        <ul style="margin-bottom:0; padding-left:18px;">
            @foreach($warnings as $warning)
                <li>
                    {{ __(':parent and :child both contribute :attribute. Attached child values override parent values for calculated asset specs.', [
                        'parent' => $warning['parent_definition_name'] ?? __('Parent definition'),
                        'child' => $warning['child_expected_name'] ?? ($warning['child_definition_name'] ?? __('Child definition')),
                        'attribute' => $warning['attribute_label'] ?? __('this attribute'),
                    ]) }}
                    <span class="text-muted">
                        {{ __('Parent: :parent_value | Child: :child_value', [
                            'parent_value' => $warning['parent_value'] ?? trans('general.none'),
                            'child_value' => $warning['child_value'] ?? trans('general.none'),
                        ]) }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
