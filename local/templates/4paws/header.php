<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
} ?><!doctype html>
<html>
<head>
    <?php $APPLICATION->ShowHead(); ?>
    <title><?php $APPLICATION->ShowTitle() ?></title>
</head>
<body>
<?php $APPLICATION->ShowPanel() ?>
<?php $APPLICATION->IncludeComponent('fourpaws:auth.form', '', [], false, ['HIDE_ICONS' => 'Y']); ?>
