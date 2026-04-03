<?php

namespace Modules\Xml\Services\Parsers;

use SimpleXMLElement;

/**
 * Парсер XML-файла участника обучения (Edu_Participant).
 *
 * Структура источника (Global ERP):
 *   <Edu_Participant>
 *     <id>          — внешний ID в ERP
 *     <sCode>       — табельный номер (employee_code)
 *     <sLastName>   — фамилия
 *     <sMiddleName> — имя (в ERP поле названо «Middle», но содержит имя)
 *     <sFirstName>  — отчество (в ERP — «First», но содержит отчество)
 *     <sFIO>        — ФИО целиком
 *     <idOrganization>   — внешний ID компании
 *     <idOrganizationHL> — название компании (human-label)
 *   </Edu_Participant>
 */
class ParticipantXmlParser
{
    /**
     * Разбирает XML-строку и возвращает нормализованный массив данных.
     *
     * @throws \InvalidArgumentException при ошибке разбора или неверном корне
     */
    public function parseMultiple(string $xmlContent): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = array_map(fn($e) => trim($e->message), libxml_get_errors());
            libxml_clear_errors();
            throw new \InvalidArgumentException('XML parse error: ' . implode('; ', $errors));
        }

        $participants = [];

        // Если корень — одиночный участник
        if ($xml->getName() === 'Edu_Participant') {
            $participants[] = $this->extractParticipant($xml);

        // Если корень — блок участников
        } elseif ($xml->getName() === 'Participants') {
            foreach ($xml->children() as $child) {
                if ($child->getName() !== 'Edu_Participant') {
                    continue; // игнорируем неожиданные теги
                }
                $participants[] = $this->extractParticipant($child);
            }

        } else {
            throw new \InvalidArgumentException(
                "Неподдерживаемый корневой тег: <{$xml->getName()}>. Ожидались: Edu_Participant или Participants."
            );
        }

        return $participants;
    }

    private function extractParticipant(SimpleXMLElement $xml): array
    {
        return [
            'external_id'      => (string) $xml->id,
            'employee_code'    => (string) $xml->sCode,
            'last_name'        => (string) $xml->sLastName,
            // В Global ERP sMiddleName хранит имя, sFirstName — отчество
            'first_name'       => (string) $xml->sMiddleName,
            'middle_name'      => (string) $xml->sFirstName,
            'full_name'        => (string) $xml->sFIO,
            // Данные компании
            'company_external_id'   => (string) $xml->idOrganization,
            'company_name'          => (string) $xml->idOrganizationHL,
        ];
    }
}