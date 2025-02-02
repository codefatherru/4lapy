<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use FourPaws\App\Application;
use FourPaws\EcommerceBundle\Service\RetailRocketService;

/**
 * @todo вынести всё в бандл
 */
$container = Application::getInstance()->getContainer();
$retailRocket = $container->get(RetailRocketService::class);
?>
<script data-skip-moving="true">
    <?= $retailRocket->renderTracking() ?>
    window.dataLayer = window.dataLayer || [];

    (function (w, d, s, l, i) {
        w[l] = w[l] || [];
        w[l].push({
            'gtm.start':
                new Date().getTime(), event: 'gtm.js'
        });
        var f = d.getElementsByTagName(s)[0],
            j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
        j.async = true;
        j.src =
            'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
        f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', 'GTM-NXNPF4Z');
</script>
<!-- Global site tag (gtag.js) - Google Ads: 832765585 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-832765585"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'AW-832765585');
</script>

<?php /* счетчик exponea */ ?>
<script>(function(b,c){if(!b.exponea){var a=function(a,g){function k(d){return function(){var e=arguments;""==a&&"initialize"==d&&e&&e[0].modify&&e[0].modify.overlay&&"loading"==c.readyState&&(c.write('<div id="__inf__overlay__" style="position:absolute;background:#fff;left:0;top:0;width:100%;height:100%;z-index:1000000"></div>'),setTimeout(function(){var a=c.getElementById("__inf__overlay__");a&&c.body.removeChild(a);res.__=!0},e[0].modify.delay||500));b.exponea._.push([a+d,arguments])}}var h=g.split(" "),f,d;res={_:[]};for(d=0;d<h.length;d++)f=h[d],res[f]=k(f);return res};b.exponea=a("","initialize identify update track trackLink trackEnhancedEcommerce getHtml showHtml showBanner showForm ping getAbTest");b.exponea.notifications=a("notifications.","isAvailable isSubscribed subscribe unsubscribe");var a=c.createElement("script"),g="https:"===c.location.protocol?"https:":"http:";a.type="text/javascript";a.async=!0;a.src=g+"//api-cis.exponea.com/js/exponea.min.js";c.getElementsByTagName("head")[0].appendChild(a);b.webxpClient=b.webxpClient||{};b.webxpClient.sdk=b.exponea;b.webxpClient.sdkObjectName="exponea"}})(window,document);</script>
<script type="text/javascript">
    exponea.initialize({
        "target": "//api-cis.exponea.com",
        "token": "a1c7f982-1686-11ea-8645-02473a0220cc",
        "track": {
            "google_analytics": false
        }
    });
</script>
