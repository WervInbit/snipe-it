<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ForkDocumentationBoundaryTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = realpath(__DIR__ . '/../..');
    }

    public function testForkApiDocumentDefinesTheCompatibilityBoundary(): void
    {
        $documentation = file_get_contents($this->basePath . '/docs/api-compatibility.md');

        $this->assertStringContainsString('Inbit Device Refurbishment Platform fork', $documentation);
        $this->assertStringContainsString('official Snipe-IT documentation is not the contract', $documentation);
        $this->assertStringContainsString('php artisan route:list --path=api/v1', $documentation);
        $this->assertStringContainsString('does not currently publish an exhaustive OpenAPI schema', $documentation);
        $this->assertStringContainsString('V1.0.0 designates the exact application revision', $documentation);
        $this->assertStringContainsString('current branch is post-V1 development', $documentation);
    }

    public function testReleaseFacingHelpDoesNotLinkToTheUpstreamDocumentationContract(): void
    {
        $paths = [
            'routes/api.php',
            'resources/views/account/api.blade.php',
            'resources/views/livewire/importer.blade.php',
            'resources/views/statuslabels/index.blade.php',
            'resources/views/statuslabels/edit.blade.php',
            'resources/views/settings/labels.blade.php',
            'config/mail.php',
            'app/Http/Controllers/Controller.php',
            'app/Policies/SnipePermissionsPolicy.php',
        ];

        foreach ($this->translationFiles() as $translationFile) {
            $paths[] = $translationFile;
        }

        foreach ($paths as $path) {
            $contents = file_get_contents($this->basePath . '/' . $path);

            $this->assertStringNotContainsString(
                'snipe-it.readme.io',
                $contents,
                $path . ' must not present upstream documentation as this fork\'s contract.',
            );
        }
    }

    public function testLocalHelpReferencesResolveThroughTheForkDocumentationRoute(): void
    {
        $webRoutes = file_get_contents($this->basePath . '/routes/web.php');

        $this->assertStringContainsString("->name('help.api-compatibility')", $webRoutes);

        $helpViews = [
            'resources/views/account/api.blade.php',
            'resources/views/livewire/importer.blade.php',
            'resources/views/statuslabels/index.blade.php',
            'resources/views/statuslabels/edit.blade.php',
            'resources/views/settings/labels.blade.php',
        ];

        foreach ($helpViews as $view) {
            $contents = file_get_contents($this->basePath . '/' . $view);

            $this->assertStringContainsString("route('help.api-compatibility')", $contents, $view);
        }
    }

    public function testDependabotTargetsTheRepositoryDefaultBranch(): void
    {
        $dependabot = file_get_contents($this->basePath . '/.github/dependabot.yml');

        $this->assertStringContainsString('target-branch: "master"', $dependabot);
        $this->assertStringNotContainsString('target-branch: "develop"', $dependabot);
    }

    public function testRootContributorDocumentsUseForkContractsAndResolvableInternalLinks(): void
    {
        $documents = [
            'README.md',
            'CONTRIBUTING.md',
            'SECURITY.md',
            'TESTING.md',
        ];

        foreach ($documents as $document) {
            $contents = file_get_contents($this->basePath . '/' . $document);

            $this->assertStringContainsString(
                'docs/v1-release-readiness-status-2026-09-03.md',
                $contents,
                "{$document} must point readers to the current release status.",
            );
            $this->assertStringNotContainsString(
                'snipe-it.readme.io',
                $contents,
                "{$document} must not present upstream documentation as this fork's contract.",
            );
            $this->assertStringNotContainsString(
                'security@snipeitapp.com',
                $contents,
                "{$document} must not direct fork reports to the upstream security address.",
            );
            $this->assertStringNotContainsString(
                'github.com/grokability/snipe-it/issues',
                $contents,
                "{$document} must not direct fork work to the upstream issue tracker.",
            );

            preg_match_all('/!?\[[^\]]*]\(([^)]+)\)/', $contents, $matches);

            foreach ($matches[1] as $target) {
                $target = trim($target);

                if (preg_match('/^(?:https?:\/\/|mailto:|#)/i', $target) === 1) {
                    continue;
                }

                $path = explode('#', $target, 2)[0];
                $resolvedPath = realpath(dirname($this->basePath . '/' . $document) . '/' . $path);

                $this->assertNotFalse(
                    $resolvedPath,
                    "{$document} contains a broken internal link: {$target}",
                );
            }
        }
    }

    public function testRuntimeConfigurationPlaceholdersUseTheForkApplicationName(): void
    {
        $branding = file_get_contents($this->basePath . '/resources/views/settings/branding.blade.php');
        $webhook = file_get_contents($this->basePath . '/resources/views/livewire/slack-settings-form.blade.php');

        $this->assertSame(2, substr_count($branding, 'placeholder="{{ config(\'app.name\') }}"'));
        $this->assertStringNotContainsString('Snipe-IT Asset Management', $branding);
        $this->assertStringContainsString(
            'placeholder="{{ config(\'app.name\') }} Bot"',
            $webhook,
        );
        $this->assertStringNotContainsString('placeholder="Snipe-Bot"', $webhook);
    }

    public function testRuntimeIdentityDefaultsDoNotFallBackToTheUpstreamProductName(): void
    {
        $settingsController = file_get_contents(
            $this->basePath . '/app/Http/Controllers/SettingsController.php',
        );
        $demoReset = file_get_contents(
            $this->basePath . '/app/Console/Commands/ResetDemoSettings.php',
        );
        $settingModel = file_get_contents($this->basePath . '/app/Models/Setting.php');

        $this->assertStringContainsString(
            "input('site_name', config('app.name'))",
            $settingsController,
        );
        $this->assertStringNotContainsString("input('site_name', 'Snipe-IT')", $settingsController);
        $this->assertStringContainsString("config('app.name') . ' Demo'", $demoReset);
        $this->assertStringNotContainsString('Snipe-IT Asset Management Demo', $demoReset);
        $this->assertStringContainsString('Reset the fork demo settings to safe defaults.', $demoReset);
        $this->assertStringContainsString("'demo@example.invalid'", $demoReset);
        $this->assertStringContainsString("'example.invalid'", $demoReset);
        $this->assertStringNotContainsString('snipe-logo', $demoReset);
        $this->assertStringNotContainsString('service@snipe-it.io', $demoReset);
        $this->assertStringContainsString('function webhookBotName(): string', $settingModel);

        foreach (glob($this->basePath . '/app/Notifications/*.php') as $notification) {
            $this->assertStringNotContainsString(
                'Snipe-Bot',
                file_get_contents($notification),
                basename($notification),
            );
        }

        foreach (['en-US', 'nl-NL'] as $locale) {
            $mail = file_get_contents($this->basePath . "/resources/lang/{$locale}/mail.php");

            $this->assertStringNotContainsString(
                'Snipe-IT',
                $mail,
                "{$locale}/mail.php must present the fork as the running product.",
            );
        }
    }

    public function testGeneratedIdentityAndOperatorTextUseTheForkBoundary(): void
    {
        $loginController = file_get_contents(
            $this->basePath . '/app/Http/Controllers/Auth/LoginController.php',
        );
        $samlRequest = file_get_contents(
            $this->basePath . '/app/Http/Requests/SettingsSamlRequest.php',
        );
        $label = file_get_contents($this->basePath . '/app/View/Label.php');

        $this->assertStringContainsString('issuer=%s&period=30', $loginController);
        $this->assertGreaterThanOrEqual(2, substr_count($loginController, 'urlencode($issuer)'));
        $this->assertStringNotContainsString('issuer=Snipe-IT', $loginController);
        $this->assertStringContainsString("'organizationName' => \$certificateIdentity", $samlRequest);
        $this->assertStringContainsString("'commonName' => \$certificateIdentity", $samlRequest);
        $this->assertStringNotContainsString("'organizationName' => 'Snipe-IT'", $samlRequest);
        $this->assertStringContainsString('setCreator($creator', $label);
        $this->assertStringNotContainsString("setCreator('Snipe-IT')", $label);

        foreach (['en-US', 'nl-NL'] as $locale) {
            $settingsCopy = file_get_contents(
                $this->basePath . "/resources/lang/{$locale}/admin/settings/general.php",
            );
            $generalCopy = file_get_contents(
                $this->basePath . "/resources/lang/{$locale}/general.php",
            );

            $this->assertStringNotContainsString(
                'Snipe-IT',
                $settingsCopy,
                "{$locale} settings copy must present the fork as the running product.",
            );
            $this->assertStringContainsString("'footer_credit'", $generalCopy);
            $this->assertStringContainsString('https://snipeitapp.com', $generalCopy);
        }

        $operatorCommands = [
            'DisableLDAP.php',
            'DisableSAML.php',
            'FixDoubleEscape.php',
            'GeneratePersonalAccessToken.php',
            'LdapTroubleshooter.php',
            'PaveIt.php',
            'ResetDemoSettings.php',
        ];

        foreach ($operatorCommands as $command) {
            $contents = file_get_contents($this->basePath . '/app/Console/Commands/' . $command);

            $this->assertStringNotContainsString(
                'Snipe-IT',
                $contents,
                "{$command} must not present Snipe-IT as the running application.",
            );
        }

        $restore = file_get_contents(
            $this->basePath . '/app/Console/Commands/RestoreFromBackup.php',
        );
        $this->assertStringContainsString(
            'Restore from a previously created application backup file',
            $restore,
        );
    }

    /**
     * @return list<string>
     */
    private function translationFiles(): array
    {
        $translationRoot = $this->basePath . '/resources/lang';
        $paths = [];
        $translationPaths = [
            'account/general.php',
            'admin/settings/general.php',
            'admin/statuslabels/table.php',
            'general.php',
        ];

        foreach (glob($translationRoot . '/*', GLOB_ONLYDIR) as $localeDirectory) {
            foreach ($translationPaths as $relativePath) {
                $translationFile = $localeDirectory . '/' . $relativePath;

                if (is_file($translationFile)) {
                    $paths[] = str_replace(
                        '\\',
                        '/',
                        substr($translationFile, strlen($this->basePath) + 1),
                    );
                }
            }
        }

        return $paths;
    }
}
