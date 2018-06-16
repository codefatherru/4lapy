<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Sale\Delivery\CalculationResult;
use FourPaws\DeliveryBundle\Helpers\DeliveryTimeHelper;
use FourPaws\DeliveryBundle\Service\DeliveryService;
use FourPaws\Helpers\WordHelper;

/**
 * @var array $pickup
 */

?>

<li class="b-product-information__item">
    <div class="b-product-information__title-info">Самовывоз</div>
    <div class="b-product-information__value">
        <?php if ($pickup['CODE'] === DeliveryService::INNER_PICKUP_CODE) { ?>
            <?= DeliveryTimeHelper::showByDate($pickup['DELIVERY_DATE'], 0, [
                    'DATE_FORMAT' => 'XX',
                    'SHOW_TIME' => true
            ]) ?>
            <?php if ($pickup['SHOP_COUNT']) { ?>
                из <?= $pickup['SHOP_COUNT'] . ' ' . WordHelper::declension(
                    (int)$pickup['SHOP_COUNT'],
                    ['магазина', 'магазинов', 'магазинов']
                ); ?>
            <?php } ?>
        <?php } else { ?>
            <?= DeliveryTimeHelper::showByDate($pickup['DELIVERY_DATE'], 0, ['DATE_FORMAT' => 'XX']) ?>
        <?php } ?>
    </div>
</li>
