<?php

namespace Modules\Core\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Core\Auth\Models\User;
use Modules\Core\Enums\Role;

class AuthService
{
    /**
     * Регистрация нового пользователя.
     *
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // cast 'hashed' в модели
            'role' => $data['role'] ?? Role::HR,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Аутентификация пользователя.
     *
     * @throws ValidationException
     * @return array{user: User, token: string}
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Выход — отзыв текущего токена.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
