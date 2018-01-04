<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\App;

/**
 * Class MainTemplate
 *
 * Класс для основного шаблона
 *
 * @package FourPaws\App
 */
class MainTemplate extends TemplateAbstract
{
    /**
     * @return bool
     */
    public function isIndex() : bool
    {
        return $this->isPage('/');
    }
    
    public function getIndexMainClass()
    {
        return $this->isIndex() ? ' b-wrapper--main':'';
    }
    
    /**
     * Страница 404
     *
     * @return bool
     */
    public function is404() : bool
    {
        return \defined('ERROR_404') && ERROR_404 === 'Y';
    }
    
    /**
     * @return bool
     */
    public function hasHeaderPublicationListContainer() : bool
    {
        return $this->isListNews() || $this->isListArticles() || $this->isListShares() || $this->isListSharesFilter();
    }
    
    /**
     * @return bool
     */
    public function isListNews() : bool
    {
        return $this->isDir('/company/news');
    }
    
    /**
     * @return bool
     */
    public function isListArticles() : bool
    {
        return $this->isDir('/services/articles');
    }
    
    /**
     * @return bool
     */
    public function isListShares() : bool
    {
        return $this->isDir('/customer/shares');
    }

    /**
     * @return bool
     */
    public function isListSharesFilter() : bool
    {
        return $this->isPartitionDir('/customer/shares/by_pet');
    }
    
    /**
     * @return bool
     */
    public function hasHeaderDetailPageContainer() : bool
    {
        return $this->isDetailNews() || $this->isDetailArticles() || $this->isDetailShares();
    }
    
    /**
     * @return bool
     */
    public function isDetailNews() : bool
    {
        return $this->isPartitionDir('/company/news');
    }
    
    /**
     * @return bool
     */
    public function isDetailArticles() : bool
    {
        return $this->isPartitionDir('/services/articles');
    }
    
    /**
     * @return bool
     */
    public function isDetailShares() : bool
    {
        return !$this->isListShares() && $this->isPartitionDir('/customer/shares');
    }

    /**
     * @return bool
     */
    public function hasHeaderPersonalContainer() : bool
    {
        return ($this->isPersonalDirectory() || $this->isPersonal()) && !$this->isRegister()
               && !$this->isForgotPassword();
    }
    
    /**
     * @return bool
     */
    public function isPersonalDirectory() : bool
    {
        return $this->isPartitionDir('/personal');
    }
    
    /**
     * @return bool
     */
    public function isPersonal() : bool
    {
        return $this->isDir('/personal');
    }
    
    /**
     * @return bool
     */
    public function isRegister() : bool
    {
        return $this->isDir('/personal/register');
    }
    
    /**
     * @return bool
     */
    public function isForgotPassword() : bool
    {
        return $this->isDir('/personal/forgot-password');
    }
    
    /**
     * @return bool
     */
    public function hasHeaderBlockShopList() : bool
    {
        return $this->isShopList();
    }
    
    /**
     * @return bool
     */
    public function isShopList() : bool
    {
        return $this->isDir('/company/shops');
    }
}
