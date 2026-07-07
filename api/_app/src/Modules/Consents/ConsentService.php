<?php

declare(strict_types=1);

namespace App\Modules\Consents;

use App\Database\Repositories\AuditLogRepository;
use App\Database\Repositories\ConsentRepository;
use InvalidArgumentException;
use Throwable;

final class ConsentService
{
    private ?ConsentRepository $consents = null;

    public function __construct(?ConsentRepository $consents = null)
    {
        $this->consents = $consents;
    }

    /**
     * @return array<string, mixed>
     */
    public function activeTerm(): array
    {
        $term = $this->consents()->findActiveTerm();

        if ($term === null) {
            throw new InvalidArgumentException('No active consent term found');
        }

        return [
            'status' => 'ok',
            'consent_term' => $term,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function accept(string $userId, array $payload): void
    {
        $termId = (string) ($payload['consent_term_id'] ?? '');
        $term = $termId !== ''
            ? $this->consents()->findById($termId)
            : $this->consents()->findActiveTerm();

        if ($term === null || empty($term['id']) || !self::isActiveTerm($term)) {
            throw new InvalidArgumentException('No active consent term found');
        }

        $this->consents()->create([
            'user_id' => $userId,
            'consent_term_id' => $term['id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'metadata' => [
                'route' => '/api/consents/accept',
                'method' => 'POST',
            ],
        ]);

        $this->auditAccepted($userId, (string) $term['id']);
    }

    private function auditAccepted(string $userId, string $termId): void
    {
        try {
            (new AuditLogRepository())->create([
                'actor_user_id' => $userId,
                'severity' => 'INFO',
                'action' => 'consent.accepted',
                'entity_type' => 'consent_terms',
                'entity_id' => $termId,
                'metadata_json' => [
                    'route' => '/api/consents/accept',
                    'method' => 'POST',
                    'status_code' => 200,
                ],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable) {
        }
    }

    /**
     * @param array<string, mixed> $term
     */
    private static function isActiveTerm(array $term): bool
    {
        $active = $term['active'] ?? false;

        return $active === true || $active === 1 || $active === '1';
    }

    private function consents(): ConsentRepository
    {
        if (!$this->consents instanceof ConsentRepository) {
            $this->consents = new ConsentRepository();
        }

        return $this->consents;
    }
}
