<?php
/**
 * OrderStatusLog Model
 *
 * @category  Vendor
 * @package   Vendor_CustomOrderProcessing
 */
declare(strict_types=1);

namespace Vendor\CustomOrderProcessing\Model;

use Magento\Framework\Model\AbstractModel;
use Vendor\CustomOrderProcessing\Api\Data\OrderStatusUpdateInterface;
use Vendor\CustomOrderProcessing\Model\ResourceModel\OrderStatusLog as ResourceModel;

/**
 * Order status log model
 */
class OrderStatusLog extends AbstractModel implements OrderStatusUpdateInterface
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Get Entity ID
     *
     * @return int|null
     */
    public function getEntityId(): ?int
    {
        return $this->getData(self::ENTITY_ID) === null ? null 
            : (int)$this->getData(self::ENTITY_ID);
    }

    /**
     * Set Entity ID
     *
     * @param int|string $entityId
     * @return $this
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get Order ID
     *
     * @return int
     */
    public function getOrderId(): int
    {
        return (int)$this->getData(self::ORDER_ID);
    }

    /**
     * Set Order ID
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId): self
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get Increment ID
     *
     * @return string
     */
    public function getIncrementId(): string
    {
        return (string)$this->getData(self::INCREMENT_ID);
    }

    /**
     * Set Increment ID
     *
     * @param string $incrementId
     * @return $this
     */
    public function setIncrementId(string $incrementId): self
    {
        return $this->setData(self::INCREMENT_ID, $incrementId);
    }

    /**
     * Get Old Status
     *
     * @return string
     */
    public function getOldStatus(): string
    {
        return (string)$this->getData(self::OLD_STATUS);
    }

    /**
     * Set Old Status
     *
     * @param string $status
     * @return $this
     */
    public function setOldStatus(string $status): self
    {
        return $this->setData(self::OLD_STATUS, $status);
    }

    /**
     * Get New Status
     *
     * @return string
     */
    public function getNewStatus(): string
    {
        return (string)$this->getData(self::NEW_STATUS);
    }

    /**
     * Set New Status
     *
     * @param string $status
     * @return $this
     */
    public function setNewStatus(string $status): self
    {
        return $this->setData(self::NEW_STATUS, $status);
    }
   
    /**
     * Get Created At
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}