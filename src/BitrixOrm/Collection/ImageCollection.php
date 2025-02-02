<?php

namespace FourPaws\BitrixOrm\Collection;

use Adv\Bitrixtools\Collection\ObjectArrayCollection;
use Bitrix\Main\FileTable;
use FourPaws\App\Templates\MediaEnum;
use FourPaws\BitrixOrm\Model\Image;
use InvalidArgumentException;

class ImageCollection extends ObjectArrayCollection
{
    /**
     * @param array $ids
     * @return ImageCollection
     */
    public static function createFromIds(array $ids = [])
    {
        $collection = new static();
        if ($ids) {
            $result = FileTable::query()->addFilter('ID', $ids)->addSelect('*')->exec();
            while ($item = $result->fetch()) {
                $collection->add(new Image($item));
            }
        }
    
        return $collection;
    }
    
    /**
     * @param mixed $object
     *
     * @throws InvalidArgumentException
     * @return void
     */
    protected function checkType($object)
    {
        if (!($object instanceof Image)) {
            throw new InvalidArgumentException('Переданный объект не является картинкой');
        }
    }
    
    /**
     * Dirty hack
     *
     * @throws \InvalidArgumentException
     */
    public static function createNoImageCollection()
    {
        $collection = new static();
        
        $collection->add(new Image([
                                       'src'    => MediaEnum::NO_IMAGE_WEB_PATH,
                                       'width'  => 500,
                                       'height' => 362,
                                   ]));
        
        return $collection;
    }
}
