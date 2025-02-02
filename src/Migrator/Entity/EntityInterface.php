<?php

namespace FourPaws\Migrator\Entity;

interface EntityInterface
{
    /**
     * Метод должен вернуть маппинг сущностей по умолчанию, которые не нужно импортировать (или которые уже существуют)
     *
     * @return array
     */
    public function setDefaults() : array;
    
    /**
     * @return string
     */
    public function getPrimary() : string;
    
    /**
     * Метод должен вернуть имя сущности
     *
     * @return string
     */
    public function getEntity() : string;
    
    /**
     * Метод должен вернуть имя поля, в котором передаётся timestamp
     *
     * @return string
     */
    public function getTimestamp() : string;
    
    /**
     * @param string $primary
     * @param array  $item
     *
     * @return \FourPaws\Migrator\Entity\AddResult
     */
    public function addItem(string $primary, array $item) : AddResult;
    
    /**
     * @param string $primary
     * @param array  $item
     *
     * @return \FourPaws\Migrator\Entity\UpdateResult
     */
    public function updateItem(string $primary, array $item) : UpdateResult;
    
    /**
     * @param string $primary
     * @param array  $item
     *
     * @return \FourPaws\Migrator\Entity\Result
     */
    public function addOrUpdateItem(string $primary, array $item) : Result;
    
    /**
     * @param array $item
     *
     * @return string
     */
    public function getPrimaryByItem(array $item) : string;
    
    /**
     * @param array $item
     *
     * @return string
     */
    public function getTimestampByItem(array $item) : string;
    
    /**
     * @param array  $data
     * @param string $internal
     * @param string $entity
     */
    public function setInternalKeys(array $data, string $internal, string $entity);
    
    /**
     * @param string $field
     * @param string $primary
     * @param        $value
     *
     * @return \FourPaws\Migrator\Entity\UpdateResult
     */
    public function setFieldValue(string $field, string $primary, $value) : UpdateResult;
}
