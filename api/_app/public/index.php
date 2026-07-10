<?php

declare(strict_types=1);

use App\Controllers\HealthController;
use App\Controllers\DbCheckController;
use App\Http\JsonResponse;
use App\Http\Router;
use App\Middleware\CorsMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Modules\AiAnalysis\AiController;
use App\Modules\AiAnalysis\CanvasGeneratorController;
use App\Modules\Auth\AuthController;
use App\Modules\Consents\ConsentController;
use App\Modules\Dashboard\DashboardController;
use App\Modules\Maps\MapImageController;
use App\Modules\Maps\MapController;
use App\Modules\Patients\PatientMapController;
use App\Modules\Patients\PatientController;
use App\Support\Env;

require dirname(__DIR__) . '/src/bootstrap.php';

Env::load(dirname(__DIR__) . '/.env');

$cors = new CorsMiddleware();
(new SecurityHeadersMiddleware())->handle();
$cors->handle();

if ($cors->isPreflight()) {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$sensitiveRoutes = [
    'POST /api/auth/login',
    'POST /api/auth/register',
    'POST /api/auth/forgot-password',
    'POST /api/auth/reset-password',
    'POST /api/consents/accept',
];

(new RateLimitMiddleware())->handle(
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    "{$method} {$path}",
    in_array("{$method} {$path}", $sensitiveRoutes, true) ? 20 : 120
);

$router = new Router();

$router->get('/api/health', [HealthController::class, 'show']);
$router->get('/api/db-check', [DbCheckController::class, 'show']);

$router->get('/api/csrf-token', [AuthController::class, 'csrfToken']);
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/auth/me', [AuthController::class, 'me']);

$router->get('/api/consents/active', [ConsentController::class, 'active']);
$router->post('/api/consents/accept', [ConsentController::class, 'accept']);

$router->get('/api/dashboard/summary', [DashboardController::class, 'summary']);

$router->get('/api/patients', [PatientController::class, 'index']);
$router->post('/api/patients', [PatientController::class, 'create']);
$router->get('/api/patients/{id}', [PatientController::class, 'show']);
$router->put('/api/patients/{id}', [PatientController::class, 'update']);
$router->delete('/api/patients/{id}', [PatientController::class, 'delete']);
$router->post('/api/patients/{id}/create-map', [PatientMapController::class, 'createMap']);
$router->post('/api/patients/{id}/restore', [PatientController::class, 'restore']);

$router->get('/api/maps', [MapController::class, 'index']);
$router->post('/api/maps', [MapController::class, 'create']);

$router->get('/api/maps/{id}/canvas-versions', [MapController::class, 'canvasVersions']);
$router->get('/api/maps/{id}/canvas-versions/{versionId}', [MapController::class, 'canvasVersion']);
$router->post('/api/maps/{id}/canvas-versions/{versionId}/restore', [MapController::class, 'restoreCanvasVersion']);
$router->get('/api/maps/{id}/canvas-versions/{versionId}/export/pdf', [MapController::class, 'exportCanvasVersionPdf']);

$router->get('/api/maps/{id}/export/pdf', [MapController::class, 'exportPdf']);
$router->post('/api/maps/{id}/image', [MapImageController::class, 'upload']);
$router->get('/api/maps/{id}/image', [MapImageController::class, 'show']);
$router->post('/api/maps/{id}/generate-canvas', [CanvasGeneratorController::class, 'generate']);
$router->get('/api/maps/{id}/analysis/image', [AiController::class, 'image']);
$router->get('/api/maps/{id}/analysis', [AiController::class, 'show']);
$router->post('/api/maps/{id}/analysis', [AiController::class, 'generate']);

$router->get('/api/maps/{id}', [MapController::class, 'show']);
$router->put('/api/maps/{id}', [MapController::class, 'update']);
$router->delete('/api/maps/{id}', [MapController::class, 'delete']);

$response = $router->dispatch(
    $method,
    $path
);

if ($response === null) {
    $response = JsonResponse::error('Route not found', 404);
}

$response->send();
