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

$stmt = $pdo->query(
    "SELECT a.map_id, m.owner_user_id AS user_id
     FROM map_ai_analyses a
     JOIN maps m ON m.id = a.map_id
     WHERE a.status = 'pending'
     ORDER BY a.created_at ASC
     LIMIT 3"
);

if ($stmt === false) {
    echo date('c') . " [worker] Falha na query.\n";
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
    exit(1);
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo date('c') . " [worker] Nenhuma análise em fila.\n";
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
    exit(0);
}

// ── Processa cada item ────────────────────────────────────────────────────────
foreach ($rows as $row) {
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
}

echo date('c') . " [worker] Finalizado.\n";

flock($lockFp, LOCK_UN);
fclose($lockFp);
exit(0);
