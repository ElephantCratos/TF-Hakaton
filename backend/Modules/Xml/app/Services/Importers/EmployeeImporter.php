<?php

namespace Modules\Xml\Services\Importers;

use Illuminate\Support\Facades\DB;
use Modules\Xml\Models\XmlImportLog;

class EmployeeImporter
{
    public function import(array $data, int $batchId): array
    {
        $company = DB::table('companies')
            ->where('code', $data['company_external_id'])
            ->first();

        if (! $company) {
            $companyId = DB::table('companies')->insertGetId([
                'code'       => $data['company_external_id'],
                'name'       => $data['company_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $companyId = $company->id;

            if ($company->name !== $data['company_name']) {
                DB::table('companies')
                    ->where('id', $companyId)
                    ->update(['name' => $data['company_name'], 'updated_at' => now()]);
            }
        }

        $existing = DB::table('employees')
            ->where('employee_code', $data['employee_code'])
            ->whereNull('deleted_at')
            ->first();

        if (! $existing) {
            DB::table('employees')->insert([
                'employee_code' => $data['employee_code'],
                'last_name'     => $data['last_name'],
                'first_name'    => $data['first_name'],
                'middle_name'   => $data['middle_name'],
                'full_name'     => $data['full_name'],
                'company_id'    => $companyId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            return [
                'operation_type' => XmlImportLog::OP_CREATE,
                'status'         => XmlImportLog::STATUS_SUCCESS,
                'message'        => "Создан сотрудник: {$data['full_name']} (код: {$data['employee_code']})",
            ];
        }

        $changed = $existing->last_name   !== $data['last_name']
                || $existing->first_name  !== $data['first_name']
                || $existing->middle_name !== $data['middle_name']
                || $existing->full_name   !== $data['full_name']
                || $existing->company_id  !== $companyId;

        if (! $changed) {
            return [
                'operation_type' => XmlImportLog::OP_SKIP,
                'status'         => XmlImportLog::STATUS_SKIPPED,
                'message'        => "Без изменений: {$data['full_name']} (код: {$data['employee_code']})",
            ];
        }

        DB::table('employees')
            ->where('id', $existing->id)
            ->update([
                'last_name'   => $data['last_name'],
                'first_name'  => $data['first_name'],
                'middle_name' => $data['middle_name'],
                'full_name'   => $data['full_name'],
                'company_id'  => $companyId,
                'updated_at'  => now(),
            ]);

        return [
            'operation_type' => XmlImportLog::OP_UPDATE,
            'status'         => XmlImportLog::STATUS_SUCCESS,
            'message'        => "Обновлён сотрудник: {$data['full_name']} (код: {$data['employee_code']})",
        ];
    }
}