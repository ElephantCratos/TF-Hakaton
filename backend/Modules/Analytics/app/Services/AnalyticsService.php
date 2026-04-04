<?php

namespace Modules\Analytics\Services;

use Illuminate\Support\Facades\DB;
use Modules\Company\Models\Company;

/**
 * Сервис аналитики в разрезе компаний.
 *
 * Логика:
 * - Для каждой компании агрегируются данные по сотрудникам, группам обучения,
 *   спецификациям и стоимости обучения.
 * - Все расчёты выполняются через SQL-агрегации для производительности.
 * - «Участвовал в обучении» = сотрудник компании есть хотя бы в одной записи group_participants.
 */
class AnalyticsService
{
    /**
     * Сводная аналитика по всем компаниям.
     *
     * Возвращает массив с метриками по каждой компании:
     * - total_employees        — всего сотрудников в компании
     * - trained_employees      — сотрудников, участвовавших хотя бы в одном обучении
     * - training_groups_count  — количество учебных групп, в которых были сотрудники компании
     * - specifications_count   — количество спецификаций компании
     * - total_cost             — суммарная стоимость обучения (без НДС) по спецификациям
     * - total_cost_with_vat    — суммарная стоимость обучения (с НДС 22%)
     * - avg_progress           — средний прогресс обучения сотрудников компании
     */
    public function companySummary(): array
    {
        $companies = Company::withCount([
            // Всего сотрудников
            'employees',
            // Спецификации компании
            'specifications',
        ])->get();

        $result = [];

        foreach ($companies as $company) {
            // Сотрудники, которые участвовали хотя бы в одном обучении
            $trainedEmployees = DB::table('group_participants')
                ->join('employees', 'group_participants.employee_id', '=', 'employees.id')
                ->where('employees.company_id', $company->id)
                ->distinct('group_participants.employee_id')
                ->count('group_participants.employee_id');

            // Количество уникальных учебных групп, в которых участвовали сотрудники компании
            $trainingGroupsCount = DB::table('group_participants')
                ->join('employees', 'group_participants.employee_id', '=', 'employees.id')
                ->where('employees.company_id', $company->id)
                ->distinct('group_participants.training_group_id')
                ->count('group_participants.training_group_id');

            // Средний прогресс обучения сотрудников компании
            $avgProgress = DB::table('group_participants')
                ->join('employees', 'group_participants.employee_id', '=', 'employees.id')
                ->where('employees.company_id', $company->id)
                ->avg('group_participants.completion_percent') ?? 0;

            // Суммарная стоимость по спецификациям компании (без НДС)
            // Логика: сумма (цена курса * кол-во участников) для каждой группы в спецификации
            $totalCost = $this->calculateCompanyTotalCost($company->id);

            $result[] = [
                'id'                    => $company->id,
                'code'                  => $company->code,
                'name'                  => $company->name,
                'total_employees'       => $company->employees_count,
                'trained_employees'     => $trainedEmployees,
                'training_groups_count' => $trainingGroupsCount,
                'specifications_count'  => $company->specifications_count,
                'total_cost'            => round($totalCost, 2),
                'total_cost_with_vat'   => round($totalCost * 1.22, 2),
                'avg_progress'          => round($avgProgress, 2),
            ];
        }

        return $result;
    }

    /**
     * Детальная аналитика по конкретной компании.
     *
     * Возвращает расширенную информацию включая:
     * - список сотрудников с их прогрессом
     * - список спецификаций с суммами
     * - распределение по статусам групп обучения
     */
    public function companyDetail(int $companyId): array
    {
        $company = Company::findOrFail($companyId);

        // Сотрудники компании с информацией об обучении
        $employees = DB::table('employees')
            ->leftJoin('group_participants', 'employees.id', '=', 'group_participants.employee_id')
            ->leftJoin('training_groups', 'group_participants.training_group_id', '=', 'training_groups.id')
            ->leftJoin('courses', 'training_groups.course_id', '=', 'courses.id')
            ->where('employees.company_id', $companyId)
            ->whereNull('employees.deleted_at')
            ->select(
                'employees.id',
                'employees.full_name',
                'employees.email',
                DB::raw('COUNT(DISTINCT group_participants.training_group_id) as groups_count'),
                DB::raw('COALESCE(AVG(group_participants.completion_percent), 0) as avg_progress')
            )
            ->groupBy('employees.id', 'employees.full_name', 'employees.email')
            ->get()
            ->map(fn ($e) => [
                'id'            => $e->id,
                'full_name'     => $e->full_name,
                'email'         => $e->email,
                'groups_count'  => (int) $e->groups_count,
                'avg_progress'  => round((float) $e->avg_progress, 2),
            ]);

        // Спецификации компании
        $specifications = DB::table('specifications')
            ->where('company_id', $companyId)
            ->select('id', 'number', 'date')
            ->get();

        // Распределение по статусам: сколько групп в каждом статусе
        $statusDistribution = DB::table('group_participants')
            ->join('employees', 'group_participants.employee_id', '=', 'employees.id')
            ->join('training_groups', 'group_participants.training_group_id', '=', 'training_groups.id')
            ->where('employees.company_id', $companyId)
            ->select('training_groups.status', DB::raw('COUNT(DISTINCT training_groups.id) as count'))
            ->groupBy('training_groups.status')
            ->pluck('count', 'status');

        return [
            'company'              => [
                'id'   => $company->id,
                'code' => $company->code,
                'name' => $company->name,
            ],
            'employees'            => $employees,
            'specifications'       => $specifications,
            'status_distribution'  => $statusDistribution,
        ];
    }

    /**
     * Рассчитать суммарную стоимость обучения по спецификациям компании (без НДС).
     *
     * Логика расчёта:
     * 1. Находим все спецификации компании.
     * 2. Для каждой спецификации находим привязанные учебные группы.
     * 3. Для каждой группы: стоимость = актуальная цена курса * количество участников.
     * 4. Суммируем стоимости всех групп по всем спецификациям.
     */
   private function calculateCompanyTotalCost(int $companyId): float
{
    return (float) DB::table('specifications')
        ->join('training_groups', 'training_groups.specification_id', '=', 'specifications.id')
        ->join('courses', 'training_groups.course_id', '=', 'courses.id')
        ->leftJoin(
            DB::raw('(
                SELECT training_group_id, COUNT(*) as cnt
                FROM group_participants
                GROUP BY training_group_id
            ) as pc'),
            'pc.training_group_id', '=', 'training_groups.id'
        )
        ->leftJoin(
            DB::raw('(
                SELECT cp1.course_id, cp1.price
                FROM course_price cp1
                INNER JOIN (
                    SELECT course_id, MAX(valid_from) as max_valid
                    FROM course_price
                    WHERE valid_from <= CURDATE()
                    GROUP BY course_id
                ) cp2 ON cp1.course_id = cp2.course_id AND cp1.valid_from = cp2.max_valid
            ) as latest_price'),
            'latest_price.course_id', '=', 'courses.id'
        )
        ->where('specifications.company_id', $companyId)
        ->selectRaw('COALESCE(SUM(COALESCE(latest_price.price, 0) * COALESCE(pc.cnt, 0)), 0) as total')
        ->value('total');
}
}
