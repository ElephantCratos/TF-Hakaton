<?php

namespace Modules\Core\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Унифицированные API-ответы для всех контроллеров.
 */
trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function created(mixed $data = null, string $message = 'Создано'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function error(string $message = 'Ошибка', int $code = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    protected function notFound(string $message = 'Не найдено'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
