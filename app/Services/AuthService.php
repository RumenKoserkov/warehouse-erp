<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Csrf;
use App\Core\Environment;
use App\Core\Session;
use App\Models\User;

class AuthService
{
    private const DUMMY_PASSWORD_HASH =
        '$2y$12$/2E1ivR0wsrs/sQu4W1Dz.N.q2519pzzzVuobduZxyt4dVRm/lfAK';

    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function login(
        string $email,
        string $password
    ): bool {
        $email = mb_strtolower(
            trim($email)
        );

        $user =
            $this->userModel
                ->findByEmail($email);

        /*
         * Извършваме password_verify и при
         * несъществуващ email, за да намалим
         * разликата във времето за отговор.
         */
        if ($user === null) {
            password_verify(
                $password,
                self::DUMMY_PASSWORD_HASH
            );

            return false;
        }

        $passwordIsValid =
            password_verify(
                $password,
                (string) $user['password']
            );

        if (
            !$passwordIsValid ||
            (int) $user['is_active'] !== 1
        ) {
            return false;
        }

        Session::regenerate();

        $now = time();

        Session::set(
            'user',
            $this->sessionUser($user)
        );

        Session::set(
            'auth_login_at',
            $now
        );

        Session::set(
            'auth_last_activity',
            $now
        );

        Session::set(
            'auth_last_regeneration',
            $now
        );

        Session::set(
            'auth_last_user_check',
            $now
        );

        Session::set(
            'auth_user_agent_hash',
            $this->userAgentHash()
        );

        /*
         * Старият CSRF token не трябва да
         * продължава след authentication.
         */
        Csrf::regenerate();

        $this->userModel->updateLastLogin(
            (int) $user['id']
        );

        return true;
    }

    public function validateSession(): bool
    {
        $sessionUser = $this->user();

        if ($sessionUser === null) {
            return false;
        }

        $now = time();

        $loginAt = Session::get(
            'auth_login_at'
        );

        $lastActivity = Session::get(
            'auth_last_activity'
        );

        $lastRegeneration = Session::get(
            'auth_last_regeneration'
        );

        $lastUserCheck = Session::get(
            'auth_last_user_check'
        );

        if (
            !is_int($loginAt) ||
            !is_int($lastActivity) ||
            !is_int($lastRegeneration) ||
            !is_int($lastUserCheck)
        ) {
            $this->logout();

            return false;
        }

        $idleTimeout =
            Environment::integer(
                'SESSION_IDLE_TIMEOUT',
                1800
            );

        $absoluteTimeout =
            Environment::integer(
                'SESSION_ABSOLUTE_TIMEOUT',
                43200
            );

        if (
            $now - $lastActivity >
                $idleTimeout ||
            $now - $loginAt >
                $absoluteTimeout
        ) {
            $this->logout();

            return false;
        }

        $storedUserAgentHash =
            Session::get(
                'auth_user_agent_hash'
            );

        if (
            !is_string(
                $storedUserAgentHash
            ) ||
            !hash_equals(
                $storedUserAgentHash,
                $this->userAgentHash()
            )
        ) {
            $this->logout();

            return false;
        }

        $userCheckInterval =
            Environment::integer(
                'SESSION_USER_RECHECK_INTERVAL',
                300
            );

        if (
            $now - $lastUserCheck >=
                $userCheckInterval
        ) {
            $databaseUser =
                $this->userModel
                    ->findById(
                        (int) $sessionUser['id']
                    );

            if (
                $databaseUser === null ||
                (int) $databaseUser[
                    'is_active'
                ] !== 1 ||
                (int) $databaseUser[
                    'company_id'
                ] !==
                (int) $sessionUser[
                    'company_id'
                ]
            ) {
                $this->logout();

                return false;
            }

            Session::set(
                'user',
                $this->sessionUser(
                    $databaseUser
                )
            );

            Session::set(
                'auth_last_user_check',
                $now
            );
        }

        $renewalInterval =
            Environment::integer(
                'SESSION_RENEWAL_INTERVAL',
                1800
            );

        if (
            $now - $lastRegeneration >=
                $renewalInterval
        ) {
            Session::regenerate();

            Csrf::regenerate();

            Session::set(
                'auth_last_regeneration',
                $now
            );
        }

        Session::set(
            'auth_last_activity',
            $now
        );

        return true;
    }

    public function logout(): void
    {
        Session::destroy();
    }

    public function check(): bool
    {
        return Session::has('user');
    }

    public function user(): ?array
    {
        $user = Session::get('user');

        if (!is_array($user)) {
            return null;
        }

        return $user;
    }

    public function id(): ?int
    {
        $user = $this->user();

        if ($user === null) {
            return null;
        }

        return (int) $user['id'];
    }

    public function hasRole(
        string $roleSlug
    ): bool {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return (string) $user[
            'role_slug'
        ] === $roleSlug;
    }

    public function hasAnyRole(
        array $roleSlugs
    ): bool {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return in_array(
            (string) $user[
                'role_slug'
            ],
            $roleSlugs,
            true
        );
    }

    private function sessionUser(
        array $user
    ): array {
        return [
            'id' =>
                (int) $user['id'],

            'company_id' =>
                (int) $user['company_id'],

            'role_id' =>
                (int) $user['role_id'],

            'name' =>
                (string) $user['name'],

            'email' =>
                (string) $user['email'],

            'role_name' =>
                (string) $user['role_name'],

            'role_slug' =>
                (string) $user['role_slug'],

            'company_name' =>
                (string) $user[
                    'company_name'
                ],
        ];
    }

    private function userAgentHash(): string
    {
        $userAgent = '';

        if (
            isset(
                $_SERVER[
                    'HTTP_USER_AGENT'
                ]
            ) &&
            is_string(
                $_SERVER[
                    'HTTP_USER_AGENT'
                ]
            )
        ) {
            $userAgent =
                $_SERVER[
                    'HTTP_USER_AGENT'
                ];
        }

        return hash(
            'sha256',
            $userAgent
        );
    }
}