<?php

namespace Modules\Xml\Services\Importers;

use Illuminate\Support\Facades\DB;
use Modules\Xml\Models\XmlImportLog;

/**
 * Импортёр спецификаций.
 *
 * Стратегия:
 *  1. Компания спецификации — ищем по idOrganization. Не найдена → ошибка.
 *  2. Курс                  — ищем по sCourseCode. Не найден → ошибка.
 *  3. Участники             — повторяем логику EmployeeImporter:
 *                             компанию upsert по idOrganization,
 *                             сотрудника upsert по employee_code.
 *  4. Валидация компании и курсов — ДО записи в БД.
 *  5. Спецификация          — upsert по sNumber.
 *  6. Группы                — старые удаляем вместе с участниками, создаём заново.
 */
class SpecificationImporter
{
    /**
     * @param  array  $data     Нормализованный массив из SpecificationXmlParser
     * @param  int    $batchId
     * @return array{operation_type: string, status: string, message: string}
     */
    public function import(array $data, int $batchId): array
    {
        // ----------------------------------------------------------------
        // 1. Компания спецификации — обязана существовать
        // ----------------------------------------------------------------
        $company = DB::table('companies')
            ->where('code', $data['company_code'])
            ->first();

        if (! $company) {
            return [
                'operation_type' => XmlImportLog::OP_SKIP,
                'status'         => XmlImportLog::STATUS_ERROR,
                'message'        => "Компания с кодом «{$data['company_code']}» не найдена.",
            ];
        }

        // ----------------------------------------------------------------
        // 2. Курсы — все обязаны существовать до начала записи
        // ----------------------------------------------------------------
        foreach ($data['groups'] as $groupData) {
            $exists = DB::table('courses')
                ->where('code', $groupData['course_code'])
                ->whereNull('deleted_at')
                ->exists();

            if (! $exists) {
                return [
                    'operation_type' => XmlImportLog::OP_SKIP,
                    'status'         => XmlImportLog::STATUS_ERROR,
                    'message'        => "Курс с кодом «{$groupData['course_code']}» не найден.",
                ];
            }
        }

        // ----------------------------------------------------------------
        // 3. Спецификация — upsert по number
        // ----------------------------------------------------------------
        $existing = DB::table('specifications')
            ->where('number', $data['number'])
            ->first();

        $operation = XmlImportLog::OP_SKIP;

        if (! $existing) {
            $specificationId = DB::table('specifications')->insertGetId([
                'number'     => $data['number'],
                'date'       => $data['date'],
                'company_id' => $company->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $operation = XmlImportLog::OP_CREATE;
        } else {
            $specificationId = $existing->id;

            $changed = $existing->date       !== $data['date']
                    || $existing->company_id !== $company->id;

            if ($changed) {
                DB::table('specifications')
                    ->where('id', $specificationId)
                    ->update([
                        'date'       => $data['date'],
                        'company_id' => $company->id,
                        'updated_at' => now(),
                    ]);
            }

            $operation = XmlImportLog::OP_UPDATE;
        }

        // ----------------------------------------------------------------
        // 4. Группы: сносим старые, создаём новые
        // ----------------------------------------------------------------
        $oldGroupIds = DB::table('training_groups')
            ->where('specification_id', $specificationId)
            ->pluck('id');

        if ($oldGroupIds->isNotEmpty()) {
            DB::table('group_participants')
                ->whereIn('training_group_id', $oldGroupIds)
                ->delete();

            DB::table('training_groups')
                ->whereIn('id', $oldGroupIds)
                ->delete();
        }

        foreach ($data['groups'] as $groupData) {
            $this->createGroup($groupData, $specificationId);
        }

        // ----------------------------------------------------------------
        // 5. Результат
        // ----------------------------------------------------------------
        $label = $operation === XmlImportLog::OP_CREATE ? 'Создана' : 'Обновлена';

        return [
            'operation_type' => $operation,
            'status'         => XmlImportLog::STATUS_SUCCESS,
            'message'        => "{$label} спецификация №{$data['number']} "
                              . '(групп: ' . count($data['groups']) . ')',
        ];
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function createGroup(array $groupData, int $specificationId): void
    {
        $course = DB::table('courses')
            ->where('code', $groupData['course_code'])
            ->whereNull('deleted_at')
            ->first();

        $groupId = DB::table('training_groups')->insertGetId([
            'specification_id'   => $specificationId,
            'course_id'          => $course->id,
            'start_date'         => $groupData['start_date'],
            'end_date'           => $groupData['end_date'],
            'status'             => $groupData['status'],
            'participants_count' => $groupData['participants_count'],
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        if (empty($groupData['participants'])) {
            return;
        }

        $now  = now();
        $rows = [];

        foreach ($groupData['participants'] as $participantData) {
            $employeeId = $this->resolveEmployee($participantData);

            $rows[] = [
                'training_group_id'  => $groupId,
                'employee_id'        => $employeeId,
                'completion_percent' => 0,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        DB::table('group_participants')->insert($rows);
    }

    /**
     * Повторяет логику EmployeeImporter:
     *  — компанию upsert по code
     *  — сотрудника upsert по employee_code
     *
     * Возвращает ID сотрудника.
     */
    private function resolveEmployee(array $data): int
    {
        // Компания участника (может отличаться от компании спецификации)
        $company = DB::table('companies')
            ->where('code', $data['company_code'])
            ->first();

        if (! $company) {
            $companyId = DB::table('companies')->insertGetId([
                'code'       => $data['company_code'],
                'name'       => $data['company_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $companyId = $company->id;

            if ($company->name !== $data['company_name']) {
                DB::table('companies')
                    ->where('id', $companyId)
                    ->update([
                        'name'       => $data['company_name'],
                        'updated_at' => now(),
                    ]);
            }
        }

        // Сотрудник
        $employee = DB::table('employees')
            ->where('employee_code', $data['employee_code'])
            ->whereNull('deleted_at')
            ->first();

        if (! $employee) {
            return DB::table('employees')->insertGetId([
                'employee_code' => $data['employee_code'],
                'last_name'     => $data['last_name'],
                'first_name'    => $data['first_name'],
                'middle_name'   => $data['middle_name'],
                'full_name'     => $data['full_name'],
                'company_id'    => $companyId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        // Обновляем если данные изменились
        $changed = $employee->last_name   !== $data['last_name']
                || $employee->first_name  !== $data['first_name']
                || $employee->middle_name !== $data['middle_name']
                || $employee->full_name   !== $data['full_name']
                || $employee->company_id  !== $companyId;

        if ($changed) {
            DB::table('employees')
                ->where('id', $employee->id)
                ->update([
                    'last_name'   => $data['last_name'],
                    'first_name'  => $data['first_name'],
                    'middle_name' => $data['middle_name'],
                    'full_name'   => $data['full_name'],
                    'company_id'  => $companyId,
                    'updated_at'  => now(),
                ]);
        }

        return $employee->id;
    }
}