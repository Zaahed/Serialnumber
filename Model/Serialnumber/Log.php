<?php

namespace Zaahed\Serialnumber\Model\Serialnumber;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Serialize\Serializer\Json;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber as ResourceModel;

class Log extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'serialnumber_log_model';

    /**
     * @var Json
     */
    private $jsonHelper;

    /**
     * @param Json $jsonHelper
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Json $jsonHelper,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection,
            $data);
    }

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Get serial number ID.
     *
     * @return int|null
     */
    public function getSerialnumberId(): ?int
    {
        return $this->getData('serialnumber_id');
    }

    /**
     * Set serial number ID.
     *
     * @param int $serialnumber
     * @return $this
     */
    public function setSerialnumberId(int $serialnumber): self
    {
        $this->setData('serialnumber_id', $serialnumber);
        return $this;
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        $message = (string)$this->getData('message');
        $values = $this->getMessageValues();

        return __($message, $values);

    }

    /**
     * Get message string.
     *
     * @return string
     */
    public function getMessageString(): string
    {
        return (string)$this->getData('message');
    }

    /**
     * Set message.
     *
     * @param string $message
     * @param array|null $values
     * @return $this
     */
    public function setMessage(string $message, array $values = null): self
    {
        $this->setData('message', $message);

        if ($values !== null) {
            $this->setData('message_values', $this->jsonHelper->serialize($values));
        }

        return $this;
    }

    /**
     * Get message values.
     *
     * @return array
     */
    public function getMessageValues(): array
    {
        $messageValues = $this->getData('message_values');
        return $messageValues ?
            $this->jsonHelper-> unserialize($messageValues) : [];
    }

    /**
     * Get message values as JSON.
     *
     * @return string|null
     */
    public function getMessageValuesJson(): ?string
    {
        return $this->getData('message_values');
    }

    /**
     * Get user ID.
     *
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->getData('user_id');
    }

    /**
     * Set user ID.
     *
     * @param int|null $userId
     * @return $this
     */
    public function setUserId(?int $userId)
    {
        if ($userId === 0) {
            $userId = null;
        }

        $this->setData('user_id', $userId);

        return $this;
    }

    /**
     * Get the datetime of when the log event was saved into the DB.
     *
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->getData('created_at')
            ? new \DateTime($this->getData('created_at'))
            : null;
    }
}
