<?php

namespace Tests\Unit\Models\User;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VerifyExactUsernameMatchTest extends TestCase
{
    public function testExactUsernameReturnsResolvedUser(): void
    {
        $user = new User(['username' => 'snipeitreport3']);

        $this->assertSame(
            $user,
            User::verifyExactUsernameMatch($user, 'snipeitreport3')
        );
    }

    #[DataProvider('nonExactUsernames')]
    public function testCollationVariantsAreRejected(string $stored, string $external): void
    {
        $user = new User(['username' => $stored]);

        $this->assertNull(User::verifyExactUsernameMatch($user, $external));
    }

    public static function nonExactUsernames(): iterable
    {
        yield 'case variant' => ['admin', 'Admin'];
        yield 'uppercase variant' => ['admin', 'ADMIN'];
        yield 'accented variant' => ['snipeitreport3', "sn\u{00ED}peitreport3"];
        yield 'leading whitespace' => ['admin', ' admin'];
        yield 'trailing whitespace' => ['admin', 'admin '];
    }

    public function testNullOrEmptyIdentityIsRejected(): void
    {
        $this->assertNull(User::verifyExactUsernameMatch(null, 'admin'));
        $this->assertNull(User::verifyExactUsernameMatch(new User(['username' => null]), ''));
        $this->assertNull(User::verifyExactUsernameMatch(new User(['username' => '']), ''));
        $this->assertNull(User::verifyExactUsernameMatch(new User(['username' => 'admin']), ''));
    }
}
