<?php

namespace FourPaws\External\Manzana\Model;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlElement;
use JMS\Serializer\Annotation\XmlList;
use JMS\Serializer\Annotation\XmlNamespace;
use JMS\Serializer\Annotation\XmlRoot;
use FourPaws\External\Manzana\Model\ChequePayment;

/**
 * Class Cheque
 * Элемент результата методов getCheques, getChequesByContactId (contact_cheques)
 *
 * @package FourPaws\External\Manzana\Model
 *
 * @ExclusionPolicy("none")
 * @XmlNamespace(uri="http://www.w3.org/2001/XMLSchema-instance", prefix="xsi")
 * @XmlRoot("Cheque")
 */
class Cheque
{
    /**
     * ID чека
     *
     * @XmlElement(cdata=false)
     * @Type("string")
     * @SerializedName("ChequeId")
     */
    public $chequeId;
    
    /**
     * Номер чека
     *
     * @XmlElement(cdata=false)
     * @Type("string")
     * @SerializedName("ChequeNumber")
     */
    public $chequeNumber;
    
    /**
     * Дата
     *
     * @Type("manzana_date_time_short")
     * @SerializedName("Date")
     */
    public $date;
    
    /**
     * Название чека
     *
     * @XmlElement(cdata=false)
     * @Type("string")
     * @SerializedName("ChequeName")
     */
    public $chequeName;
    
    /**
     * Скидка
     *
     * @XmlElement(cdata=false)
     * @Type("float")
     * @SerializedName("Discount")
     */
    public $discount;
    
    /**
     * Сумма
     *
     * @XmlElement(cdata=false)
     * @Type("float")
     * @SerializedName("Summ")
     */
    public $sum;
    
    /**
     * Сумма со скидкой
     *
     * @XmlElement(cdata=false)
     * @Type("float")
     * @SerializedName("SummDiscounted")
     */
    public $sumDiscounted;
    
    /**
     * Название магазина !!! не код !!!
     *
     * @XmlElement(cdata=false)
     * @Type("string")
     * @SerializedName("BusinessUnit")
     */
    public $businessUnit;
    
    /**
     * ID партнёра
     *
     * @XmlElement(cdata=false)
     * @Type("string")
     * @SerializedName("OrganizationId")
     */
    public $organizationId;
    
    /**
     * Название партнёра
     *
     * @XmlElement(cdata=false)
     * @Type("string")
     * @SerializedName("OrganizationName")
     */
    public $organizationName;
    
    /**
     * Дата обработки чека
     *
     * @Type("manzana_date_time_short")
     * @SerializedName("Processed")
     */
    public $processed;
    
    /**
     * Идентификатор наличия позиций в чеке
     * 1-No (Нет) 2-Yes (Да)
     *
     * @XmlElement(cdata=false)
     * @Type("int")
     * @SerializedName("HasItems")
     */
    public $hasItems;
    
    /**
     * Код типа операции
     * 1-Purchase (Продажа) 2-Return (Возврат)
     *
     * @XmlElement(cdata=false)
     * @Type("int")
     * @SerializedName("OperationTypeCode")
     */
    public $operationTypeCode;
    
    /**
     * Название типа операции
     *
     * @XmlElement(cdata=false)
     * @Type("string")
     * @SerializedName("OperationTypeText")
     */
    public $operationTypeText;
    
    /**
     * Начисленный бонус
     *
     * @XmlElement(cdata=false)
     * @Type("float")
     * @SerializedName("Bonus")
     */
    public $bonus;
    
    /**
     * Оплачено бонусами
     *
     * @XmlElement(cdata=false)
     * @Type("float")
     * @SerializedName("PaidByBonus")
     */
    public $paidByBonus;
    
    /**
     * @Type("FourPaws\External\Manzana\Model\ChequePayments")
     * @SerializedName("Payments")
     */
    public $payments;

    /**
     * @return bool
     */
    public function hasItemsBool()
    {
        return (int)$this->hasItems === 2;
    }

    /**
     * @return array
     */
    public function getPaymentsArray()
    {
        return $this->payments->chequePayments ? $this->payments->chequePayments->toArray() : [];
    }
}
