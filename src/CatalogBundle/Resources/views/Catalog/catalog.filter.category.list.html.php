<?php

/**
 * @var Request $request
 * @var CatalogCategorySearchRequestInterface $catalogRequest
 * @var ProductSearchResult $productSearchResult
 * @var PhpEngine $view
 * @var Category $category
 * @var CMain $APPLICATION
 */

use FourPaws\Catalog\Model\Category;
use FourPaws\CatalogBundle\Dto\CatalogCategorySearchRequestInterface;
use FourPaws\Decorators\SvgDecorator;
use FourPaws\Search\Model\ProductSearchResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\PhpEngine;

?>
<?php if ($category->getChild()->count()) { ?>
    <div class="b-filter__block b-filter__block--select">
        <h3 class="b-title b-title--filter-header">
            Категория
        </h3>
        <div class="b-select b-select--filter">
            <ul class="b-filter-link-list b-filter-link-list--filter b-filter-link-list--select-filter js-accordion-filter-select js-filter-checkbox">
                <?php /** @var Category $child */ ?>
                <?php foreach ($category->getChild() as $child) { ?>
                    <li class="b-filter-link-list__item">
                        <a class="b-filter-link-list__link"
                           href="<?= $child->getSectionPageUrl() ?>"
                           title="<?= $child->getDisplayName() ?: $child->getName() ?>">
                            <?= $child->getDisplayName() ?: $child->getName() ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>
            <a class="b-link b-link--filter-more b-link--filter-select js-open-filter-all"
               href="javascript:void(0);" title="Показать все">
                Показать все
                <span class="b-icon b-icon--more">
                    <?= new SvgDecorator('icon-arrow-down', 10, 10) ?>
                </span>
            </a>
        </div>
    </div>
<?php } ?>
