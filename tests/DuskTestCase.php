<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use Tests\Support\TestEnvironmentGuard;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    protected string $baseUrl = 'http://127.0.0.1:8000';

    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    public function createApplication()
    {
        $baseUrl = env('DUSK_BASE_URL', $this->baseUrl);
        $forceTls = parse_url($baseUrl, PHP_URL_SCHEME) === 'https' ? 'true' : 'false';
        $configuredForceTls = $_ENV['APP_FORCE_TLS'] ?? getenv('APP_FORCE_TLS');

        $this->syncEnv('APP_URL', $baseUrl);
        $this->syncEnv(
            'APP_FORCE_TLS',
            is_string($configuredForceTls) && $configuredForceTls !== '' ? $configuredForceTls : $forceTls
        );
        $this->syncEnv('APP_ALLOW_INSECURE_HOSTS', $_ENV['APP_ALLOW_INSECURE_HOSTS'] ?? getenv('APP_ALLOW_INSECURE_HOSTS') ?? 'false');
        $this->syncEnv('APP_ENV', 'testing');
        TestEnvironmentGuard::prepareDuskProcess(realpath(__DIR__ . '/../'));

        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        TestEnvironmentGuard::assertBootedDuskApplication($app);

        $config = $app->make('config');
        $config->set('app.url', $baseUrl);
        $config->set('app.asset_url', $baseUrl);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $baseUrl = env('APP_URL', $this->baseUrl);
        $this->baseUrl = $baseUrl;

        config(['app.env' => 'testing']);
        config(['app.url' => $baseUrl]);
        URL::forceRootUrl($baseUrl);
        URL::forceScheme(parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https');

        $this->artisan('view:clear');
        TestEnvironmentGuard::assertBootedDuskApplication($this->app);
        $this->artisan('migrate:fresh', ['--seed' => true]);
    }

    protected function driver(): RemoteWebDriver
    {
        $arguments = collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--disable-dev-shm-usage',
            '--no-sandbox',
            '--ignore-certificate-errors',
            '--allow-insecure-localhost',
            '--use-fake-ui-for-media-stream',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        });

        $options = (new ChromeOptions)->addArguments($arguments->all());

        if ($binary = env('DUSK_CHROME_BINARY')) {
            $options->setBinary($binary);
        }

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    private function syncEnv(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
