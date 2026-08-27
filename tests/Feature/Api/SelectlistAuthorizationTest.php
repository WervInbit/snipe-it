<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;

class SelectlistAuthorizationTest extends TestCase
{
    public function testApiUserWithoutRelevantMutationPermissionCannotEnumerateSelectlists(): void
    {
        $this->actingAsForApi(User::factory()->create());

        foreach ([
            'api.accessories.selectlist',
            'assets.selectlist',
            'api.consumables.selectlist',
            'api.licenses.selectlist',
            'api.users.selectlist',
        ] as $routeName) {
            $this->getJson(route($routeName))->assertForbidden();
        }
    }
}
