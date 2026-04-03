<?php

namespace Modules\Xml\Services\Importers;

use Illuminate\Support\Facades\DB;
use Modules\Xml\Models\XmlImportLog;

/**
 * Импортёр курсов обучения.
 *
 * Стратегия:
 *  1. По code ищем курс. Если не найден — создаём.
 *     Если найден — обновляем поля (title, description, duration_days).
 *
 *  2. Цена (course_price): реализована временнАя зависимость (историчность).
 *     При импорте:
 *      - Если нет актуальной цены — создаём новую запись (valid_from = сегодня).
 *      - Если последняя цена отличается — закрываем старую (valid_to = вчера)
 *        и создаём новую (valid_from = сегодня).
 *      - Если цена не изменилась — ничего не делаем.
 */
class CourseImporter
{
    /**
     * @param  array  $data  Нормализованный массив из CourseXmlParser
     * @param  int    $batchId
     * @return array{operation_type: string, status: string, message: string}
     */
    public function import(array $data, int $batchId): array
    {
        // ----------------------------------------------------------------
        // 1. Курс: найти или создать по code
        // ----------------------------------------------------------------
        $existing = DB::table('courses')
            ->where('code', $data['code'])
            ->whereNull('deleted_at')
            ->first();

        $operation = XmlImportLog::OP_SKIP;

        if (! $existing) {
            $courseId = DB::table('courses')->insertGetId([
                'code'          => $data['code'],
                'title'         => $data['title'],
                'description'   => $data['description'],
                'duration_days' => $data['duration_days'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $operation = XmlImportLog::OP_CREATE;
        } else {
            $courseId = $existing->id;

            $courseChanged = $existing->title         !== $data['title']
                          || $existing->description   !== $data['description']
                          || (int) $existing->duration_days !== $data['duration_days'];

            if ($courseChanged) {
                DB::table('courses')
                    ->where('id', $courseId)
                    ->update([
                        'title'         => $data['title'],
                        'description'   => $data['description'],
                        'duration_days' => $data['duration_days'],
                        'updated_at'    => now(),
                    ]);

                $operation = XmlImportLog::OP_UPDATE;
            }
        }

        // ----------------------------------------------------------------
        // 2. Цена: историчная запись в course_price
        // ----------------------------------------------------------------
        $priceUpdated = false;

        if ($data['price'] !== null) {
            $priceUpdated = $this->syncPrice($courseId, $data['price']);

            if ($priceUpdated && $operation === XmlImportLog::OP_SKIP) {
                $operation = XmlImportLog::OP_UPDATE;
            }
        }

        if ($operation === XmlImportLog::OP_SKIP) {
            return [
                'operation_type' => XmlImportLog::OP_SKIP,
                'status'         => XmlImportLog::STATUS_SKIPPED,
                'message'        => "Без изменений: {$data['title']} (код: {$data['code']})",
            ];
        }

        $label = $operation === XmlImportLog::OP_CREATE ? 'Создан' : 'Обновлён';

        return [
            'operation_type' => $operation,
            'status'         => XmlImportLog::STATUS_SUCCESS,
            'message'        => "{$label} курс: {$data['title']} (код: {$data['code']})"
                              . ($priceUpdated ? ', цена обновлена' : ''),
        ];
    }

    /**
     * Синхронизирует цену курса с поддержкой историчности.
     *
     * Возвращает true, если была создана новая запись о цене.
     */
    private function syncPrice(int $courseId, string $newPrice): bool
    {
        $today     = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Ищем текущую активную цену (valid_to IS NULL или valid_to >= today)
        $currentPrice = DB::table('course_price')
            ->where('course_id', $courseId)
            ->where('valid_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $today);
            })
            ->orderByDesc('valid_from')
            ->first();

        // Если цена не изменилась — ничего не делаем
        if ($currentPrice && bccomp((string) $currentPrice->price, $newPrice, 2) === 0) {
            return false;
        }

        // Закрываем текущую цену (если есть)
        if ($currentPrice) {
            DB::table('course_price')
                ->where('id', $currentPrice->id)
                ->update(['valid_to' => $yesterday]);
        }

        // Создаём новую запись цены
        DB::table('course_price')->insert([
            'course_id'  => $courseId,
            'price'      => $newPrice,
            'valid_from' => $today,
            'valid_to'   => null,
            'created_at' => now(),
        ]);

        return true;
    }
}