<?php

namespace Modules\Xml\Services\Importers;

use Illuminate\Support\Facades\DB;
use Modules\Xml\Models\XmlImportLog;

class SpecificationImporter
{
    public function import(array $data, int $batchId): array
    {
        if (empty($data['company_code'])) {
            return [
                'operation_type' => XmlImportLog::OP_SKIP,
                'status'         => XmlImportLog::STATUS_ERROR,
                'message'        => "Спецификация №{$data['number']}: отсутствует <idOrganization>.",
            ];
        }

        $companyId = $this->resolveCompany($data['company_code'], $data['company_name']);

        $existing = DB::table('specifications')
            ->where('number', $data['number'])
            ->first();

        $operation = XmlImportLog::OP_SKIP;

        if (! $existing) {
            $specificationId = DB::table('specifications')->insertGetId([
                'number'     => $data['number'],
                'date'       => $data['date'],
                'company_id' => $companyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $operation = XmlImportLog::OP_CREATE;
        } else {
            $specificationId = $existing->id;

            $changed = $existing->date       !== $data['date']
                    || $existing->company_id !== $companyId;

            if ($changed) {
                DB::table('specifications')
                    ->where('id', $specificationId)
                    ->update([
                        'date'       => $data['date'],
                        'company_id' => $companyId,
                        'updated_at' => now(),
                    ]);
            }

            $operation = XmlImportLog::OP_UPDATE;
        }

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

        $label = $operation === XmlImportLog::OP_CREATE ? 'Создана' : 'Обновлена';

        return [
            'operation_type' => $operation,
            'status'         => XmlImportLog::STATUS_SUCCESS,
            'message'        => "{$label} спецификация №{$data['number']} "
                              . '(групп: ' . count($data['groups']) . ')',
        ];
    }

    private function createGroup(array $groupData, int $specificationId): void
    {
        $courseId = $this->resolveCourse($groupData['course']);

        $groupId = DB::table('training_groups')->insertGetId([
            'specification_id'   => $specificationId,
            'course_id'          => $courseId,
            'start_date'         => $groupData['start_date'],
            'end_date'           => $groupData['end_date'],
            'status'             => $groupData['status'],
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        if (empty($groupData['participants'])) {
            return;
        }

        $now  = now();
        $rows = [];

        foreach ($groupData['participants'] as $participantData) {
            $rows[] = [
                'training_group_id'  => $groupId,
                'employee_id'        => $this->resolveEmployee($participantData),
                'completion_percent' => 0,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        DB::table('group_participants')->insert($rows);
    }

    private function resolveCompany(string $code, string $name): int
    {
        $company = DB::table('companies')
            ->where('code', $code)
            ->first();

        if (! $company) {
            return DB::table('companies')->insertGetId([
                'code'       => $code,
                'name'       => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($company->name !== $name) {
            DB::table('companies')
                ->where('id', $company->id)
                ->update(['name' => $name, 'updated_at' => now()]);
        }

        return $company->id;
    }

    private function resolveCourse(array $data): int
    {
        $existing = DB::table('courses')
            ->where('code', $data['code'])
            ->whereNull('deleted_at')
            ->first();

        if (! $existing) {
            $courseId = DB::table('courses')->insertGetId([
                'code'          => $data['code'],
                'title'         => $data['title'],
                'description'   => $data['description'],
                'duration_days' => $data['duration_days'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        } else {
            $courseId = $existing->id;

            $changed = $existing->title         !== $data['title']
                    || $existing->description   !== $data['description']
                    || (int) $existing->duration_days !== $data['duration_days'];

            if ($changed) {
                DB::table('courses')
                    ->where('id', $courseId)
                    ->update([
                        'title'         => $data['title'],
                        'description'   => $data['description'],
                        'duration_days' => $data['duration_days'],
                        'updated_at'    => now(),
                    ]);
            }
        }

        if ($data['price'] !== null) {
            $this->syncPrice($courseId, $data['price']);
        }

        return $courseId;
    }

    private function syncPrice(int $courseId, string $newPrice): void
    {
        $today     = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $currentPrice = DB::table('course_price')
            ->where('course_id', $courseId)
            ->where('valid_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $today);
            })
            ->orderByDesc('valid_from')
            ->first();

        if ($currentPrice && bccomp((string) $currentPrice->price, $newPrice, 2) === 0) {
            return;
        }

        if ($currentPrice) {
            DB::table('course_price')
                ->where('id', $currentPrice->id)
                ->update(['valid_to' => $yesterday]);
        }

        DB::table('course_price')->insert([
            'course_id'  => $courseId,
            'price'      => $newPrice,
            'valid_from' => $today,
            'valid_to'   => null,
            'created_at' => now(),
        ]);
    }

    private function resolveEmployee(array $data): int
    {
        $companyId = $this->resolveCompany($data['company_code'], $data['company_name']);

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