<?php

namespace FourPaws\Catalog\Collection;

use FourPaws\BitrixOrm\Collection\IblockElementCollection;
use FourPaws\Catalog\Model\Banner;
use FourPaws\Catalog\Query\BannerQuery;
use Generator;

class BannerCollection extends IblockElementCollection
{
    protected function fetchElement(): Generator
    {
        $props = (new BannerQuery())->getProperties();
        while ($fields = $this->getCdbResult()->GetNextElement()) {
            $result = $fields->GetFields();
            $result['PROPERTIES'] = $fields->GetProperties();
            foreach ($result['PROPERTIES'] as $key => &$arProp) {
                if (in_array($key, $props)) {
                    if ($arProp['PROPERTY_TYPE'] == 'F' && $arProp['VALUE'] == '') {
                        $val = null;
                    } else if ($key === 'TYPE') {
                        $val = $arProp['VALUE_XML_ID'];
                    } else {
                        $val = $arProp['VALUE'];
                    }
                    $result['PROPERTY_' . $key . '_VALUE'] = $val;
                    $result['~PROPERTY_' . $key . '_VALUE'] = $val;
                    if (isset($arProp['PROPERTY_VALUE_ID'])) {
                        $result['PROPERTY_' . $key . '_VALUE_ID'] = $arProp['PROPERTY_VALUE_ID'];
                        $result['~PROPERTY_' . $key . '_VALUE_ID'] = $arProp['PROPERTY_VALUE_ID'];
                    }
                }
            }
            unset($result['PROPERTIES']);
            yield new Banner($result);
        }
    }

}
