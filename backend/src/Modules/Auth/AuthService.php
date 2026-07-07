<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Database\Repositories\AuditLogRepository;
use App\Database\Repositories\ConsentRepository;
use App\Database\Repositories\UserRepository;
use App\Security\PasswordHasher;
use App\Security\SessionManager;
use InvalidArgumentException;
use Throwable;

final class AuthService
{
    private ?UserRepository $users = null;
    private ?ConsentRepository $consents = null;

    public function __construct(?UserRepository $users = null, ?ConsentRepository $consents = null)
    {
        $this->users = $users;
        $this->consents = $consents;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status:string, user:array<string, mixed>}
     */
    public function register(array $payload): array
    {
        try {
            $name = trim((string) ($payload['name'] ?? ''));
            $email = self::normalizeEmail((string) ($payload['email'] ?? ''));
            $password = (string) ($payload['password'] ?? '');
            $role = 'profissional';

            if ($name === '' || $email === '' || $password === '') {
                throw new InvalidArgumentException('Preencha nome, e-mail e senha.');
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException('Informe um e-mail valido.');
            }

            self::assertPasswordPolicy($password);

            if ($this->users()->findByEmail($email) !== null) {
                throw new InvalidArgumentException('Este e-mail ja esta cadastrado.');
            }

            $id = $this->users()->create([
                'name' => $name,
                'email' => $email,
                'password_hash' => PasswordHasher::hash($password),
                'role' => $role,
                'status' => 'active',
            ]);

            $user = $this->users()->findById($id);

            $this->audit('auth.register.success', 'INFO', $id, [
                'route' => '/api/auth/register',
                'method' => 'POST',
                'status_code' => 200,
            ]);

            return [
                'status' => 'ok',
                'user' => self::safeUser($user ?? []),
            ];
        } catch (InvalidArgumentException $exception) {
            $this->audit('auth.register.validation_failed', 'WARN', null, [
                'route' => '/api/auth/register',
                'method' => 'POST',
                'status_code' => 400,
                'reason' => $exception->getMessage(),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            $this->audit('auth.register.failed', 'WARN', null, [
                'route' => '/api/auth/register',
                'method' => 'POST',
                'status_code' => 400,
                'exception' => $exception::class,
            ]);

            throw new InvalidArgumentException('Could not register user');
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status:string, user:array<string, mixed>, requires_consent:bool}
     */
    public function login(array $payload): array
    {
        $email = self::normalizeEmail((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $emailHash = hash('sha256', $email);

        try {
            $user = $this->users()->findByEmail($email);

            if ($user === null || !PasswordHasher::verify($password, (string) $user['password_hash'])) {
                throw new InvalidArgumentException('Invalid credentials');
            }

            if (!in_array((string) $user['status'], ['active'], true)) {
                throw new InvalidArgumentException('Inactive user');
            }

            SessionManager::login([
                'id' => (string) $user['id'],
                'role' => (string) $user['role'],
            ]);

            $this->users()->updateLastLogin((string) $user['id']);

            $this->audit('auth.login.success', 'INFO', (string) $user['id'], [
                'route' => '/api/auth/login',
                'method' => 'POST',
                'status_code' => 200,
            ]);

            return [
                'status' => 'ok',
                'user' => self::safeUser($user),
                'requires_consent' => $this->requiresConsent((string) $user['id']),
            ];
        } catch (Throwable $exception) {
            $this->audit('auth.login.failed', 'WARN', null, [
                'route' => '/api/auth/login',
                'method' => 'POST',
                'status_code' => 401,
                'email_hash' => $emailHash,
                'exception' => $exception::class,
            ]);

            throw new InvalidArgumentException('Invalid email or password');
        }
    }

    /**
     * @return array{status:string, user:array<string, mixed>, requires_consent:bool}
     */
    public function me(string $userId): array
    {
        $user = $this->users()->findById($userId);

        if ($user === null) {
            throw new InvalidArgumentException('Unauthorized');
        }

        $this->audit('auth.me.access', 'INFO', $userId, [
            'route' => '/api/auth/me',
            'method' => 'GET',
            'status_code' => 200,
        ]);

        return [
            'status' => 'ok',
            'user' => self::safeUser($user),
            'requires_consent' => $this->requiresConsent($userId),
        ];
    }

    public function logout(string $userId): void
    {
        $this->audit('auth.logout.success', 'INFO', $userId, [
            'route' => '/api/auth/logout',
            'method' => 'POST',
            'status_code' => 200,
        ]);

        SessionManager::logout();
    }

    public function requiresConsent(string $userId): bool
    {
        $activeTerm = $this->consents()->findActiveTerm();

        if ($activeTerm === null || empty($activeTerm['id'])) {
            return true;
        }

        return $this->consents()->findAcceptedByUserAndTerm($userId, (string) $activeTerm['id']) === null;
    }

    /**
     * @param array<string, mixed> $user
     * @return array{id:string, name:string, email:string, role:string, user_status:string}
     */
    public static function safeUser(array $user): array
    {
        return [
            'id' => (string) ($user['id'] ?? ''),
            'name' => (string) ($user['name'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'role' => (string) ($user['role'] ?? ''),
            'user_status' => (string) ($user['status'] ?? ''),
        ];
    }

    private static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private static function assertPasswordPolicy(string $password): void
    {
        if (strlen($password) < 8 || preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
            throw new InvalidArgumentException('A senha precisa ter pelo menos 8 caracteres, incluindo letras e numeros.');
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function audit(string $action, string $severity, ?string $userId, array $metadata): void
    {
        try {
            (new AuditLogRepository())->create([
                'actor_user_id' => $userId,
                'severity' => $severity,
                'action' => $action,
                'entity_type' => 'auth',
                'metadata_json' => $metadata,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable) {
        }
    }

    private function users(): UserRepository
    {
        if (!$this->users instanceof UserRepository) {
            $this->users = new UserRepository();
        }

        return $this->users;
    }

    private function consents(): ConsentRepository
    {
        if (!$this->consents instanceof ConsentRepository) {
            $this->consents = new ConsentRepository();
        }

        return $this->consents;
    }
}
