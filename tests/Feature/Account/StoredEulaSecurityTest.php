<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Tests\TestCase;

class StoredEulaSecurityTest extends TestCase
{
    public function testMissingStoredEulaLogReturnsAControlledResponse(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.storedeula.download', 'missing.pdf'))
            ->assertRedirect(route('account'))
            ->assertSessionHas('error');
    }
}
