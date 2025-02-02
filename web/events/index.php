<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetPageProperty('title', 'Запись на услуги');
$APPLICATION->SetPageProperty('description', '');
$APPLICATION->SetTitle('Запись на услуги');
?>
<div class="flagship-store-page">
    <?php
    $APPLICATION->IncludeComponent(
        'articul:flagman.menu',
        '',
        [
            'SHOW_GROOMING' => 'N',
            'SHOW_LECTION'  => 'N',
            'SHOW_TRAINING' => 'N',
        ],
        false
    );
    
    $APPLICATION->IncludeComponent(
        'articul:flagman.grooming',
        'grooming',
        [],
        false
    );
    
    $APPLICATION->IncludeComponent(
        'articul:flagman.training',
        'training',
        [],
        false
    );
    
    $APPLICATION->IncludeComponent(
        'articul:flagman.lection',
        '',
        [],
        false
    );
    ?>
</div>

<? /*
<script>
    window.addEventListener('load', function() {
        var items = document.querySelectorAll('.fashion-page .measure_dog__button.js-scroll-to-catalog, .fashion-page .b-news-item__link, .fashion-page .measure_dog__steps a, .b-main-slider a.b-main-item__link-main');

        for (var i = 0; i < items.length; i++) {
            items[i].setAttribute('target', '_blank');
            items[i].target = '_blank';
        }
    });
</script>
*/ ?>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
