<?php

declare(strict_types=1);

use App\Http\JsonResponse;
use App\Modules\Auth\RoleMiddleware;
use App\Modules\AiAnalysis\AiPromptBuilder;
use App\Modules\AiAnalysis\MethodologyContext;
use App\Modules\AiAnalysis\KnowledgeRetriever;
use App\Modules\AiAnalysis\StructuredReading;
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

test('vision extraction always requires human review', static function (): void {
    $reading = StructuredReading::normalizeExtraction([
        'summary' => '  leitura inicial  ',
        'elements' => [['type' => 'desconhecido', 'label' => ' Item ', 'confidence' => 2, 'x' => -1]],
        'review' => ['status' => 'reviewed', 'professional_notes' => ' conferir seta '],
    ]);

    assertSame('leitura inicial', $reading['summary']);
    assertSame('pending', $reading['review']['status']);
    assertSame('conferir seta', $reading['review']['professional_notes']);
    assertSame('situacao', $reading['elements'][0]['type']);
    assertSame(1.0, $reading['elements'][0]['confidence']);
    assertSame(0.0, $reading['elements'][0]['x']);
    assertSame(3, count($reading['arrows']));
    assertTrue(!StructuredReading::isReviewed($reading));
});

test('reviewed structured reading is accepted by the review contract', static function (): void {
    assertTrue(StructuredReading::isReviewed(['review' => ['status' => 'reviewed']]));
    assertTrue(!StructuredReading::isReviewed(['review' => ['status' => 'pending']]));
});

test('analysis prompt includes the reviewed structured map', static function (): void {
    $prompt = AiPromptBuilder::userPrompt([
        'patient_name' => 'Paciente teste',
        'canvas_json' => [
            'main_demand' => 'Teste',
            'structured_reading' => [
                'summary' => 'Setas revisadas',
                'review' => ['status' => 'reviewed', 'professional_notes' => 'Validado'],
            ],
        ],
    ]);

    assertTrue(str_contains($prompt, 'LEITURA ESTRUTURADA DO MAPA'));
    assertTrue(str_contains($prompt, 'Setas revisadas'));
    assertTrue(str_contains($prompt, 'Não invente dados ausentes'));
});

test('methodology context is versioned and available to both AI stages', static function (): void {
    $context = MethodologyContext::load();
    assertTrue(str_contains($context, MethodologyContext::VERSION));
    assertTrue(str_contains($context, 'A revisão humana da leitura visual é obrigatória'));
    assertTrue(str_contains(AiPromptBuilder::systemPrompt(), '<metodologia'));
    assertTrue(str_contains(AiPromptBuilder::canvasFillerSystemPrompt(), '<metodologia'));
});

test('methodology context enforces evidence and uncertainty boundaries', static function (): void {
    $context = MethodologyContext::load();
    assertTrue(str_contains($context, 'não diagnóstico conclusivo'));
    assertTrue(str_contains($context, 'Não inventar conteúdo ilegível'));
    assertTrue(str_contains($context, 'Alternativa/limite'));
    assertTrue(str_contains($context, 'Pergunta de confirmação'));
});

test('knowledge retrieval selects traceable excerpts for reviewed map data', static function (): void {
    $map = [
        'canvas_json' => [
            'structured_reading' => [
                'quadrants' => ['emocional' => 'mãe e dependência afetiva'],
                'arrows' => [['arrow_type' => 'F', 'quadrant' => 'emocional', 'notes' => 'seta grande']],
                'review' => ['professional_notes' => 'Investigar complexo materno sem determinismo'],
            ],
        ],
    ];
    $excerpts = KnowledgeRetriever::relevantExcerpts($map);
    assertTrue(str_contains($excerpts, 'Fonte: material-didatico-2026'));
    assertTrue(str_contains($excerpts, 'seta'));

    $prompt = AiPromptBuilder::systemPrompt($map);
    assertTrue(str_contains($prompt, '<fontes_relevantes>'));
    assertTrue(str_contains($prompt, 'não podem ser copiados como conclusão'));
});

test('knowledge source manifest preserves source roles and fingerprints', static function (): void {
    $manifestPath = dirname(__DIR__) . '/resources/knowledge/sources.json';
    $manifest = json_decode((string) file_get_contents($manifestPath), true);
    assertTrue(is_array($manifest));
    assertSame(3, count($manifest['sources'] ?? []));
    assertSame('normative', $manifest['sources'][0]['role'] ?? null);
    assertSame(64, strlen((string) ($manifest['sources'][0]['sha256'] ?? '')));
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
