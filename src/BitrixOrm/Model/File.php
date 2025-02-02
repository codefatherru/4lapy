<?php

namespace FourPaws\BitrixOrm\Model;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\FileTable;
use FourPaws\BitrixOrm\Model\Exceptions\FileNotFoundException;
use FourPaws\BitrixOrm\Model\Interfaces\FileInterface;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class File
 *
 * @package FourPaws\BitrixOrm\Model
 */
class File implements FileInterface
{
    /**
     * @Serializer\Type("array")
     * @Serializer\Groups({"elastic"})
     * @var array
     */
    protected $fields;

    /**
     * @Serializer\Type("string")
     * @Serializer\Groups({"elastic"})
     * @var string
     */
    protected $src;

    /**
     * File constructor.
     *
     * @param array $fields
     */
    public function __construct(array $fields = [])
    {
        if ($fields['src']) {
            $this->setSrc($fields['src']);
        }
        
        $this->fields = $fields;
    }

    /**
     * @param string $primary
     *
     * @throws FileNotFoundException
     * @return static
     *
     */
    public static function createFromPrimary(string $primary)
    {
        $fields = FileTable::getById($primary)->fetch();

        if (!$fields) {
            throw new FileNotFoundException(sprintf('File with id %s is not found', $primary));
        }

        return new static($fields);
    }

    /**
     * @return string
     */
    public function getSrc(): string
    {
        if ($this->src === null) {
            try {
                $src = sprintf(
                    '/%s/%s/%s',
                    Option::get('main', 'upload_dir', 'upload'),
                    $this->getSubDir(),
                    $this->getFileName()
                );
                $this->setSrc($src);
            } catch (ArgumentNullException $e) {
            } catch (ArgumentOutOfRangeException $e) {
            }
        }

        return $this->src;
    }

    /**
     * @param string $src
     *
     * @return static
     */
    public function setSrc(string $src): self
    {
        $this->src = $src;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubDir(): string
    {
        return (string)$this->fields['SUBDIR'];
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return (string)$this->fields['FILE_NAME'];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return (int)$this->fields['ID'];
    }

    /**
     * @param int $id
     *
     * @return static
     */
    public function setId(int $id): self
    {
        $this->fields['ID'] = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getSrc();
    }

    /**
     * @todo move to interface
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
