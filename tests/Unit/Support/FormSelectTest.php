<?php

namespace Tests\Unit\Support;

use App\Support\FormSelect;
use Tests\TestCase;

class FormSelectTest extends TestCase
{
    public function test_country_select_renders_valid_attributes_and_selected_value(): void
    {
        $html = FormSelect::countries('country', 'NL', 'select2 country', 'modal-country')->toHtml();

        $this->assertStringContainsString('name="country"', $html);
        $this->assertStringContainsString('class="select2 country"', $html);
        $this->assertStringContainsString('id="modal-country"', $html);
        $this->assertStringContainsString('<option value=""', $html);
        $this->assertMatchesRegularExpression(
            '/<option[^>]+value="NL"[^>]+selected="selected"[^>]*>/',
            $html,
        );
    }

    public function test_country_select_escapes_a_custom_stored_value(): void
    {
        $html = FormSelect::countries(selected: '\"><script>alert(1)</script>')->toHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_format_selects_preserve_the_existing_options_and_selection(): void
    {
        $this->assertStringContainsString(
            'value="d/m/Y" role="option" aria-selected="true" selected="selected"',
            FormSelect::dateDisplayFormat(selected: 'd/m/Y')->toHtml(),
        );
        $this->assertStringContainsString(
            'value="H:i" role="option" aria-selected="true" selected="selected"',
            FormSelect::timeDisplayFormat(selected: 'H:i')->toHtml(),
        );
        $this->assertStringContainsString(
            'value="1.234,56" role="option" aria-selected="true" selected="selected"',
            FormSelect::digitSeparator(selected: '1.234,56')->toHtml(),
        );
        $this->assertStringContainsString(
            'value="last_first" role="option" aria-selected="true" selected="selected"',
            FormSelect::nameDisplayFormat(selected: 'last_first')->toHtml(),
        );
        $this->assertStringContainsString(
            'value="filastname" role="option" aria-selected="true" selected="selected"',
            FormSelect::emailFormat(selected: 'filastname')->toHtml(),
        );
        $this->assertStringContainsString(
            'value="firstname.lastname" role="option" aria-selected="true" selected="selected"',
            FormSelect::usernameFormat(selected: 'firstname.lastname')->toHtml(),
        );
    }

    public function test_abandoned_collective_html_layer_is_no_longer_configured(): void
    {
        $composer = json_decode(
            file_get_contents(base_path('composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $configuration = file_get_contents(config_path('app.php'));
        $views = implode(
            "\n",
            array_map(
                static fn (string $path): string => file_get_contents($path),
                [
                    resource_path('views/users/edit.blade.php'),
                    resource_path('views/setup/user.blade.php'),
                    resource_path('views/modals/location.blade.php'),
                    resource_path('views/settings/localization.blade.php'),
                    resource_path('views/settings/general.blade.php'),
                    resource_path('views/partials/forms/edit/address.blade.php'),
                ],
            ),
        );

        $this->assertArrayNotHasKey('laravelcollective/html', $composer['require']);
        $this->assertStringNotContainsString('Collective\\Html', $configuration);
        $this->assertStringNotContainsString('Form::', $views);
    }
}
