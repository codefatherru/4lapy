<?php

namespace FourPaws\SapBundle\Service;

use Adv\Bitrixtools\Tools\Log\LazyLoggerAwareTrait;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FourPaws\BitrixOrm\Model\HlbReferenceItem;
use FourPaws\SapBundle\Dto\In\Offers\Material;
use FourPaws\SapBundle\Dto\In\Offers\Property;
use FourPaws\SapBundle\Dto\In\Offers\PropertyValue;
use FourPaws\SapBundle\Enum\SapOfferProperty;
use FourPaws\SapBundle\Exception\CantCreateReferenceItem;
use FourPaws\SapBundle\Exception\LogicException;
use FourPaws\SapBundle\ReferenceDirectory\SapReferenceStorage;
use Psr\Log\LoggerAwareInterface;

class ReferenceService implements LoggerAwareInterface
{
    use LazyLoggerAwareTrait;

    /**
     * @var SapReferenceStorage
     */
    private $referenceStorage;
    /**
     * @var SlugifyInterface
     */
    private $slugify;

    public function __construct(SapReferenceStorage $referenceStorage, SlugifyInterface $slugify)
    {
        $this->referenceStorage = $referenceStorage;
        $this->slugify = $slugify;
    }

    /**
     * @param string $propertyCode
     * @param string $xmlId
     * @param string $name
     *
     * @throws \FourPaws\SapBundle\Exception\LogicException
     * @throws \FourPaws\SapBundle\Exception\CantCreateReferenceItem
     * @throws \FourPaws\SapBundle\Exception\NotFoundDataManagerException
     * @return HlbReferenceItem
     */
    public function getOrCreate(string $propertyCode, string $xmlId, string $name): HlbReferenceItem
    {
        $result = $this->get($propertyCode, $xmlId);
        if (!$result) {
            $result = $this->create($propertyCode, $xmlId, $name);
        }
        if (!$result) {
            throw new LogicException('For some reason created item was not get from dataManager');
        }

        return $result;
    }

    /**
     * @param string $propertyCode
     * @param string $xmlId
     *
     * @return null|HlbReferenceItem
     */
    public function get(string $propertyCode, string $xmlId)
    {
        return $this->referenceStorage->findByXmlId($propertyCode, $xmlId);
    }

    /**
     * @param string $propertyCode
     * @param string $xmlId
     * @param string $name
     *
     * @throws \FourPaws\SapBundle\Exception\CantCreateReferenceItem
     * @throws \FourPaws\SapBundle\Exception\NotFoundDataManagerException
     * @return null|HlbReferenceItem
     */
    public function create(string $propertyCode, string $xmlId, string $name)
    {
        $dataManager = $this->referenceStorage->getReferenceRegistry()->get($propertyCode);
        $fields = [
            'UF_CODE'   => $this->getUniqueCode($propertyCode, $name),
            'UF_XML_ID' => $xmlId,
            'UF_NAME'   => $name,
        ];
        $addResult = $dataManager::add($fields);

        if ($addResult->isSuccess()) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->log()->debug(
                sprintf('Создано значение справочника для свойства %s: %s', $propertyCode, $xmlId),
                $fields
            );
            return $this->get($propertyCode, $xmlId);
        }
        throw new CantCreateReferenceItem(implode(' ', $addResult->getErrorMessages()));
    }

    /**
     * @param Property $property
     *
     * @throws \FourPaws\SapBundle\Exception\NotFoundDataManagerException
     * @throws \FourPaws\SapBundle\Exception\LogicException
     * @throws \FourPaws\SapBundle\Exception\CantCreateReferenceItem
     * @return Collection|HlbReferenceItem[]
     */
    public function getPropertyValueHlbElement(Property $property): Collection
    {
        $values = $property->getValues()->filter(function (PropertyValue $propertyValue) {
            return $propertyValue->getCode() && $propertyValue->getName();
        });
        $collection = new ArrayCollection();
        foreach ($values as $value) {
            $collection->add($this->getOrCreate($property->getCode(), $value->getCode(), $value->getName()));
        }

        return $collection;
    }

    /**
     * @param Material $material
     *
     * @throws \FourPaws\SapBundle\Exception\NotFoundDataManagerException
     * @throws \FourPaws\SapBundle\Exception\LogicException
     * @throws \FourPaws\SapBundle\Exception\CantCreateReferenceItem
     */
    public function fillFromMaterial(Material $material)
    {
        foreach ($material->getProperties()->getProperties() as $property) {
            if ($this->referenceStorage->getReferenceRegistry()->has($property->getCode())) {
                $this->getPropertyValueHlbElement($property);
            }
        }
    }

    public function getOfferReferenceProperties(Material $material)
    {
        return [
            'COLOUR'           => $this->getPropertyBitrixValue(SapOfferProperty::COLOUR, $material),
            'KIND_OF_PACKING'  => $this->getPropertyBitrixValue(SapOfferProperty::KIND_OF_PACKING, $material),
            'CLOTHING_SIZE'    => $this->getPropertyBitrixValue(SapOfferProperty::CLOTHING_SIZE, $material),
            'VOLUME_REFERENCE' => $this->getPropertyBitrixValue(SapOfferProperty::VOLUME, $material),
            'SEASON_YEAR'      => $this->getPropertyBitrixValue(SapOfferProperty::SEASON_YEAR, $material),
        ];
    }

    /**
     * @param string $propertyCode
     * @param string $name
     *
     * @return string
     */
    protected function getUniqueCode(string $propertyCode, string $name): string
    {
        $i = 0;
        $code = $this->slugify->slugify($name);
        do {
            if ($i > 10) {
                $resultCode = md5($code . microtime());
                break;
            }
            $resultCode = $code . ($i > 0 ? $i : '');
            $result = $this->referenceStorage->findByCallable(
                $propertyCode,
                function (HlbReferenceItem $item) use ($resultCode) {
                    return $item->getCode() === $resultCode;
                }
            );
        } while ($result->count());
        return $resultCode;
    }

    /**
     * @param string   $code
     * @param Material $material
     * @param bool     $multiple
     *
     * @throws \FourPaws\SapBundle\Exception\NotFoundDataManagerException
     * @throws \FourPaws\SapBundle\Exception\LogicException
     * @throws \RuntimeException
     * @throws \FourPaws\SapBundle\Exception\CantCreateReferenceItem
     * @return array|string
     */
    protected function getPropertyBitrixValue(string $code, Material $material, bool $multiple = false)
    {
        $hlbElements = $this
            ->getPropertyValueHlbElement($material->getProperties()->getProperty($code));

        $result = $hlbElements->map(function (HlbReferenceItem $item) {
            return $item->getXmlId();
        });

        if ($multiple) {
            return $result->toArray();
        }
        if ($result->count() > 1) {
            $this
                ->log()
                ->error(
                    sprintf('Get more than one value for not multiple property %s.', $code),
                    $result->toArray()
                );
        }
        return $result->first() ?: '';
    }
}
