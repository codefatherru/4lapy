<?php

namespace FourPaws\Migrator\Converter;

use FourPaws\Migrator\Converter\Exception\ReferenceException;

/**
 * Class MetroToReference
 *
 * Специфичный для проекта конвертер
 * Сохраняет в справочники связку метро/ветка
 *
 * @package FourPaws\Migrator\Converter
 */
final class MetroToReference extends StringToReference
{
    private $branchReference;
    
    const CODE_FIELD_NAME = 'PROPERTY_COUNTRY_NAME';
    
    public function convert(array $data) : array
    {
        $this->setDataClass();
        
        $fieldName = $this->getFieldName();
        
        if (!$data[$fieldName]) {
            return $data;
        }
        
        $result = $this->searchValue($data[$fieldName]);
        
        if (!$result) {
            $result = $this->addValue($data[self::CODE_FIELD_NAME], $data[$fieldName]);
            unset($data[self::CODE_FIELD_NAME]);
        }
        
        $data[$fieldName] = $result;
        
        return $data;
    }
    
    /**
     * @param $code
     * @param $name
     *
     * @return string
     *
     * @throws ReferenceException
     */
    protected function addValue(string $code, string $name) : string
    {
        $fields = [
            $this->getFieldToSearch() => $code,
            self::FIELD_EXTERNAL_KEY  => $name,
        ];
        
        $result = $this->getDataClass()::add($fields);
        
        if (!$result->isSuccess()) {
            throw new ReferenceException('Reference value add error: ' . implode(', ', $result->getErrorMessages()));
        }
        
        self::$referenceValues[$this->getReferenceCode()][] = $fields;
        
        return $code;
    }
    
    /**
     * @param $code
     * @param $fieldToSearch
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function searchValue($code, $fieldToSearch = self::FIELD_EXTERNAL_KEY) : string
    {
        return parent::searchValue($code, self::FIELD_EXTERNAL_KEY);
    }
    
    public function __construct(string $fieldName)
    {
        parent::__construct($fieldName);
    }
}
