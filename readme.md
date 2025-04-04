# Vendor_CustomOrderProcessing

This Magento 2 module enhances order processing workflow by providing a REST API for order status updates, automated order status change logging, and customer email notifications.

## Features

- **Order Status Update API**: Update order status via REST API
- **Status Change Logging**: Automatically log order status changes to a custom database table
- **Email Notifications**: Send automated email notifications when orders are marked as shipped
- **Status Transition Validation**: Validate order status transitions to maintain data integrity

## Installation

### Manual Installation

1. Upload the zip under directory structure in your Magento installation:
   ```
   app/code/
   ```

2. Enable the module:
   ```bash
   bin/magento module:enable Vendor_CustomOrderProcessing
   ```

4. Run Magento setup upgrade:
   ```bash
   bin/magento setup:upgrade
   ```

5. Compile code and if in production mode deploy static content:
   ```bash
   bin/magento setup:di:compile
   bin/magento setup:static-content:deploy
   ```

6. Clean the cache:
   ```bash
   bin/magento cache:clean
   ```

## Usage

### API Endpoints

The module provides a REST API endpoint for updating order status:

**Endpoint:** `POST /rest/V1/orders/status`

**Headers:**
- `Content-Type: application/json`
- `Authorization: Bearer <admin-token>`

**Request Body:**
```json
{
  "incrementId": "000000001",
  "status": "processing"
}
```

**Parameters:**
- `incrementId` (required): The order increment ID
- `status` (required): The new order status to set

**Example using cURL:**
```bash
curl -X POST \
  "https://your-magento-domain.com/rest/V1/orders/status" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <admin-token>" \
  -d '{
    "incrementId": "000000001",
    "status": "processing"    
  }'
```

### Status Logging

Order status changes are automatically logged to the `vendor_order_status_log` table with the following information:
- Order ID
- Order Increment ID
- Old Status
- New Status
- Timestamp

### Email Notifications

When an order status is changed to "complete" or "shipped", the module automatically sends an email notification to the customer using Magento's standard shipment email template.

## Architectural Decisions

### Module Structure

The module follows Magento 2's recommended module structure for optimal maintainability:

- **API Interfaces**: Define contracts for module functionality, allowing for easier integration and testing
- **Models**: Implement business logic and data handling
- **Observer Pattern**: Used for event-driven actions like status change logging
- **Repository Pattern**: Used for data access, ensuring proper encapsulation of database operations

### Performance Considerations

- **Optimized Database Operations**: Uses repository patterns instead of direct SQL queries
- **Validation**: Comprehensive validation to prevent invalid status transitions

### Email Implementation

The module uses Magento's built-in email infrastructure with:
- Standard email templates for consistency
- Fallback mechanisms for missing data
- Extensive error logging

### Extensibility Points

The module is designed to be extended through:
- API interfaces that can be implemented by other modules
- Events and observers
- Dependency injection configuration

## Troubleshooting

### Common Issues

1. **API Authorization Errors**: Ensure to use valid admin token 

2. **Email Sending Failures**: Check Magento email configuration in Admin > Stores > Configuration > Advanced > System > Mail Sending Settings