<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\SapBundle\Serialization;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\XmlDeserializationVisitor;
use JMS\Serializer\XmlSerializationVisitor;

/**
 * Class SapPositionStringHandler
 *
 * @package FourPaws\SapBundle\Serialization
 */
class SapPositionStringHandler implements SubscribingHandlerInterface
{
    protected const PAD_LENGTH = 6;

    /**
     * Return format:
     *
     *      array(
     *          array(
     *              'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
     *              'format' => 'json',
     *              'type' => 'DateTime',
     *              'method' => 'serializeDateTimeToJson',
     *          ),
     *      )
     *
     * The direction and method keys can be omitted.
     *
     * @return array
     */
    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format'    => 'xml',
                'type'      => 'sap_position',
                'method'    => 'serialize',
            ],
            [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format'    => 'xml',
                'type'      => 'sap_position',
                'method'    => 'deserialize',
            ],
        ];
    }

    /**
     * Позиция увеличивается по 10
     *
     * @param XmlSerializationVisitor $visitor
     * @param                         $data
     * @param array                   $type
     * @param Context                 $context
     *
     * @return mixed
     */
    public function serialize(XmlSerializationVisitor $visitor, $data, array $type, Context $context)
    {
        $data = \str_pad((int)$data * 10, static::PAD_LENGTH, '0', \STR_PAD_LEFT);

        return $visitor->getNavigator()->accept(
            $data,
            [
                'name'   => 'string',
                'params' => $type['params'],
            ],
            $context
        );
    }

    /**
     * @param XmlDeserializationVisitor $visitor
     * @param $data
     * @param array $type
     * @param Context $context
     *
     * @return mixed
     */
    public function deserialize(XmlDeserializationVisitor $visitor, $data, array $type, Context $context)
    {
        $data = $data instanceof \SimpleXMLElement ? $data->__toString() : $data;
        $data = (int)$data / 10;

        return $visitor->getNavigator()->accept(
            (int)$data,
            [
                'name'   => 'int',
                'params' => $type['params'],
            ],
            $context
        );
    }
}
