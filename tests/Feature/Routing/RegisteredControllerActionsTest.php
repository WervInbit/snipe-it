<?php

namespace Tests\Feature\Routing;

use App\Http\Controllers\Api\PredefinedKitsController;
use Illuminate\Routing\Route;
use Tests\TestCase;

class RegisteredControllerActionsTest extends TestCase
{
    public function testEveryRegisteredControllerActionIsCallable(): void
    {
        $missingActions = [];

        /** @var Route $route */
        foreach (app('router')->getRoutes() as $route) {
            $action = $route->getActionName();

            if ($action === 'Closure') {
                continue;
            }

            if (! str_contains($action, '@')) {
                if (! class_exists($action) || ! method_exists($action, '__invoke')) {
                    $missingActions[] = "Route [{$route->uri()}] references non-invokable action [{$action}].";
                }

                continue;
            }

            [$controller, $method] = explode('@', $action, 2);

            if (! class_exists($controller) || ! method_exists($controller, $method)) {
                $missingActions[] = "Route [{$route->uri()}] references missing action [{$action}].";
            }
        }

        $this->assertSame([], $missingActions, implode(PHP_EOL, $missingActions));
    }

    public function testKitModelRoutesReferenceTheImplementedControllerActions(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertSame(
            PredefinedKitsController::class . '@updateModel',
            $routes->getByName('api.kits.models.update')->getActionName()
        );
        $this->assertSame(
            PredefinedKitsController::class . '@detachModel',
            $routes->getByName('api.kits.models.destroy')->getActionName()
        );
    }

    public function testKnownRouteNamesAreDeclaredOnlyOnce(): void
    {
        $apiRoutes = file_get_contents(base_path('routes/api.php'));
        $webRoutes = file_get_contents(base_path('routes/web.php'));

        $this->assertSame(1, substr_count($apiRoutes, "->name('api.assets.put-update')"));
        $this->assertSame(1, substr_count($apiRoutes, "->name('api.statuslabels.selectlist')"));
        $this->assertSame(1, substr_count($webRoutes, "->name('password.email')"));
    }
}
