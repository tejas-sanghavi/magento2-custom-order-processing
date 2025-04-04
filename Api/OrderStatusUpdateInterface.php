<?php
/**
 * Order Status Update API Interface
 *
 * @category  Vendor
 * @package   Vendor_CustomOrderProcessing
 */
declare(strict_types=1);

namespace Vendor\CustomOrderProcessing\Api;

/**
 * Interface for order status update API
 * @api
 */
interface OrderStatusUpdateInterface
{
    /**
     * Update order status via API
     *
     * @param string $incrementId Order increment ID
     * @param string $status New order status
     * @param string|null $comment Comment for status update
     * @return bool
     */
    public function updateOrderStatus(
        string $incrementId,
        string $status,
        ?string $comment = null
    ): bool;
}