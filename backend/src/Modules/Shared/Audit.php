<?php

declare(strict_types=1);

namespace App\Modules\Shared;

use App\Database\Repositories\AuditLogRepository;
use Throwable;

final class Audit
{
    /**
     * @param array<string, mixed> $metadata
     */
    public static function record(string $action, ?string $userId, string $entityType, ?string $entityId = null, array $metadata = []): void
    {
        try {
            (new AuditLogRepository())->create([
                'actor_user_id' => $userId,
                'severity' => 'INFO',
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metadata_json' => array_merge([
                    'route' => $_SERVER['REQUEST_URI'] ?? '',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                ], $metadata),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable) {
        }
    }
}
