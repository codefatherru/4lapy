<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use FourPaws\DeliveryBundle\Service\DeliveryService;
use FourPaws\Helpers\WordHelper;
use Bitrix\Sale\Delivery\CalculationResult;

/**
 * @var array $pickup
 */

$pickupDays = (int)$pickup['PERIOD_FROM'];
switch ($pickup['PERIOD_TYPE']) {
    case CalculationResult::PERIOD_TYPE_HOUR:
        $date = new DateTime();
        $date->modify('+1 hour');
        if ($date->format('H') < 21) {
            $pickupDateString = 'Сегодня с ' . $date->format('H:i');
        } else {
            $pickupDateString = 'Завтра с 10:00';
        }
        if ($date->format('z') == date('z')) {
            $pickupDateString = 'Сегодня с ' . $date->format('H:i');
        }
        break;
    case CalculationResult::PERIOD_TYPE_DAY:
        switch ($pickupDays) {
            case 0:
                $pickupDateString = 'Сегодня';
                break;
            case 1:
                $pickupDateString = 'Завтра';
                break;
            default:
                $pickupDateString = 'В течение ' . $pickupDays . ' ' . WordHelper::declension(
                        $pickupDays,
                        ['дня', 'дней', 'дней']
                    );
        }
}
?>

<li class="b-product-information__item">
    <div class="b-product-information__title-info">Самовывоз
    </div>
    <div class="b-product-information__value">
        <?php if ($pickup['CODE'] == DeliveryService::INNER_PICKUP_CODE) { ?>
            <?= $pickupDateString ?>
            <?php if ($pickup['SHOP_COUNT']) { ?>
                из <?= $pickup['SHOP_COUNT'] . ' ' . WordHelper::declension(
                    (int)$pickup['SHOP_COUNT'],
                    ['магазина', 'магазинов', 'магазинов']
                ); ?>
            <?php } ?>
        <?php } else { ?>
            В течение <?= $pickup['PERIOD_FROM'] ?>&nbsp;<?php WordHelper::declension(
                $pickup['PERIOD_FROM'],
                ['день', 'дня', 'дней']
            ) ?>
        <?php } ?>
    </div>
</li>
