<?php

namespace Modules\Xml\Services\Parsers;

use SimpleXMLElement;

/**
 * Парсер XML-файла курса обучения (Edu_Course).
 *
 * Структура источника (Global ERP):
 *   <Edu_Course>
 *     <id>              — внешний ID в ERP
 *     <sCode>           — код курса
 *     <sCourseHL>       — название курса (human-label)
 *     <sDescription>    — описание
 *     <nDurationInDays> — длительность в днях
 *     <nPricePerPerson> — цена за человека (руб.)
 *   </Edu_Course>
 */
class CourseXmlParser
{
    /**
     * Разбирает XML-строку и возвращает нормализованный массив данных.
     *
     * @throws \InvalidArgumentException при ошибке разбора или неверном корне
     */
    public function parse(string $xmlContent): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = array_map(fn($e) => trim($e->message), libxml_get_errors());
            libxml_clear_errors();
            throw new \InvalidArgumentException('XML parse error: ' . implode('; ', $errors));
        }

        if ($xml->getName() !== 'Edu_Course') {
            throw new \InvalidArgumentException(
                "Ожидался корневой тег <Edu_Course>, получен <{$xml->getName()}>"
            );
        }

        return $this->extractCourse($xml);
    }

    private function extractCourse(SimpleXMLElement $xml): array
    {
        $price = (string) $xml->nPricePerPerson;

        return [
            'external_id'   => (string) $xml->id,
            'code'          => (string) $xml->sCode,
            'title'         => (string) $xml->sCourseHL,
            'description'   => (string) $xml->sDescription ?: null,
            'duration_days' => (int) $xml->nDurationInDays,
            // Цена — decimal(12,2), храним как строку для точности
            'price'         => $price !== '' ? number_format((float) $price, 2, '.', '') : null,
        ];
    }
}