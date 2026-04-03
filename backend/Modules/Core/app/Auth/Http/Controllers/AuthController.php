<?php

namespace Modules\Core\Auth\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Core\Auth\Http\Requests\LoginRequest;
use Modules\Core\Auth\Http\Requests\RegisterRequest;
use Modules\Core\Auth\Http\Resources\UserResource;
use Modules\Core\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->created([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Регистрация прошла успешно');
    }

    /**
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Вход выполнен');
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(message: 'Выход выполнен');
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user())
        );
    }
}
