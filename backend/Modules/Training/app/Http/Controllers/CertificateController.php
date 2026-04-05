<?php

namespace Modules\Training\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Training\Http\Requests\UploadCertificateRequest;
use Modules\Training\Models\TrainingGroup;
use Modules\Training\Models\GroupParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends BaseController
{
    public function upload(
        UploadCertificateRequest $request,
        TrainingGroup $trainingGroup,
        GroupParticipant $participant,
    ): JsonResponse {
        if ($participant->certificate_path) {
            Storage::disk('public')->delete($participant->certificate_path);
        }
        $path = $request->file('certificate')->storeAs(
            "certificates/{$trainingGroup->id}",
            "{$participant->id}.pdf",
            'public'
        );

        $participant->update(['certificate_path' => $path]);

        return $this->success([
            'message'          => 'Сертификат успешно загружен.',
            'certificate_path' => $path,
        ]);
    }

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