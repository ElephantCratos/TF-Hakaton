<?php

namespace Modules\Xml\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Xml\Models\XmlImportBatch;
use Modules\Xml\Models\XmlImportLog;
use Modules\Xml\Services\Parsers\ParticipantXmlParser;
use Modules\Xml\Services\Parsers\CourseXmlParser;
use Modules\Xml\Services\Parsers\SpecificationXmlParser;
use Modules\Xml\Services\Importers\EmployeeImporter;
use Modules\Xml\Services\Importers\CourseImporter;
use Modules\Xml\Services\Importers\SpecificationImporter;

/**
 * Главный сервис импорта XML.
 *
 * Точка входа для HTTP-контроллера и CLI-команды.
 * Определяет тип XML по корневому тегу и делегирует
 * парсинг + импорт нужным классам.
 */
class XmlImportService
{
    public function __construct(
        private readonly ParticipantXmlParser $participantParser,
        private readonly CourseXmlParser      $courseParser,
        private readonly SpecificationXmlParser  $specificationParser,
        private readonly EmployeeImporter     $employeeImporter,
        private readonly CourseImporter       $courseImporter,
        private readonly SpecificationImporter   $specificationImporter,
    ) {}

    /**
     * Импортирует один загруженный файл.
     *
     * @param  UploadedFile  $file
     * @param  int|null      $processedBy  ID сотрудника (из auth)
     * @return XmlImportBatch
     */
    public function importFile(UploadedFile $file, ?int $processedBy = null): XmlImportBatch
    {
        $content = $file->get();

        $batch = $this->createBatch($file->getClientOriginalName(), $content, $processedBy);

        $this->processContent($content, $batch);

        return $batch->fresh(['logs']);
    }

    public function importString(string $xmlContent, string $fileName, ?int $processedBy = null): XmlImportBatch
    {
        $batch = $this->createBatch($fileName, $xmlContent, $processedBy);

        $this->processContent($xmlContent, $batch);

        return $batch->fresh(['logs']);
    }

    private function createBatch(string $fileName, string $rawPayload, ?int $processedBy): XmlImportBatch
    {
        $batch = new XmlImportBatch();
        $batch->source_system = 'Global ERP';
        $batch->file_name     = $fileName;
        $batch->imported_at   = now();
        $batch->processed_by  = $processedBy;
        $batch->raw_payload   = $rawPayload;
        $batch->save();

        return $batch;
    }

    private function processContent(string $content, XmlImportBatch $batch): void
    {
        try {
            $rootTag = $this->detectRootTag($content);
        } catch (\InvalidArgumentException $e) {
            $this->logEntry($batch->id, 'unknown', null, null,
                XmlImportLog::STATUS_ERROR, $e->getMessage());
            return;
        }

        match ($rootTag) {
            'Edu_Participant', 'Participants' => $this->importParticipants($content, $batch),
            'Edu_Course', 'Courses' => $this->importCourses($content, $batch),
            'Edu_Specification', 'Specifications' => $this->importSpecifications($content, $batch),
            default => $this->logEntry(
                $batch->id, 'unknown', null, null,
                XmlImportLog::STATUS_ERROR,
                "Неизвестный тип XML: <{$rootTag}>. Поддерживаются: Edu_Participant, Participants, Edu_Course, Courses."
            ),
        };
    }

        private function importParticipants(string $content, XmlImportBatch $batch): void
    {
        // Парсим XML в массив
        try {
            $participants = $this->participantParser->parseMultiple($content);
        } catch (\InvalidArgumentException $e) {
            $this->logEntry($batch->id, 'Employee', null, null,
                XmlImportLog::STATUS_ERROR, 'Ошибка разбора XML: ' . $e->getMessage());
            return;
        }

        foreach ($participants as $data) {
            try {
                DB::beginTransaction();
                $result = $this->employeeImporter->import($data, $batch->id);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('[XmlImport] Employee import failed', [
                    'batch_id' => $batch->id,
                    'external_id' => $data['external_id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                $this->logEntry($batch->id, 'Employee', $data['external_id'] ?? null, null,
                    XmlImportLog::STATUS_ERROR, 'Ошибка БД: ' . $e->getMessage());
                continue;
            }

            $this->logEntry(
                $batch->id,
                'Employee',
                $data['external_id'],
                $result['operation_type'],
                $result['status'],
                $result['message'],
            );
        }
    }

        
    private function importCourses(string $content, XmlImportBatch $batch): void
    {
        try {
            $courses = $this->courseParser->parseMultiple($content);
        } catch (\InvalidArgumentException $e) {
            $this->logEntry($batch->id, 'Course', null, null,
                XmlImportLog::STATUS_ERROR, 'Ошибка разбора XML: ' . $e->getMessage());
            return;
        }

        foreach ($courses as $data) {
            try {
                DB::beginTransaction();
                $result = $this->courseImporter->import($data, $batch->id);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('[XmlImport] Course import failed', [
                    'batch_id' => $batch->id,
                    'external_id' => $data['external_id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                $this->logEntry($batch->id, 'Course', $data['external_id'] ?? null, null,
                    XmlImportLog::STATUS_ERROR, 'Ошибка БД: ' . $e->getMessage());
                continue;
            }

            $this->logEntry(
                $batch->id,
                'Course',
                $data['external_id'],
                $result['operation_type'],
                $result['status'],
                $result['message'],
            );
        }
    }

    private function importSpecifications(string $content, XmlImportBatch $batch): void
    {
        try {
            $specifications = $this->specificationParser->parseMultiple($content);
        } catch (\InvalidArgumentException $e) {
            $this->logEntry($batch->id, 'Specification', null, null,
                XmlImportLog::STATUS_ERROR, 'Ошибка разбора XML: ' . $e->getMessage());
            return;
        }

        foreach ($specifications as $data) {
            try {
                DB::beginTransaction();
                $result = $this->specificationImporter->import($data, $batch->id);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('[XmlImport] Specification import failed', [
                    'batch_id' => $batch->id,
                    'number'   => $data['number'] ?? null,
                    'error'    => $e->getMessage(),
                ]);
                $this->logEntry($batch->id, 'Specification', $data['number'] ?? null, null,
                    XmlImportLog::STATUS_ERROR, 'Ошибка импорта: ' . $e->getMessage());
                continue;
            }

            $this->logEntry(
                $batch->id,
                'Specification',
                $data['number'],
                $result['operation_type'],
                $result['status'],
                $result['message'],
            );
        }
    }

    private function detectRootTag(string $content): string
    {
        if (preg_match('/<([A-Za-z_][A-Za-z0-9_]*)[\s>\/]/', $content, $matches)) {
            return $matches[1];
        }

        throw new \InvalidArgumentException('Не удалось определить корневой тег XML.');
    }

    private function logEntry(
        int $batchId,
        string $entityName,
        ?string $externalId,
        ?string $operation,
        string $status,
        string $message,
    ): void {
        XmlImportLog::create([
            'batch_id'           => $batchId,
            'entity_name'        => $entityName,
            'entity_external_id' => $externalId,
            'operation_type'     => $operation,
            'status'             => $status,
            'message'            => $message,
        ]);
    }
}