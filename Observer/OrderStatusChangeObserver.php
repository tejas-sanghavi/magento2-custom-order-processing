<?php
/**
 * Order Status Change Observer
 *
 * @category  Vendor
 * @package   Vendor_CustomOrderProcessing
 */
declare(strict_types=1);

namespace Vendor\CustomOrderProcessing\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Vendor\CustomOrderProcessing\Model\OrderStatusLogFactory;
use Vendor\CustomOrderProcessing\Model\ResourceModel\OrderStatusLog as OrderStatusLogResource;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Order Status Change Observer
 */
class OrderStatusChangeObserver implements ObserverInterface
{
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
     * @var RequestInterface
     */
    private $request;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param LoggerInterface $logger
     * @param OrderStatusLogFactory $orderStatusLogFactory
     * @param OrderStatusLogResource $orderStatusLogResource
     * @param RequestInterface $request
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        LoggerInterface $logger,
        OrderStatusLogFactory $orderStatusLogFactory,
        OrderStatusLogResource $orderStatusLogResource,
        RequestInterface $request,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->orderStatusLogFactory = $orderStatusLogFactory;
        $this->orderStatusLogResource = $orderStatusLogResource;
        $this->request = $request;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        $origData = $order->getOrigData();

        // Check if status has changed
        if ($origData && isset($origData['status']) && $origData['status'] !== $order->getStatus()) {
            $oldStatus = $origData['status'];
            $newStatus = $order->getStatus();
            
            // Log the status change
            $this->logStatusChange($order, $oldStatus, $newStatus);
            
            // If order is shipped, send notification
            if ($newStatus === Order::STATE_COMPLETE || $newStatus === 'shipped') {
                $this->sendShippedNotification($order);
            }
        }
    }

    /**
     * Log order status change
     *
     * @param Order $order
     * @param string $oldStatus
     * @param string $newStatus
     * @return void
     */
    private function logStatusChange(Order $order, string $oldStatus, string $newStatus): void
    {
        try {
            $statusLog = $this->orderStatusLogFactory->create();
            $statusLog->setOrderId((int)$order->getId());
            $statusLog->setIncrementId($order->getIncrementId());
            $statusLog->setOldStatus($oldStatus);
            $statusLog->setNewStatus($newStatus);           
            
            $this->orderStatusLogResource->save($statusLog);
            
        } catch (\Exception $e) {
            $this->logger->error('Error logging order status change: ' . $e->getMessage());
        }
    }

    /**
     * Send shipped notification to customer
     *
     * @param Order $order
     * @return void
     */
    private function sendShippedNotification(Order $order): void
    {
        try {
            if (!$order->getCustomerEmail()) {
                return;
            }
            
            $storeId = $order->getStoreId();
            $customerName = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
            
            // Prepare email template
            $this->inlineTranslation->suspend();
            
            $templateVars = [
                'order' => $order,
                'order_id' => $order->getIncrementId(),
                'customer_name' => $customerName,
                'store' => $this->storeManager->getStore($storeId)
            ];
            
            $sender = [
                'name' => 'Sales Team',
                'email' => 'sales@example.com'
            ];
            
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('sales_email_shipment_template')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId
                ])
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($order->getCustomerEmail(), $customerName) 
                ->getTransport();

            $transport->sendMessage();
            
            $this->inlineTranslation->resume();
            
            $this->logger->info('Shipped notification sent to customer for order #' . $order->getIncrementId());
        } catch (\Exception $e) {
            $this->logger->error('Error sending shipped notification: ' . $e->getMessage());
        }
    }
}