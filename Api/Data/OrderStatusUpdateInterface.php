<?php
/**
 * Order Status Update Data Interface
 *
 * @category  Vendor
 * @package   Vendor_CustomOrderProcessing
 */
declare(strict_types=1);

namespace Vendor\CustomOrderProcessing\Api\Data;

/**
 * Interface for order status log data
 * @api
 */
interface OrderStatusUpdateInterface
{
    /**
     * Constants for keys of data array
     */
    const ENTITY_ID = 'entity_id';
    const ORDER_ID = 'order_id';
    const INCREMENT_ID = 'increment_id';
    const OLD_STATUS = 'old_status';
    const NEW_STATUS = 'new_status';
    const CREATED_AT = 'created_at';

    /**
     * Get Entity ID
     *
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * Set Entity ID
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId(int $entityId);

    /**
     * Get Order ID
     *
     * @return int
     */
    public function getOrderId(): int;

    /**
     * Set Order ID
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId): self;

    /**
     * Get Increment ID
     *
     * @return string
     */
    public function getIncrementId(): string;

    /**
     * Set Increment ID
     *
     * @param string $incrementId
     * @return $this
     */
    public function setIncrementId(string $incrementId): self;

    /**
     * Get Old Status
     *
     * @return string
     */
    public function getOldStatus(): string;

    /**
     * Set Old Status
     *
     * @param string $status
     * @return $this
     */
    public function setOldStatus(string $status): self;

    /**
     * Get New Status
     *
     * @return string
     */
    public function getNewStatus(): string;

    /**
     * Set New Status
     *
     * @param string $status
     * @return $this
     */
    public function setNewStatus(string $status): self;

    /**
     * Get Created At
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;
}