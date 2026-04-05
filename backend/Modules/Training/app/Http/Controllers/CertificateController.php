<?php

namespace Modules\Training\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Training\Http\Requests\UploadCertificateRequest;
use Modules\Training\Models\TrainingGroup;
use Modules\Training\Models\GroupParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Контроллер для работы с сертификатами участников обучения.
 *
 * Логика:
 *   - Сертификат — одностраничный PDF-документ, подтверждающий прохождение курса.
 *   - Файл сохраняется на диск «public» в папку certificates/{groupId}/{participantId}.pdf
 *   - Путь хранится в поле certificate_path таблицы group_participants.
 *   - Допустимые форматы: только PDF, макс. размер: 5 MB.
 *   - Валидация количества страниц не выполняется на уровне контроллера
 *     (можно добавить через сервис с использованием smalot/pdfparser или аналога).
 */
class CertificateController extends BaseController
{
    /**
     * POST /api/training-groups/{trainingGroup}/participants/{participant}/certificate
     *
     * Загрузка сертификата (PDF).
     * Если у участника уже есть сертификат — он перезаписывается.
     */
    public function upload(
        UploadCertificateRequest $request,
        TrainingGroup $trainingGroup,
        GroupParticipant $participant,
    ): JsonResponse {
        // Удаляем старый сертификат, если он существует
        if ($participant->certificate_path) {
            Storage::disk('public')->delete($participant->certificate_path);
        }

        // Формируем путь: certificates/{groupId}/{participantId}.pdf
        $path = $request->file('certificate')->storeAs(
            "certificates/{$trainingGroup->id}",
            "{$participant->id}.pdf",
            'public'
        );

        $participant->update(['certificate_path' => $path]);

        // Не отдаём прямой URL на storage — скачивание всегда через API-endpoint
        // GET /api/training-groups/{id}/participants/{pid}/certificate
        return $this->success([
            'message'          => 'Сертификат успешно загружен.',
            'certificate_path' => $path,
        ]);
    }

    /**
     * GET /api/training-groups/{trainingGroup}/participants/{participant}/certificate
     *
     * Скачивание сертификата.
     */
    public function download(
        TrainingGroup $trainingGroup,
        GroupParticipant $participant,
    ): StreamedResponse|JsonResponse {
        if (!$participant->hasCertificate()) {
            return $this->error('Сертификат не найден.', 404);
        }

        $employeeName = $participant->employee?->full_name ?? 'participant';
        $fileName     = "certificate_{$employeeName}.pdf";

        return Storage::disk('public')->download(
            $participant->certificate_path,
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * DELETE /api/training-groups/{trainingGroup}/participants/{participant}/certificate
     *
     * Удаление сертификата.
     */
    public function destroy(
        TrainingGroup $trainingGroup,
        GroupParticipant $participant,
    ): JsonResponse {
        if (!$participant->certificate_path) {
            return $this->error('Сертификат не найден.', 404);
        }

        $participant->deleteCertificate();

        return $this->success(['message' => 'Сертификат удалён.']);
    }
}