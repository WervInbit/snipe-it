<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class FormSelect
{
    public static function countries(
        string $name = 'country',
        mixed $selected = null,
        ?string $class = null,
        ?string $id = null,
    ): HtmlString {
        $countries = trans('localizations.countries');
        $countries = is_array($countries) ? $countries : [];
        $placeholder = trans('localizations.select_country');
        $options = ['' => $placeholder];

        foreach ($countries as $abbreviation => $country) {
            $key = $abbreviation === '' ? '' : strtoupper((string) $abbreviation);
            $options[$key] = $country;
        }

        $selectedValue = is_null($selected) ? '' : (string) $selected;

        if ($selectedValue !== '' && ! array_key_exists($selectedValue, $options)) {
            $options[$selectedValue] = $selectedValue.' *';
        }

        return self::select(
            name: $name,
            options: $options,
            selected: $selectedValue,
            class: $class,
            id: $id,
            style: 'width:100%',
            attributes: [
                'data-placeholder' => $placeholder,
                'data-allow-clear' => 'true',
                'data-tags' => 'true',
            ],
        );
    }

    public static function dateDisplayFormat(
        string $name = 'date_display_format',
        mixed $selected = null,
        ?string $class = null,
    ): HtmlString {
        $options = [];

        foreach ([
            'Y-m-d',
            'D M d, Y',
            'M j, Y',
            'd M, Y',
            'm/d/Y',
            'n/d/y',
            'd/m/Y',
            'd.m.Y',
            'Y.m.d.',
        ] as $format) {
            $options[$format] = Carbon::today()->format($format);
        }

        return self::select($name, $options, $selected, $class, style: 'min-width:100%');
    }

    public static function timeDisplayFormat(
        string $name = 'time_display_format',
        mixed $selected = null,
        ?string $class = null,
    ): HtmlString {
        $time = Carbon::createFromTime(14, 0);
        $options = [];

        foreach (['g:iA', 'h:iA', 'H:i'] as $format) {
            $options[$format] = $time->format($format);
        }

        return self::select($name, $options, $selected, $class, style: 'min-width:150px');
    }

    public static function digitSeparator(
        string $name = 'digit_separator',
        mixed $selected = null,
        ?string $class = null,
    ): HtmlString {
        return self::select(
            $name,
            [
                '1,234.56' => '1,234.56',
                '1.234,56' => '1.234,56',
            ],
            $selected,
            $class,
            style: 'min-width:120px',
        );
    }

    public static function nameDisplayFormat(
        string $name = 'name_display_format',
        mixed $selected = null,
        ?string $class = null,
    ): HtmlString {
        return self::select(
            $name,
            [
                'first_last' => trans('general.firstname_lastname_display'),
                'last_first' => trans('general.lastname_firstname_display'),
            ],
            $selected,
            $class,
            style: 'width: 100%',
        );
    }

    public static function emailFormat(
        string $name = 'email_format',
        mixed $selected = null,
        ?string $class = null,
    ): HtmlString {
        return self::identityFormat($name, $selected, $class, 'email_formats');
    }

    public static function usernameFormat(
        string $name = 'username_format',
        mixed $selected = null,
        ?string $class = null,
    ): HtmlString {
        return self::identityFormat($name, $selected, $class, 'username_formats');
    }

    private static function identityFormat(
        string $name,
        mixed $selected,
        ?string $class,
        string $translationGroup,
    ): HtmlString {
        $translationPrefix = 'admin/settings/general.'.$translationGroup.'.';

        return self::select(
            $name,
            [
                'firstname.lastname' => trans($translationPrefix.'firstname_lastname_format'),
                'firstname' => trans($translationPrefix.'first_name_format'),
                'lastname' => trans($translationPrefix.'last_name_format'),
                'filastname' => trans($translationPrefix.'filastname_format'),
                'lastnamefirstinitial' => trans($translationPrefix.'lastnamefirstinitial_format'),
                'firstname_lastname' => trans($translationPrefix.'firstname_lastname_underscore_format'),
                'firstinitial.lastname' => trans($translationPrefix.'firstinitial_lastname'),
                'lastname_firstinitial' => trans($translationPrefix.'lastname_firstinitial'),
                'lastname.firstinitial' => trans($translationPrefix.'lastname_dot_firstinitial_format'),
                'firstnamelastname' => trans($translationPrefix.'firstnamelastname'),
                'firstnamelastinitial' => trans($translationPrefix.'firstnamelastinitial'),
                'lastname.firstname' => trans($translationPrefix.'lastnamefirstname'),
            ],
            $selected,
            $class,
            style: 'width: 100%',
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $attributes
     */
    private static function select(
        string $name,
        array $options,
        mixed $selected,
        ?string $class = null,
        ?string $id = null,
        ?string $style = null,
        array $attributes = [],
    ): HtmlString {
        $selectAttributes = [
            'name' => $name,
            'class' => $class,
            'id' => $id,
            'style' => $style,
            'aria-label' => $name,
            ...$attributes,
        ];
        $html = '<select'.self::attributes($selectAttributes).'>';
        $selectedValue = is_null($selected) ? '' : (string) $selected;

        foreach ($options as $value => $label) {
            $value = (string) $value;
            $isSelected = hash_equals($selectedValue, $value);
            $optionAttributes = [
                'value' => $value,
                'role' => 'option',
                'aria-selected' => $isSelected ? 'true' : 'false',
                'selected' => $isSelected ? 'selected' : null,
            ];

            $html .= '<option'.self::attributes($optionAttributes).'>'
                .self::escape($label)
                .'</option>';
        }

        return new HtmlString($html.'</select>');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            if (is_null($value)) {
                continue;
            }

            $html .= ' '.self::escape($name).'="'.self::escape($value).'"';
        }

        return $html;
    }

    private static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}
