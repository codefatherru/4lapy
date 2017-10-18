<?php

namespace FourPaws\Migrator\Entity;

use Bitrix\Sale\Basket;
use Bitrix\Sale\Compatible\Internals\EntityCompatibility;
use Bitrix\Sale\Compatible\OrderCompatibility;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use Bitrix\Sale\Order as SaleOrder;
use Bitrix\Sale\PaySystem\Manager;
use FourPaws\Migrator\Client\Catalog;
use FourPaws\Migrator\Client\Delivery;
use FourPaws\Migrator\Client\OrderProperty;
use FourPaws\Migrator\Client\User;
use FourPaws\Migrator\Entity\Exceptions\AddException;
use FourPaws\Migrator\Entity\Exceptions\UpdateException;
use FourPaws\Migrator\Utils;

/**
 * Class Order
 *
 * @package FourPaws\Migrator\Entity
 */
class Order extends AbstractEntity
{
    protected $propertyMap;
    
    /**
     * @return string
     */
    public function getTimestamp() : string
    {
        return 'DATE_UPDATE';
    }
    
    /**
     * Order constructor.
     *
     * @param string $entity
     *
     * @throws \Bitrix\Main\ArgumentException
     */
    public function __construct($entity)
    {
        $this->propertyMap = MapTable::getFullMapByEntity(OrderProperty::ENTITY_NAME);
        
        parent::__construct($entity);
    }
    
    public function setDefaults() : array
    {
        /**
         * У нас нет заказов по умолчанию
         */
        
        return [];
    }
    
    /**
     * @param string $primary
     * @param array  $data
     *
     * @return \FourPaws\Migrator\Entity\AddResult
     *
     * @throws \FourPaws\Migrator\Entity\Exceptions\AddException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Exception
     */
    public function addItem(string $primary, array $data) : AddResult
    {
        $userId = MapTable::getInternalIdByExternalId($data['USER_ID'], User::ENTITY_NAME);
        
        if (!$userId) {
            throw new AddException(sprintf('User with external id #%s is not found.', $data['USER_ID']));
        }
        
        $order = SaleOrder::create(SITE_ID, $userId, $data['CURRENCY']);
        $this->_prepareOrder($data, $order);
        $result = $this->saveOrder($order);
        
        if (!$result->getInternalId()) {
            throw new AddException(sprintf('Order with primary %s add error.', $primary));
        }
        
        MapTable::addEntity($this->entity, $primary, $result->getInternalId());
        
        return new AddResult(true, $result->getInternalId());
    }
    
    /**
     * @param string $primary
     * @param array  $data
     *
     * @return \FourPaws\Migrator\Entity\UpdateResult
     *
     * @throws \Exception
     */
    public function updateItem(string $primary, array $data) : UpdateResult
    {
        $order = SaleOrder::load($primary);
        
        $this->_prepareOrder($data, $order);
        $result = $order->save();
        
        if (!$result->getResult()) {
            throw new UpdateException(sprintf('Order with primary %s update errors: %s.',
                                              $primary,
                                              implode(', ', $result->getErrorMessages())));
        }
        
        return new UpdateResult($result->isSuccess(), $primary);
    }
    
    /**
     * @param string $field
     * @param string $primary
     * @param        $value
     *
     * @return \FourPaws\Migrator\Entity\UpdateResult
     *
     * @throws \FourPaws\Migrator\Entity\Exceptions\UpdateException
     * @throws \Exception
     */
    public function setFieldValue(string $field, string $primary, $value) : UpdateResult
    {
        throw new UpdateException('Order fields is not updated.');
    }
    
    /**
     * @param array $rawData
     *
     * @return array
     */
    protected function prepareFullData(array $rawData) : array
    {
        return EntityCompatibility::convertDateFields($rawData);
    }
    
    /**
     * @param array $rawData
     *
     * @return array
     */
    protected function _prepareOrderData(array $rawData) : array
    {
        $rawData = OrderCompatibility::convertDateFields($rawData, Utils::getOdrerDateFields());
        
        foreach ($rawData['PROPERTY_VALUES'] as $property) {
            if ((int)$property['ORDER_PROPS_ID'] === 42) {
                $rawData['COMMENTS'] .= ' ' . $property['VALUE'];
                
                break;
            }
        }
        
        $filter = function ($key) {
            return in_array($key, SaleOrder::getAvailableFields(), true);
        };
        
        return array_filter($rawData, $filter, ARRAY_FILTER_USE_KEY);
    }
    
    /**
     * @param array              $rawData
     * @param \Bitrix\Sale\Order $order
     *
     * @return \Bitrix\Sale\Order
     *
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     * @throws \Exception
     */
    protected function _prepareOrder(array $rawData, SaleOrder $order) : SaleOrder
    {
        unset($rawData['USER_ID']);
        
        $order->setPersonTypeId($rawData['PERSON_TYPE_ID']);
        $order->setFields($this->_prepareOrderData($rawData));
        try {
            $this->_addBasketToOrder($rawData['BASKET'], $order);
            $this->_addPaymentToOrder($rawData, $order);
            $this->_addDeliveryToOrder($rawData, $order);
            $this->_addPropertiesToOrder($rawData, $order);
        } catch (\Exception $e) {
            var_dump($e);
        }
        
        return $order;
    }
    
    /**
     * @param array              $rawBasketList
     * @param \Bitrix\Sale\Order $order
     *
     * @return \Bitrix\Sale\Order
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \FourPaws\Migrator\Entity\Exceptions\AddException
     * @throws \Exception
     */
    protected function _addBasketToOrder(array $rawBasketList, SaleOrder $order) : SaleOrder
    {
        $basket = Basket::loadItemsForOrder($order);
        
        foreach ($rawBasketList as $rawBasket) {
            $productId = MapTable::getInternalIdByExternalId($rawBasket['PRODUCT_ID'], Catalog::ENTITY_NAME);
            
            $rawBasket['USER_ID'] = $order->getUserId();
            $rawBasket            = $this->_prepareBasketData($rawBasket);
            
            if ($item = $basket->getExistsItem($rawBasket['MODULE'], $productId)) {
                $item->setFieldsNoDemand($rawBasket);
                $item->save();
            } else {
                $item = \Bitrix\Sale\BasketItem::create($basket, $rawBasket['MODULE'], $productId);
                $item->setFieldsNoDemand($rawBasket);
                $result = $item->save();
                
                if (!$result->isSuccess()) {
                    throw new AddException(sprintf('Basket product #%s add error: %s',
                                                   $productId,
                                                   implode(', ', $result->getErrorMessages())));
                }
            }
        }
        
        if ($order->isNotEmptyBasket()) {
            $basket->setOrder($order);
        } else {
            $order->setBasket($basket);
        }
        
        $basket->save();
        
        return $order;
    }
    
    /**
     * @param array              $properties
     * @param \Bitrix\Sale\Order $order
     *
     * @return \Bitrix\Sale\Order
     *
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Exception
     */
    protected function _addPropertiesToOrder(array $properties, SaleOrder $order) : SaleOrder
    {
        $propertyCollection = $order->getPropertyCollection();
        $propertyCollection->setValuesFromPost($this->_preparePropertiesData($properties['PROPERTY_VALUES']), []);
        $propertyCollection->save();
        
        return $order;
    }
    
    /**
     * На данный момент мы умеем работать только с простыми службами доставки
     *
     * @param array              $data
     * @param \Bitrix\Sale\Order $order
     *
     * @return \Bitrix\Sale\Order
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\SystemException
     * @throws \Exception
     */
    protected function _addDeliveryToOrder(array $data, SaleOrder $order) : SaleOrder
    {
        $shipmentCollection = $order->getShipmentCollection();
        $deliveryId         = MapTable::getInternalIdByExternalId($data['DELIVERY_ID'], Delivery::ENTITY_NAME);
        
        $service = DeliveryManager::getObjectById($deliveryId);
        
        /**
         * @var \Bitrix\Sale\Shipment $shipment
         */
        $shipment = $shipmentCollection->count() > 0 ? $shipmentCollection[0] : $shipmentCollection->createItem();
        $fields   = [
            'DELIVERY_NAME'         => $service->getName(),
            'CURRENCY'              => $data['CURRENCY'],
            'PRICE_DELIVERY'        => $data['PRICE_DELIVERY'],
            'BASE_PRICE_DELIVERY'   => $data['PRICE_DELIVERY'],
            'CUSTOM_PRICE_DELIVERY' => 'Y',
            'TRACKING_NUMBER'       => $data['TRACKING_NUMBER'],
        ];
        $shipment->setFieldsNoDemand($fields);
        $shipmentCollection->setOrder($order);
        $shipmentCollection->save();
        
        return $order;
    }
    
    /**
     * @param array              $data
     * @param \Bitrix\Sale\Order $order
     *
     * @return \Bitrix\Sale\Order
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Exception
     */
    protected function _addPaymentToOrder(array $data, SaleOrder $order) : SaleOrder
    {
        if (!$data['PAY_SYSTEM_ID']) {
            return $order;
        }
        
        $paymentCollection = $order->getPaymentCollection();
        $sum               = $data['PS_SUM'];
        $service           = Manager::getObjectById($data['PAY_SYSTEM_ID']);
        
        $payment = $paymentCollection->count() > 0 ? $paymentCollection[0] : $paymentCollection->createItem($service);
        $payment->setFieldsNoDemand([
                                        'SUM'             => $sum,
                                        'PAY_SYSTEM_NAME' => $service->getField('NAME'),
                                        'PAY_SYSTEM_ID'   => $data['PAY_SYSTEM_ID'],
                                    ]);
        
        $payment->setPaid($data['PAYED']);
        
        $data = OrderCompatibility::convertDateFields($data, Utils::getPaymentDateFields());
        $data = Utils::replaceFields($data, Utils::getPaymentReplaceFields());
        $data = Utils::clearFields($data, Utils::getPaymentAvailableFields());
        
        unset($data['SUM'], $data['PAY_SYSTEM_NAME'], $data['PAY_SYSTEM_ID']);
        
        $payment->setFieldsNoDemand($data);
        $paymentCollection->setOrder($order);
        $paymentCollection->save();
        
        return $order;
    }
    
    /**
     * @param array $data
     *
     * @return array
     *
     * @throws \Bitrix\Main\ArgumentException
     */
    protected function _prepareBasketData(array $data) : array
    {
        $fields = [
            'PRICE',
            'PRODUCT_ID',
            'PRICE',
            'CURRENCY',
            'WEIGHT',
            'QUANTITY',
            'NAME',
            'DETAIL_PAGE_URL',
            'PRODUCT_XML_ID',
            'DISCOUNT_NAME',
            'DISCOUNT_VALUE',
            'VAT_RATE',
            'DIMENSIONS',
        ];
        
        $filter = function ($key) use ($fields) {
            return in_array($key, $fields, true);
        };
        
        $data = array_filter($data, $filter, ARRAY_FILTER_USE_KEY);
        
        return $data;
    }
    
    /**
     * @param array $data
     *
     * @return array
     */
    protected function _preparePropertiesData(array $data) : array
    {
        array_walk($data,
            function ($rawProperty) {
                return [
                    'NAME'           => $rawProperty['NAME'],
                    'ORDER_PROPS_ID' => $this->propertyMap['ORDER_PROPS_ID'],
                    'VALUE'          => $rawProperty['VALUE'],
                ];
            });
        
        return $data;
    }
}
