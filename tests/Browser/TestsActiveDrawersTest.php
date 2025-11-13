<?php

namespace Tests\Browser;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TestsActiveDrawersTest extends DuskTestCase
{
    public function test_note_and_photo_drawers_toggle(): void
    {
        $baseUrl = rtrim(config('app.url'), '/');

        if (parse_url($baseUrl, PHP_URL_HOST) !== 'dev.snipe.inbit') {
            $this->markTestIncomplete('Configure APP_URL/DUSK_BASE_URL to https://dev.snipe.inbit for interactive tests.');
        }

        $user = User::factory()->superuser()->create([
            'email' => 'dusk-tests-active@example.test',
            'username' => 'dusk-tests-active',
        ]);

        $asset = Asset::query()->findOrFail(2);

        $testType = TestType::factory()->create([
            'name' => 'Camera focus',
            'instructions' => 'Zorg dat het beeld scherp is.',
        ]);

        $run = TestRun::factory()->for($asset)->for($user)->create([
            'finished_at' => null,
        ]);

        $result = TestResult::factory()
            ->for($run)
            ->for($testType, 'type')
            ->create([
                'status' => TestResult::STATUS_NVT,
                'note' => '',
                'photo_path' => null,
            ]);

        $cardSelector = sprintf('[data-result-id="%d"]', $result->id);
        $noteSelector = "#note-{$result->id}";
        $photoSelector = "#photos-{$result->id}";
        $photoInputSelector = "{$cardSelector} input[data-action=\"upload-photo\"]";
        $instructionSelector = "#instructions-{$result->id}";
        $instructionToggle = "{$cardSelector} [data-action=\"toggle-help\"]";
        $noteIndicatorSelector = "{$cardSelector} [data-note-indicator]";
        $photoIndicatorSelector = "{$cardSelector} [data-photo-indicator]";

        $photoDirectory = storage_path('framework/testing');
        if (!is_dir($photoDirectory)) {
            mkdir($photoDirectory, 0755, true);
        }
        $photoPathOne = $photoDirectory . '/dusk-photo-1.jpg';
        $photoPathTwo = $photoDirectory . '/dusk-photo-2.jpg';
        $tinyImage = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PBqGiQAAAABJRU5ErkJggg==');
        if (!file_exists($photoPathOne)) {
            file_put_contents($photoPathOne, $tinyImage);
        }
        if (!file_exists($photoPathTwo)) {
            file_put_contents($photoPathTwo, $tinyImage);
        }

        $passSelector = "{$cardSelector} [data-action=\"set-pass\"]";
        $failSelector = "{$cardSelector} [data-action=\"set-fail\"]";

        $this->browse(function (Browser $browser) use (
            $user,
            $asset,
            $cardSelector,
            $noteSelector,
            $photoSelector,
            $photoInputSelector,
            $photoPathOne,
            $photoPathTwo,
            $baseUrl,
            $passSelector,
            $failSelector,
            $instructionSelector,
            $instructionToggle,
            $noteIndicatorSelector,
            $photoIndicatorSelector
        ) {
            $waitForAttribute = function (string $selector, string $attribute, string $expected, int $seconds = 5) use ($browser) {
                $browser->waitUsing($seconds, 200, function () use ($browser, $selector, $attribute, $expected) {
                    $value = $browser->script("var el = document.querySelector('{$selector}'); return el ? el.getAttribute('{$attribute}') : null;");
                    return ($value[0] ?? null) === $expected;
                }, "Timeout waiting for {$selector} {$attribute}={$expected}");
            };

            $waitForClassState = function (string $selector, string $class, bool $shouldHave = true, int $seconds = 8) use ($browser) {
                $browser->waitUsing($seconds, 200, function () use ($browser, $selector, $class, $shouldHave) {
                    $value = $browser->script("var el = document.querySelector('{$selector}'); return el ? el.classList.contains('{$class}') : false;");
                    return ($value[0] ?? false) === $shouldHave;
                }, "Timeout waiting for {$selector} class '{$class}' state");
            };

            $waitForCount = function (string $selector, int $expected, int $seconds = 8) use ($browser) {
                $browser->waitUsing($seconds, 200, function () use ($browser, $selector, $expected) {
                    $value = $browser->script("return document.querySelectorAll('{$selector}').length;");
                    return ($value[0] ?? 0) === $expected;
                }, "Timeout waiting for {$selector} count {$expected}");
            };

            $waitForIndicator = function (string $selector, bool $active, int $seconds = 5) use ($browser) {
                $browser->waitUsing($seconds, 200, function () use ($browser, $selector, $active) {
                    $value = $browser->script("var el = document.querySelector('{$selector}'); return el ? el.classList.contains('is-active') : false;");
                    return ($value[0] ?? false) === $active;
                }, "Timeout waiting for {$selector} indicator state");
            };

            $browser->loginAs($user)
                ->visit("{$baseUrl}/hardware/{$asset->id}/tests/active")
                ->waitFor($cardSelector, 15)
                ->waitUntil('return window.TestsActiveUIBootstrapped === true;', 10);

            $browser->click($instructionToggle);
            $waitForClassState($instructionSelector, 'show', true);
            $browser->click($instructionToggle);
            $waitForClassState($instructionSelector, 'show', false);

            // Test pass/fail toggles (including deselect)
            $browser->click($passSelector);
            $waitForAttribute($passSelector, 'aria-pressed', 'true');
            $browser->click($passSelector);
            $waitForAttribute($passSelector, 'aria-pressed', 'false');
            $browser->click($failSelector);
            $waitForAttribute($failSelector, 'aria-pressed', 'true');

            $browser->click("{$cardSelector} [data-action=\"toggle-note\"]");
            $waitForClassState($noteSelector, 'show', true);

            $browser->type("{$noteSelector} textarea", 'Dusk note entry')->pause(800);
            $waitForIndicator($noteIndicatorSelector, true);

            $browser->click("{$cardSelector} [data-action=\"toggle-note\"]");
            $waitForClassState($noteSelector, 'show', false);

            $browser->click("{$cardSelector} [data-action=\"toggle-photos\"]");
            $waitForClassState($photoSelector, 'show', true);

            $browser->attach($photoInputSelector, $photoPathOne, 'dusk-photo-1.jpg');
            $waitForCount("{$photoSelector} [data-photo-node=\"true\"]", 1);
            $waitForIndicator($photoIndicatorSelector, true);

            $browser->attach($photoInputSelector, $photoPathTwo, 'dusk-photo-2.jpg');
            $waitForCount("{$photoSelector} [data-photo-node=\"true\"]", 2);

            $browser->click("{$photoSelector} img[data-photo-id]")
                ->waitFor('#photoViewerModal.show', 5)
                ->press('#photoViewerModal .btn-close');
            $waitForClassState('#photoViewerModal', 'show', false, 5);

            // Remove first photo
            $browser->click("{$photoSelector} [data-action=\"confirm-remove-photo\"]")
                ->waitFor('#photoDeleteModal.show', 5)
                ->press('#confirmPhotoDeleteBtn');
            $waitForClassState('#photoDeleteModal', 'show', false, 5);
            $waitForCount("{$photoSelector} [data-photo-node=\"true\"]", 1);

            // Remove remaining photo
            $browser->click("{$photoSelector} [data-action=\"confirm-remove-photo\"]")
                ->waitFor('#photoDeleteModal.show', 5)
                ->press('#confirmPhotoDeleteBtn');
            $waitForClassState('#photoDeleteModal', 'show', false, 5);
            $waitForCount("{$photoSelector} [data-photo-node=\"true\"]", 0);
            $waitForIndicator($photoIndicatorSelector, false);
        });
    }
}
