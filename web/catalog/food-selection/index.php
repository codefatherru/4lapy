<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Подбор корма');

echo '<div class="b-food">
    <div class="b-container">
        <h1 class="b-title b-title--h1 b-title--block b-title--food js-food-permutation-tablet">';
$APPLICATION->ShowTitle(false);
echo'</h1>'
?>
<?php $APPLICATION->IncludeComponent(
    'fourpaws:catalog.food.selection',
    '',
    []
); ?>
<?php
echo '</div>
</div>
<div class="b-preloader b-preloader--fixed">
    <div class="b-preloader__spinner">
        <img class="b-preloader__image" src="/static/build/images/inhtml/spinner.svg" alt="spinner" title=""/>
    </div>
</div>';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>