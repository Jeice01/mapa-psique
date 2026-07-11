<?php

declare(strict_types=1);

use App\Http\JsonResponse;
use App\Modules\Auth\RoleMiddleware;
use App\Security\Csrf;
use App\Security\InputSanitizer;
use App\Security\PasswordHasher;
use App\Security\SessionManager;

require dirname(__DIR__) . '/src/bootstrap.php';

/** @var list<array{name:string,test:Closure():void}> $tests */
$tests = [];

function test(string $name, Closure $test): void
{
    global $tests;
    $tests[] = ['name' => $name, 'test' => $test];
}

function assertTrue(bool $condition, string $message = 'Expected condition to be true'): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertSame(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        $detail = sprintf('Expected %s, got %s', var_export($expected, true), var_export($actual, true));
        throw new RuntimeException($message === '' ? $detail : "{$message}: {$detail}");
    }
}

function assertThrows(string $exceptionClass, Closure $operation): void
{
    try {
        $operation();
    } catch (Throwable $exception) {
        assertTrue($exception instanceof $exceptionClass, 'Unexpected exception: ' . $exception::class);
        return;
    }

    throw new RuntimeException("Expected {$exceptionClass} to be thrown");
}

/** @return array{payload:array<string,mixed>,status:int} */
function responseData(JsonResponse $response): array
{
    $reflection = new ReflectionClass($response);

    return [
        'payload' => $reflection->getProperty('payload')->getValue($response),
        'status' => $reflection->getProperty('status')->getValue($response),
    ];
}

putenv('APP_ENV=testing');
putenv('CSRF_ENABLED=true');
putenv('SESSION_COOKIE_NAME=mapa_psique_test_session');

test('password hashes verify only the correct password', static function (): void {
    $hash = PasswordHasher::hash('correct horse battery staple');
    assertTrue(PasswordHasher::verify('correct horse battery staple', $hash));
    assertTrue(!PasswordHasher::verify('wrong password', $hash));
});

test('input sanitizer removes markup and surrounding whitespace', static function (): void {
    assertSame('alert(1) Nome', InputSanitizer::sanitizeString('  <script>alert(1)</script> Nome  '));
    assertSame('user@example.com', InputSanitizer::normalizeEmail(' USER@EXAMPLE.COM '));
    assertSame('abc', InputSanitizer::maxLength('abcdef', 3));
});

test('required input rejects empty sanitized values', static function (): void {
    assertThrows(InvalidArgumentException::class, static fn (): string => InputSanitizer::required(' <b> </b> ', 'name'));
});

test('session login exposes the authenticated identity and role', static function (): void {
    SessionManager::login(['id' => 'user-1', 'role' => 'profissional']);
    $session = SessionManager::current();
    assertSame('user-1', $session['user_id'] ?? null);
    assertSame('profissional', $session['role'] ?? null);
    assertTrue(($session['expires_at'] ?? 0) > time());
});

test('expired sessions are rejected', static function (): void {
    $_SESSION['auth']['expires_at'] = time() - 1;
    assertSame(null, SessionManager::current());
});

test('csrf tokens are random, correctly sized and validated', static function (): void {
    SessionManager::start();
    $first = Csrf::generate();
    $second = Csrf::generate();

    assertTrue((bool) preg_match('/^[a-f0-9]{64}$/', $second));
    assertTrue($first !== $second, 'CSRF tokens must not be reused');
    assertTrue(Csrf::validate($second));
    assertTrue(!Csrf::validate($first));
    assertTrue(!Csrf::validate(null));
});

test('csrf token is read only from the expected request header', static function (): void {
    $_SERVER['HTTP_X_CSRF_TOKEN'] = 'header-token';
    assertSame('header-token', Csrf::tokenFromRequest());
    unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    assertSame(null, Csrf::tokenFromRequest());
});

test('csrf cannot be disabled in production', static function (): void {
    putenv('APP_ENV=production');
    putenv('CSRF_ENABLED=false');
    $_SESSION['_csrf_token'] = 'expected';
    assertTrue(!Csrf::validate('different'));
    putenv('APP_ENV=testing');
    putenv('CSRF_ENABLED=true');
});

test('role middleware allows explicitly authorized roles', static function (): void {
    $result = (new RoleMiddleware())->requireRole(
        ['user_id' => 'user-1', 'role' => 'profissional'],
        ['profissional']
    );
    assertSame(null, $result);
});

test('role middleware returns 403 for unauthorized roles', static function (): void {
    $result = (new RoleMiddleware())->requireRole(
        ['user_id' => 'user-2', 'role' => 'paciente'],
        ['profissional', 'administrador']
    );
    assertTrue($result instanceof JsonResponse);
    $response = responseData($result);
    assertSame(403, $response['status']);
    assertSame('Forbidden', $response['payload']['message'] ?? null);
});

test('patient repository queries preserve owner isolation', static function (): void {
    $source = file_get_contents(dirname(__DIR__) . '/src/Database/Repositories/PatientRepository.php');
    assertTrue(is_string($source));
    assertTrue(substr_count($source, 'owner_user_id = :owner_user_id') >= 5);
    assertTrue(str_contains($source, "'owner_user_id' => \$ownerUserId"));
});

test('map repository queries preserve owner isolation', static function (): void {
    $source = file_get_contents(dirname(__DIR__) . '/src/Database/Repositories/MapRepository.php');
    assertTrue(is_string($source));
    assertTrue(substr_count($source, 'owner_user_id = :owner_user_id') >= 5);
    assertTrue(str_contains($source, "'owner_user_id' => \$ownerUserId"));
});

$failures = [];

foreach ($tests as $case) {
    try {
        $case['test']();
    } catch (Throwable $exception) {
        $failures[] = sprintf('%s: %s', $case['name'], $exception->getMessage());
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Security/access tests failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, sprintf("Security/access tests passed: %d\n", count($tests)));
