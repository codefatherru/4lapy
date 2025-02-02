<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Bitrix vars
 *
 * @global CMain                 $APPLICATION
 * @global CUser                 $USER
 * @var array                    $arParams
 * @var array                    $arResult
 * @var CBitrixComponentTemplate $this
 * @var CBitrixComponent         $component
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Location;

Loc::loadMessages(__FILE__);

if (is_array($arResult['ERRORS']['FATAL']) && !empty($arResult['ERRORS']['FATAL'])):
    foreach ($arResult['ERRORS']['FATAL'] as $error):
        ShowError($error);
    endforeach;
else:
    CJSCore::Init();
    $this->addExternalJs('/bitrix/js/sale/core_ui_widget.js');
    $this->addExternalJs('/bitrix/js/sale/core_ui_etc.js');
    $this->addExternalJs('/bitrix/js/sale/core_ui_autocomplete.js');
    $this->addExternalJs('/bitrix/js/sale/core_ui_itemtree.js');
    
    // to be able to launch this outside the admin section
    $this->addExternalCss('/bitrix/panel/main/adminstyles_fixed.css');
    $this->addExternalCss('/bitrix/panel/main/admin.css');
    $this->addExternalCss('/bitrix/panel/main/admin-public.css');
    ?>
    
    <div id="slss-<?= (int)$arResult['RANDOM_TAG'] ?>">
        
        <?php if (!empty($arResult['ERRORS']['NONFATAL'] && is_array($arResult['ERRORS']['NONFATAL']))) {
            foreach ($arResult['ERRORS']['NONFATAL'] as $error):
                ShowError($error);
            endforeach;
        } ?>
        
        <div class="adm-location-popup-wrap" id="adm-location" style="height: 600px; min-width: 800px">
            <div class="adm-loc-left-wrap">
                <div class="adm-loc-left">
                    <div class="adm-loc-title"><?= Loc::getMessage('SALE_SLSS_LOCATION_AVAILABLE') ?></div>
                    <div class="adm-loc-filter">
                        <div class="adm-loc-filter-title"><?= Loc::getMessage('SALE_SLSS_FIND_LOCATION') ?></div>
                        
                        <table cellpadding="0" cellspacing="0" class="adm-loc-filter-controls">
                            
                            <tr>
                                <td class="adm-loc-filter-inp-cell">
                                    <div style="padding-right: 12px">
                                        <input type="text" class="adm-loc-filter-inp bx-ui-slss-input" title="" />
                                    </div>
                                </td>
                                
                                <td class="adm-loc-filter-select-cell" style="padding: 0 5px; width: 1%">
                                    <select class="adm-loc-filter-select bx-ui-slss-type" title="">
                                        <option value="">
                                            -- <?= Loc::getMessage('SALE_SLSS_TYPE_NOT_SELECTED') ?></option>
                                        <?php if (is_array($arResult['TYPES']) && !empty($arResult['TYPES'])) {
                                            foreach ($arResult['TYPES'] as $id => $type): ?>
                                                <option value="<?= $id ?>"><?= htmlspecialcharsbx(
                                                        $type['NAME']
                                                    ) ?></option>
                                            <?php endforeach;
                                        } ?>
                                    </select>
                                </td>
                                
                                <td class="adm-loc-filter-select-cell" style="padding-left: 5px; width: 1%">
                                    <span class="adm-loc-clear-search bx-ui-slss-clear"><nobr><?= Loc::getMessage(
                                                'SALE_SLSS_RESET_FILTER'
                                            ) ?></nobr></span>
                                </td>
                            
                            </tr>
                        
                        </table>
                    
                    </div>
                    
                    <div class="adm-loc-cont-wrap">
                        <div class="adm-loc-menu-block-wrap" style="width: 39%;">
                            <table class="adm-submenu-items-stretch">
                                <tr>
                                    <td class="adm-submenu-items-stretch-cell adm-submenu-groups bx-ui-slss-selector-groups">
                                        
                                        <?php if (is_array($arResult['GROUPS']) && !empty($arResult['GROUPS'])):
                                            foreach ($arResult['GROUPS'] as $group): ?>
                                                
                                                <div class="adm-submenu-items-block">
                                                    <div class="adm-sub-submenu-block adm-sub-submenu-open">
                                                        
                                                        <div class="adm-submenu-item-name">
															<span class="adm-submenu-item-arrow">

																<?php $r = random_int(99, 999); ?>
                                                                <input type="checkbox"
                                                                       class="adm-designed-checkbox"
                                                                       value="<?= (int)$group['ID'] ?>"
                                                                       id="designed_checkbox_<?= $r ?>">
																<label class="adm-designed-checkbox-label"
                                                                       for="designed_checkbox_<?= $r ?>"></label>

															</span>
                                                            <a href="javascript:void(0)"
                                                               class="adm-submenu-item-name-link"
                                                               data-item-id="<?= (int)$group['ID'] ?>">
                                                                <span class="adm-submenu-item-name-link-text"><?= htmlspecialcharsbx(
                                                                        $group['NAME']
                                                                    ) ?></span>
                                                            </a>
                                                        </div>
                                                    
                                                    </div>
                                                </div>
                                            <?php endforeach;
                                        endif ?>
                                    
                                    </td>
                                </tr>
                            </table>
                            
                            <?php if (is_array($arResult['GROUPS']) && !empty($arResult['GROUPS'])): ?>
                                <div class="adm-loc-menu-separate"></div>
                            <?php //todo: make it draggable
                            endif ?>
                            
                            <table class="adm-submenu-items-stretch">
                                <tr>
                                    <td class="adm-submenu-items-stretch-cell adm-submenu-locations">
                                        <div class="adm-submenu-items-block adm-submenu-items-block-tree bx-ui-slss-selector-locations-tree">
                                            
                                            <?php if (!empty($arResult['LOCATIONS'])): ?>
                                                
                                                <div class="adm-loc-i-tree-node bx-ui-item-tree-slss-node"
                                                     data-node-id="0"
                                                     data-is-parent="1"
                                                     style="margin-left: 0">
                                                    
                                                    <div class="adm-loc-i-selector-arrow bx-ui-item-tree-slss-expander"></div>
                                                    <div class="adm-loc-i-tree-label bx-ui-slss-selector-show-bundle"
                                                         data-node-id="0">
                                                        <?= Loc::getMessage('SALE_SLSS_LOCATIONS') ?>
                                                    </div>
                                                    
                                                    <div class="adm-loc-i-tree-panel bx-ui-item-tree-slss-children">
                                                        
                                                        <?php if (is_array($arResult['LOCATIONS'])
                                                                  && !empty($arResult['LOCATIONS'])) {
                                                            foreach ($arResult['LOCATIONS'] as $location): ?>
                                                                
                                                                <div class="adm-loc-i-tree-node bx-ui-item-tree-slss-node"
                                                                     data-node-id="<?= (int)$location['ID'] ?>"
                                                                     data-is-parent="<?= ($location['IS_PARENT'] ? '1' : '0') ?>">
                                                                    <div class="adm-loc-i-selector-arrow<?= ($location['IS_PARENT'] ? ' bx-ui-item-tree-slss-expander' : '') ?>"></div>
                                                                    <div class="adm-loc-i-tree-label<?= ($location['IS_PARENT'] ? ' bx-ui-slss-selector-show-bundle' : '') ?>"
                                                                         data-node-id="<?= (int)$location['ID'] ?>">
                                                                        <?= htmlspecialcharsbx($location['NAME']) ?>
                                                                    </div>
                                                                    <div class="adm-loc-i-tree-panel bx-ui-item-tree-slss-children">
                                                                        <div class="bx-ui-item-tree-slss-item-pool">
                                                                        </div>
                                                                        <div class="adm-loc-i-tree-loading"><?= Loc::getMessage(
                                                                                'SALE_SLSS_AJAX_LOADING'
                                                                            ) ?></div>
                                                                        <a class="adm-loc-i-tree-load-more bx-ui-item-tree-slss-load-more"
                                                                           href="javascript:void(0)"><?= Loc::getMessage(
                                                                                'SALE_SLSS_AJAX_LOAD_MORE'
                                                                            ) ?></a>
                                                                        <div class="adm-loc-i-tree-error">
                                                                            <?= Loc::getMessage(
                                                                                'SALE_SLSS_LOCATION_SELECTOR_ERROR_OCCURED'
                                                                            ) ?>
                                                                            :
                                                                            <span class="bx-ui-item-tree-slss-error-desc"></span><br />
                                                                            <a class="bx-ui-item-tree-slss-load-more"
                                                                               href="javascript:void(0)"><?= Loc::getMessage(
                                                                                    'SALE_SLSS_AJAX_RETRY'
                                                                                ) ?></a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            
                                                            <?php endforeach;
                                                        } ?>
                                                    
                                                    </div>
                                                </div>
                                            
                                            <?php else: ?>
                                                <div class="adm-loc-error">
                                                    <?= Loc::getMessage(
                                                        'SALE_SLSS_NO_LOCATIONS',
                                                        [
                                                            '#ANCHOR_IMPORT#' => '<a href="'
                                                                                 . ((string)$arParams['PATH_TO_LOCATION_IMPORT']
                                                                                    !== '' ? $arParams['PATH_TO_LOCATION_IMPORT'] : Location\Admin\Helper::getImportUrl(
                                                                )) . '" target="_blank">',
                                                            '#ANCHOR_END#'    => '</a>',
                                                        ]
                                                    ) ?>
                                                </div>
                                            <?php endif ?>
                                            
                                            <script type="text/html"
                                                    data-template-id="bx-ui-item-tree-slss-node"
                                                    data-skip-moving="true">
                                                
                                                <div class="adm-loc-i-tree-node bx-ui-item-tree-slss-node"
                                                     data-node-id="{{id}}"
                                                     data-is-parent="{{is_parent}}">
                                                    <div class="adm-loc-i-selector-arrow {{expander_class}}"></div>
                                                    <div class="adm-loc-i-tree-label {{select_class}}"
                                                         data-node-id="{{id}}">
                                                        {{name}}
                                                    </div>
                                                    <div class="adm-loc-i-tree-panel bx-ui-item-tree-slss-children">
                                                        <div class="bx-ui-item-tree-slss-item-pool">
                                                        </div>
                                                        <div class="adm-loc-i-tree-loading"><?= Loc::getMessage(
                                                                'SALE_SLSS_AJAX_LOADING'
                                                            ) ?></div>
                                                        <a class="adm-loc-i-tree-load-more bx-ui-item-tree-slss-load-more"
                                                           href="javascript:void(0)"><?= Loc::getMessage(
                                                                'SALE_SLSS_AJAX_LOAD_MORE'
                                                            ) ?></a>
                                                        <div class="adm-loc-i-tree-error">
                                                            <?= Loc::getMessage(
                                                                'SALE_SLSS_LOCATION_SELECTOR_ERROR_OCCURED'
                                                            ) ?>
                                                            :
                                                            <span class="bx-ui-item-tree-slss-error-desc"></span><br />
                                                            <a class="bx-ui-item-tree-slss-load-more"
                                                               href="javascript:void(0)"><?= Loc::getMessage(
                                                                    'SALE_SLSS_AJAX_RETRY'
                                                                ) ?></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </script>
                                        
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="adm-loc-table-block-wrap bx-ui-slss-pane" style="width: 59%;">
                            <table class="adm-list-table">
                                <thead>
                                <tr class="adm-list-table-header">
                                    <td class="adm-list-table-cell adm-list-table-checkbox">
                                        <?php $r = random_int(99, 999) . random_int(99, 999) . random_int(99, 999); ?>
                                        <input type="checkbox"
                                               class="adm-designed-checkbox bx-ui-slss-choose-all"
                                               id="designed_checkbox_<?= $r ?>">
                                        <label class="adm-designed-checkbox-label"
                                               for="designed_checkbox_<?= $r ?>"></label>
                                    </td>
                                    <td class="adm-list-table-cell">
                                        <div class="adm-list-table-cell-inner adm-list-table-name"><?= Loc::getMessage(
                                                'SALE_SLSS_NAME_COLUMN'
                                            ) ?></div>
                                    </td>
                                </tr>
                                </thead>
                                <tbody class="slss-current-locations bx-ui-slss-variants bx-ui-slss-selector-locations">
                                <script type="text/html"
                                        data-template-id="bx-ui-slss-dropdown-item"
                                        data-skip-moving="true">
                                    <tr class="adm-list-table-row">
                                        <td class="adm-list-table-cell adm-list-table-checkbox">
                                            <input type="checkbox"
                                                   class="adm-designed-checkbox"
                                                   value="{{value}}"
                                                   id="designed_checkbox_{{random_value}}">
                                            <label class="adm-designed-checkbox-label"
                                                   for="designed_checkbox_{{random_value}}"></label>
                                        </td>
                                        <td class="adm-list-table-cell">
                                            <span class="adm-list-table-link">{{display}}&nbsp;<span class="adm-list-table-loc-type">({{type}})</span>&nbsp;&nbsp;<a
                                                        href="/bitrix/admin/sale_location_node_edit.php?lang=<?= LANGUAGE_ID ?>&id={{value}}"
                                                        target="_blank"
                                                        class="adm-list-table-loc-id">id: {{value}}</a></span>
                                            <span class="adm-list-table-loc-path">{{path}}</span>
                                        </td>
                                    </tr>
                                </script>
                                </tbody>
                            </table>
                            
                            <div class="adm-loc-nothing-found bx-ui-slss-nothing-found">
                                <?= Loc::getMessage('SALE_SLSS_LOCATION_SELECTOR_NOTHING_FOUND') ?>
                            </div>
                            
                            <div class="adm-loc-select-prompt bx-ui-slss-select-prompt">
                                <?= Loc::getMessage('SALE_SLSS_LOCATION_SELECTOR_SELECT_PROMPT') ?>
                            </div>
                            
                            <script type="text/html" data-template-id="bx-ui-slss-error" data-skip-moving="true">
                                <tr>
                                    <td colspan="2" class="adm-loc-error">
                                        {{message}}
                                    </td>
                                </tr>
                            </script>
                        
                        </div>
                    </div>
                </div>
            </div>
            <div class="adm-loc-middle-wrap">
                <div class="adm-loc-middle">
                    <span class="adm-loc-carry-btn adm-loc-carry-r bx-ui-slss-select"
                          title="<?= Loc::getMessage('SALE_SLSS_LOCATION_SELECTOR_CHOOSE') ?>"></span>
                    <span class="adm-loc-carry-btn adm-loc-carry-l bx-ui-slss-deselect"
                          title="<?= Loc::getMessage('SALE_SLSS_LOCATION_SELECTOR_UNCHOOSE') ?>"></span>
                </div>
            </div>
            <div class="adm-loc-right-wrap">
                <div class="adm-loc-right">
                    <div class="adm-loc-title"><?= Loc::getMessage('SALE_SLSS_SELECTED_LOCATIONS') ?></div>
                    <div class="adm-loc-filter">
                        <?= Loc::getMessage('SALE_SLSS_LOCATION_SELECTOR_LOC_TOTAL_SELECTED') ?>:
                        <span class="bx-ui-slss-selected-node-counter">0</span><br />
                        <?php if ($arResult['USE_GROUPS']): ?>
                            <?= Loc::getMessage('SALE_SLSS_LOCATION_SELECTOR_GRP_TOTAL_SELECTED') ?>:
                            <span class="bx-ui-slss-selected-group-counter">0</span><br />
                        <?php endif ?>
                        <div class="adm-loc-selected-actions">
                            <a href="javascript:void(0)"
                               class="bx-ui-slss-selected-act-clean"><?= Loc::getMessage(
                                    'SALE_SLSS_LOCATION_SELECTOR_CLEAN_SELECTION'
                                ) ?></a>
                        </div>
                    </div>
                    <div class="adm-loc-cont-wrap">
                        <div class="adm-loc-table-block-wrap bx-ui-slss-selected-pane">
                            <table class="adm-list-table">
                                <thead>
                                <tr class="adm-list-table-header">
                                    <td class="adm-list-table-cell adm-list-table-checkbox">
                                        <?php $r = random_int(99, 999) . random_int(99, 999) . random_int(99, 999); ?>
                                        <input type="checkbox"
                                               class="adm-designed-checkbox bx-ui-slss-choose-all-selected"
                                               id="designed_checkbox_<?= $r ?>">
                                        <label class="adm-designed-checkbox-label"
                                               for="designed_checkbox_<?= $r ?>"></label>
                                    </td>
                                    <td class="adm-list-table-cell">
                                        <div class="adm-list-table-cell-inner adm-list-table-name"><?= Loc::getMessage(
                                                'SALE_SLSS_NAME_COLUMN'
                                            ) ?></div>
                                    </td>
                                </tr>
                                </thead>
                                <tbody class="bx-ui-slss-selected-groups">
                                <script type="text/html"
                                        data-template-id="bx-ui-slss-selected-group"
                                        data-skip-moving="true">
                                    <tr class="adm-list-table-row">
                                        <td class="adm-list-table-cell adm-list-table-checkbox">
                                            <input type="checkbox"
                                                   class="adm-designed-checkbox"
                                                   value="{{id}}"
                                                   id="designed_checkbox_{{random_value}}">
                                            <label class="adm-designed-checkbox-label"
                                                   for="designed_checkbox_{{random_value}}"></label>
                                        </td>
                                        <td class="adm-list-table-cell">
                                            <span class="adm-list-table-link">{{name}}&nbsp;&nbsp;<a href="/bitrix/admin/sale_location_node_edit.php?lang=<?= LANGUAGE_ID ?>&id={{id}}"
                                                                                                     target="_blank"
                                                                                                     class="adm-list-table-loc-id">id: {{id}}</a></span>
                                        </td>
                                    </tr>
                                </script>
                                </tbody>
                            </table>
                            
                            <div class="adm-loc-menu-separate bx-ui-slss-selected-separator"></div>
                            
                            <table class="adm-list-table">
                                <tbody class="bx-ui-slss-selected-locations">
                                <script type="text/html"
                                        data-template-id="bx-ui-slss-selected-node"
                                        data-skip-moving="true">
                                    <tr class="adm-list-table-row">
                                        <td class="adm-list-table-cell adm-list-table-checkbox">
                                            <input type="checkbox"
                                                   class="adm-designed-checkbox"
                                                   value="{{value}}"
                                                   id="designed_checkbox_{{random_value}}">
                                            <label class="adm-designed-checkbox-label"
                                                   for="designed_checkbox_{{random_value}}"></label>
                                        </td>
                                        <td class="adm-list-table-cell">
                                            <span class="adm-list-table-link">{{display}}&nbsp;<span class="adm-list-table-loc-type">({{type}})</span>&nbsp;&nbsp;<a
                                                        href="/bitrix/admin/sale_location_node_edit.php?lang=<?= LANGUAGE_ID ?>&id={{value}}"
                                                        target="_blank"
                                                        class="adm-list-table-loc-id">id: {{value}}</a></span>
                                            <span class="adm-list-table-loc-path">{{path}}</span>
                                        </td>
                                    </tr>
                                </script>
                                </tbody>
                            </table>
                            <div class="adm-loc-select-prompt bx-ui-slss-nothing-selected">
                                <?= Loc::getMessage('SALE_SLSS_LOCATION_SELECTOR_NOTHING_SELECTED') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bx-ui-slss-input-pool">
            <script type="text/html" data-template-id="bx-ui-slss-location-input" data-skip-moving="true">
                <input type="hidden" name="<?= (string)$arParams['INPUT_NAME'] ?>[L]" value="{{ids}}" />
            </script>
            <script type="text/html" data-template-id="bx-ui-slss-group-input" data-skip-moving="true">
                <input type="hidden" name="<?= $arParams['INPUT_NAME'] ?>[G]" value="{{ids}}" />
            </script>
        </div>
    </div>
    <?php
    // todo: i dont like it, refactor later (may be with strong assistance of parse_url() and $_SERVER['REQUEST_URI'])
    $urlComponents = [];
    if ('' !== $arParams['ENTITY_PRIMARY']) {
        $urlComponents[] = $arParams['ENTITY_VARIABLE_NAME'] . '=' . $arParams['ENTITY_PRIMARY'];
    }
    $urlComponents[] = $arParams['EDIT_MODE_SWITCH'] . '=1';
    $component       = $this->__component;
    
    ?>
    <script data-skip-moving="true">
        
        if (!window.BX && top.BX) {
            window.BX = top.BX;
        }
        
        //BX.ready(function(){
        <?if(!empty($arParams['JS_CONTROL_DEFERRED_INIT'])):?>
        if (typeof window.BX.locationsDeferred === 'undefined') {
            window.BX.locationsDeferred = {};
        }
        window.BX.locationsDeferred['<?=$arParams['JS_CONTROL_DEFERRED_INIT']?>'] = function () {
            <?endif?>
            
            <?if(!empty($arParams['JS_CONTROL_GLOBAL_ID'])):?>
            if (typeof window.BX.locationSelectors === 'undefined') {
                window.BX.locationSelectors = {};
            }
            window.BX.locationSelectors['<?=$arParams['JS_CONTROL_GLOBAL_ID']?>'] =
            <?endif?>
            <?/** @noinspection PhpUndefinedFieldInspection */
                $jsParams = [
                    
                    'scope'  => 'slss-' . (int)$arResult['RANDOM_TAG'],
                    //'source' => $component->getPath() . '/get.php',
                    'source' => '/bitrix/components/bitrix/sale.location.selector.system/get.php',
                    //'source' => '/local/components/bitrix/sale.location.selector.system/get.php',
                    'query'  => [
                        'BEHAVIOUR' => [
                            'LANGUAGE_ID' => LANGUAGE_ID,
                        ],
                    ],
                    
                    'editUrl'        => '?' . implode(
                            '&',
                            $urlComponents
                        ),
                    'parentTagId'    => (int)$arResult['RANDOM_TAG'],
                    'useCodes'       => $arResult['USE_CODES'],
                    'types'          => $arResult['TYPES'],
                    'startSearchLen' => $component::START_SEARCH_LEN,
                    // this, ...
                    'pageSize'       => $component::PAGE_SIZE,
                    // this ...
                    'hugeTailLen'    => $component::HUGE_TAIL_LEN,
                    // and this are being used also in class.php, so could be in parameters, but only since parameters storage implemented
                    
                    'connected' => $arResult['EDIT_MODE'] ? [] : [
                        'data' => [
                            'l' => $arResult['FOR_JS']['DATA']['LOCATION'],
                            'g' => $arResult['GROUPS'],
                            // we want all groups here, not only connected
                            'p' => $arResult['FOR_JS']['DATA']['PATH_NAMES'],
                        ],
                        'id'   => [
                            'l' => $arResult['FOR_JS']['CONNECTED']['LOCATION'],
                            'g' => $arResult['FOR_JS']['CONNECTED']['GROUP'],
                        ],
                    ],
                    
                    'messages'      => [
                        'title'             => Loc::getMessage('SALE_SLSS_LOCATION_SELECTOR_TITLE'),
                        'btnSave'           => Loc::getMessage('SALE_SLSS_LOCATION_SELECTOR_BUTTON_SAVE'),
                        'btnCancel'         => Loc::getMessage('SALE_SLSS_LOCATION_SELECTOR_BUTTON_CANCEL'),
                        'error'             => Loc::getMessage('SALE_SLSS_LOCATION_SELECTOR_ERROR_OCCURED'),
                        'sureCleanSelected' => Loc::getMessage('SALE_SLSS_LOCATION_SELECTOR_SURE_CLEAN'),
                        'andNLoc2Go'        => Loc::getMessage('SALE_SLSS_AND_N_LOCATIONS_TO_GO'),
                        'plural'            => [
                            'element'   => Loc::getMessage('SALE_SLSS_LOCATION_ELEMENT'),
                            'elementa'  => Loc::getMessage('SALE_SLSS_LOCATION_ELEMENTA'),
                            'elementov' => Loc::getMessage('SALE_SLSS_LOCATION_ELEMENTOV'),
                        ],
                        'chooseDo'          => Loc::getMessage('SALE_SLSS_CHOICE_DO'),
                        'chooseReDo'        => Loc::getMessage('SALE_SLSS_CHOICE_REDO'),
                    ],
                    'prop_location' => $arParams['PROP_LOCATION'],
                
                ];
                ?>
                new BX.Sale.component.location.selector.system(<?=CUtil::PhpToJSObject(
                    $jsParams,
                    false,
                    false,
                    true
                )?>);
            <?if(!empty($arParams['JS_CONTROL_DEFERRED_INIT'])):?>
        };
        <?endif?>
        //});
    </script>
<?php endif;