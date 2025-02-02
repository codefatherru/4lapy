<?php

namespace FourPaws\BitrixOrm\Model\Interfaces;

/**
 * Interface FileInterface
 *
 * @package FourPaws\BitrixOrm\Model
 */
interface FileInterface extends ActiveReadModelInterface
{
    /**
     * @return int
     */
    public function getId() : int;
    
    /**
     * @param int $id
     */
    public function setId(int $id);
    
    /**
     * @return string
     */
    public function getSrc() : string;
    
    /**
     * @param string $src
     */
    public function setSrc(string $src);
    
    /**
     * @return string
     */
    public function __toString() : string;
}
