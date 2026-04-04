<?php

namespace Modules\Core\Abstracts\Policies;

use Modules\Core\Auth\Models\User;
use Modules\Core\Enums\Role;

abstract class BasePolicy
{
    /**
     * Пустой массив = доступно всем авторизованным.
     * Переопределяется в каждой конкретной политике.
     *
     * @var array<string, Role[]>
     */
    protected array $permissions = [];

    /**
     * Проверка роли пользователя для указанного действия.
     * Admin проходит всегда. 
     * Пустой массив ролей = доступно всем.
     */
    protected function checkRole(User $user, string $ability): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $allowed = $this->permissions[$ability] ?? [];

        // Пустой массив — доступно всем авторизованным
        if (empty($allowed)) {
            return true;
        }

        return $user->hasRole(...$allowed);
    }
}