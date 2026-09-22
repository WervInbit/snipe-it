<?php

namespace Tests\Feature\Users;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordBaselineTest extends TestCase
{
    public function test_local_password_change_requires_uppercase_and_number(): void
    {
        Setting::getSettings()->forceFill(['pwd_secure_min' => 8, 'pwd_secure_complexity' => null])->save();
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->actingAs($user)
            ->from(route('account.password.index'))
            ->post(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'lowercase1',
                'password_confirmation' => 'lowercase1',
            ])
            ->assertSessionHasErrors('password');

        $this->post(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'UppercaseOnly',
            'password_confirmation' => 'UppercaseOnly',
        ])->assertSessionHasErrors('password');
    }

    public function test_local_password_change_accepts_eight_characters_uppercase_and_number(): void
    {
        Setting::getSettings()->forceFill(['pwd_secure_min' => 8, 'pwd_secure_complexity' => null])->save();
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->actingAs($user)
            ->post(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'Abcdefg1',
                'password_confirmation' => 'Abcdefg1',
            ])
            ->assertRedirect(route('account'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Abcdefg1', $user->fresh()->password));
    }
}
