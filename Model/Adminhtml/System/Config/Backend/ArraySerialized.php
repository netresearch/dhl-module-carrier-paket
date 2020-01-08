<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Adminhtml\System\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Data\ProcessorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Save and load config data array in JSON format.
 *
 * - The core backend model throws an "array to string" conversion error.
 * - The core backend model does not unserialize (process) when loading.
 *
 * @author  Benjamin Heuer <benjamin.heuer@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ArraySerialized extends Value implements ProcessorInterface
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * ArraySerialized constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Json $serializer
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param mixed[] $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Json $serializer,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Unserialize value.
     *
     * @return void
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        if (!\is_array($value)) {
            $this->setValue(empty($value) ? false : $this->processValue($value));
        }
    }

    /**
     * Unset array element with '__empty' key
     *
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (\is_array($value)) {
            unset($value['__empty']);
        }

        if (!empty($value)) {
            $value = $this->serializer->serialize($value);
        } else {
            $value = $this->getOldValue();
        }

        $this->setValue($value);

        parent::beforeSave();

        return $this;
    }

    /**
     * Process config value
     *
     * @param string $value
     *
     * @return string
     */
    public function processValue($value)
    {
        if ($value) {
            return $this->serializer->unserialize($value);
        }

        return '';
    }

    /**
     * Get old value from config, encode for comparison with new value.
     *
     * @see \Magento\Framework\App\Config\Value::afterSave
     * @see \Magento\Framework\App\Config\Value::isValueChanged
     *
     * @return string
     */
    public function getOldValue()
    {
        $oldValue = $this->_config->getValue(
            $this->getPath(),
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getData('scope_code')
        );

        if (\is_array($oldValue)) {
            $oldValue = $this->serializer->serialize($oldValue);
        }

        return $oldValue;
    }
}
