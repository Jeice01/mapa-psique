<?php

/**
 * CLI worker — processa análises de IA em fila.
 * Deve ser executado via cron (PHP CLI), nunca via HTTP.
 *
 * Exemplo de cron no Hostinger (a cada 5 minutos):
 *   php /home/USER/domains/api.DOMAIN/public_html/api/_app/bin/worker.php >> /tmp/ai_worker.log 2>&1
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden\n");
}

set_time_limit(0);
ini_set('memory_limit', '256M');

$appRoot = dirname(__DIR__);

require $appRoot . '/src/bootstrap.php';

use App\Support\Env;
use App\Database\Connection;
use App\Database\Repositories\AiAnalysisRepository;
use App\Modules\AiAnalysis\AiService;
use PDO;

Env::load($appRoot . '/.env');

// ── Lock: impede execuções simultâneas ────────────────────────────────────────
$lockFile = sys_get_temp_dir() . '/ai_worker.lock';
$lockFp   = fopen($lockFile, 'w');

if ($lockFp === false || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo date('c') . " [worker] Outra instância em execução. Saindo.\n";
    if ($lockFp !== false) {
        fclose($lockFp);
    }
    exit(0);
}

echo date('c') . " [worker] Iniciado.\n";

// ── Busca análises em fila ────────────────────────────────────────────────────
try {
    $pdo = Connection::pdo();
} catch (Throwable $e) {
    echo date('c') . " [worker] Erro de conexão com DB: " . $e->getMessage() . "\n";
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
    exit(1);
}

$repository = new AiAnalysisRepository($pdo);
$requeued = $repository->requeueStaleProcessing(10);
if ($requeued > 0) {
    echo date('c') . " [worker] {$requeued} processamento(s) interrompido(s) reenfileirado(s).\n";
}

$stmt = $pdo->query(
    "SELECT a.map_id, m.owner_user_id AS user_id
     FROM map_ai_analyses a
     JOIN maps m ON m.id = a.map_id
     WHERE a.status = 'pending'
     ORDER BY a.created_at ASC
     LIMIT 1"
);

if ($stmt === false) {
    echo date('c') . " [worker] Falha na query.\n";
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
    exit(1);
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Prioridade 1: relatório textual ──────────────────────────────────────────
if (!empty($rows)) {
    $row = $rows[0];
    $mapId  = (string) $row['map_id'];
    $userId = (string) $row['user_id'];

    echo date('c') . " [worker] Processando mapa {$mapId}...\n";

    try {
        (new AiService())->generate($mapId, $userId);
        echo date('c') . " [worker] Mapa {$mapId} concluído.\n";
    } catch (Throwable $t) {
        // AiService já persistiu status='failed' no DB
        echo date('c') . " [worker] Mapa {$mapId} falhou: " . $t->getMessage() . "\n";
    }
} else {
    // ── Prioridade 2: infográfico opcional, somente após o texto ──────────────
    $imageStmt = $pdo->query(
        "SELECT a.map_id, m.owner_user_id AS user_id
         FROM map_ai_analyses a
         JOIN maps m ON m.id = a.map_id
         WHERE a.status = 'completed'
           AND a.image_prompt IS NOT NULL
           AND a.image_prompt <> ''
           AND a.image_path IS NULL
           AND (a.model_image IS NULL OR a.model_image = '')
         ORDER BY a.generated_at ASC
         LIMIT 1"
    );
    $imageRow = $imageStmt === false ? false : $imageStmt->fetch(PDO::FETCH_ASSOC);

    if ($imageRow === false) {
        echo date('c') . " [worker] Nenhuma tarefa em fila.\n";
    } else {
        $mapId = (string) $imageRow['map_id'];
        $userId = (string) $imageRow['user_id'];
        echo date('c') . " [worker] Gerando infográfico do mapa {$mapId}...\n";
        try {
            (new AiService())->generateInfographic($mapId, $userId);
            echo date('c') . " [worker] Infográfico do mapa {$mapId} concluído.\n";
        } catch (Throwable $t) {
            $repository->markImageFailed($mapId);
            echo date('c') . " [worker] Infográfico do mapa {$mapId} falhou: " . $t->getMessage() . "\n";
        }
    }
}

echo date('c') . " [worker] Finalizado.\n";

flock($lockFp, LOCK_UN);
fclose($lockFp);
exit(0);
