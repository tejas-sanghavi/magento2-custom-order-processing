<?php
/**
 * Order Status Update Model
 *
 * @category  Vendor
 * @package   Vendor_CustomOrderProcessing
 */
declare(strict_types=1);

namespace Vendor\CustomOrderProcessing\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Vendor\CustomOrderProcessing\Api\OrderStatusUpdateInterface;
use Vendor\CustomOrderProcessing\Model\OrderStatusLogFactory;
use Vendor\CustomOrderProcessing\Model\ResourceModel\OrderStatusLog as OrderStatusLogResource;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

/**
 * Order Status Update Model
 */
class OrderStatusUpdate implements OrderStatusUpdateInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderStatusLogFactory
     */
    private $orderStatusLogFactory;

    /**
     * @var OrderStatusLogResource
     */
    private $orderStatusLogResource;
    
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param OrderStatusLogFactory $orderStatusLogFactory
     * @param OrderStatusLogResource $orderStatusLogResource
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        OrderStatusLogFactory $orderStatusLogFactory,
        OrderStatusLogResource $orderStatusLogResource,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->orderStatusLogFactory = $orderStatusLogFactory;
        $this->orderStatusLogResource = $orderStatusLogResource;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * Update order status via API
     *
     * @param string $incrementId Order increment ID
     * @param string $status New order status
     * @param string|null $comment Comment for status update
     * @return bool
     * @throws LocalizedException
     */
    public function updateOrderStatus(
        string $incrementId,
        string $status,
        ?string $comment = null
    ): bool {
        try {
            // Create search criteria to find the order by increment ID
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteriaBuilder->addFilter('increment_id', $incrementId);
            $searchCriteria = $searchCriteriaBuilder->create();
            
            $orderList = $this->orderRepository->getList($searchCriteria)->getItems();
            
            if (empty($orderList)) {
                throw new NoSuchEntityException(__('Order with increment ID "%1" does not exist.', $incrementId));
            }
            
            $order = reset($orderList);
            $oldStatus = $order->getStatus();
            
            // Validate status transition
            $this->validateStatusTransition($order, $status);
            
            // Update order status - use State and Status
            $newState = $this->getStateByStatus($status);
            
            // Set both state and status on the order
            $order->setState($newState);
            $order->setStatus($status);
            
            // Add comment if provided
            if ($comment) {
                $order->addCommentToStatusHistory($comment, $status, false);
            } else {
                // Just add a status change without comment
                $order->addStatusHistoryComment('', $status)
                      ->setIsCustomerNotified(false);
            }
            
            $this->orderRepository->save($order);
            
            // Debug logging
            $this->logger->info('Order ID value: ' . var_export($order->getEntityId(), true));
            $this->logger->info('Order ID type: ' . gettype($order->getEntityId()));
            
            // Convert the entity ID to integer explicitly
            $orderId = is_numeric($order->getEntityId()) ? (int)$order->getEntityId() : 0;
            if ($orderId === 0) {
                $this->logger->error('Invalid order ID detected: ' . var_export($order->getEntityId(), true));
            }          
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error updating order status: ' . $e->getMessage());
            throw new LocalizedException(__('Error updating order status: %1', $e->getMessage()));
        }
    }

    /**
     * Validate order status transition
     *
     * @param Order $order
     * @param string $newStatus
     * @return bool
     * @throws LocalizedException
     */
    private function validateStatusTransition(Order $order, string $newStatus): bool
    {
        $currentState = $order->getState();
        $allowedStates = [];
        
        // Define allowed state transitions based on Magento's order workflow
        // Modified to allow more flexible transitions
        switch ($currentState) {
            case Order::STATE_NEW:
                $allowedStates = [
                    Order::STATE_PROCESSING,
                    Order::STATE_HOLDED,
                    Order::STATE_CANCELED,
                    Order::STATE_COMPLETE  // Allow direct new->complete transition
                ];
                break;
            case Order::STATE_PROCESSING:
                $allowedStates = [
                    Order::STATE_COMPLETE,
                    Order::STATE_HOLDED,
                    Order::STATE_CANCELED
                ];
                break;
            case Order::STATE_COMPLETE:
                $allowedStates = [
                    Order::STATE_CLOSED
                ];
                break;
            case Order::STATE_HOLDED:
                $allowedStates = [
                    Order::STATE_PROCESSING,
                    Order::STATE_CANCELED,
                    Order::STATE_COMPLETE  // Allow holded->complete transition
                ];
                break;
            default:
                // Allow any transition if we don't have a specific rule
                $this->logger->info('No specific rules for state: ' . $currentState . ', allowing transition to: ' . $newStatus);
                return true;
        }
        
        // Get state by status code
        $newState = $this->getStateByStatus($newStatus);
        
        $this->logger->info('Validating transition from ' . $currentState . ' to ' . $newState);
        $this->logger->info('Allowed states: ' . implode(', ', $allowedStates));
        
        if (!in_array($newState, $allowedStates) && $newState !== $currentState) {
            $this->logger->error('Invalid state transition: ' . $currentState . ' to ' . $newState);
            throw new LocalizedException(
                __('Status transition from "%1" to "%2" is not allowed.', $currentState, $newState)
            );
        }
        
        return true;
    }

    /**
     * Get state by status code
     *
     * @param string $status
     * @return string
     */
    private function getStateByStatus(string $status): string
    {
        $state = '';
        
        switch ($status) {
            case 'pending':
            case 'pending_payment':
                $state = Order::STATE_NEW;
                break;
            case 'processing':
                $state = Order::STATE_PROCESSING;
                break;
            case 'complete':
                $state = Order::STATE_COMPLETE;
                break;
            case 'closed':
                $state = Order::STATE_CLOSED;
                break;
            case 'canceled':
                $state = Order::STATE_CANCELED;
                break;
            case 'holded':
                $state = Order::STATE_HOLDED;
                break;
            default:
                // If status code doesn't match a predefined state,
                // check if it's already a state constant
                if (in_array($status, [
                    Order::STATE_NEW,
                    Order::STATE_PROCESSING,
                    Order::STATE_COMPLETE,
                    Order::STATE_CLOSED,
                    Order::STATE_CANCELED,
                    Order::STATE_HOLDED
                ])) {
                    $state = $status;
                } else {
                    // Default to processing if unknown
                    $this->logger->info('Unknown status code: ' . $status . ', defaulting to processing state');
                    $state = Order::STATE_PROCESSING;
                }
                break;
        }
        
        return $state;
    }

}