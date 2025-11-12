# Square Payment with Service Fee

A WordPress plugin that allows customers to make secure payments through Square with an automatic service fee added to each transaction.

## Features

- **Secure Payment Processing**: Uses Square's Web Payments SDK for PCI-compliant card processing
- **Automatic Service Fee Calculation**: Configurable percentage fee automatically added to payments
- **Real-time Calculations**: Payment breakdown displayed instantly as users enter amounts
- **Complete Customer Information**: Collects full customer details including billing address
- **Transaction Logging**: Comprehensive database logging of all transactions
- **Email Notifications**: Automatic receipt emails to customers and notifications to admins
- **Transaction History**: Admin interface to view, search, and filter all transactions
- **CSV Export**: Export transaction data to CSV for reporting
- **Sandbox Mode**: Test payments with Square's sandbox environment
- **Mobile Responsive**: Optimized for all device sizes
- **Security Focused**: Input validation, nonce verification, and encrypted credential storage

## Installation

### Manual Installation

1. Download or clone this repository
2. Upload the `square-payment-service-fee` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure your Square API credentials in Settings > Square Payment

### Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- SSL certificate (HTTPS) - required by Square
- A Square account (free to create)

## Setup Guide

### 1. Create a Square Account

1. Visit [Square Developer Portal](https://developer.squareup.com/)
2. Sign up for a free Square account or log in
3. Access the Developer Dashboard

### 2. Get Your Square API Credentials

#### For Sandbox (Testing) Mode:

1. Go to [Square Developer Dashboard](https://developer.squareup.com/apps)
2. Create a new application or select an existing one
3. Navigate to the "Credentials" tab
4. Under "Sandbox" section, copy:
   - **Application ID** (starts with `sandbox-`)
   - **Access Token** (starts with `EAAA`)
5. Go to "Locations" tab and copy a **Location ID**

#### For Production (Live) Mode:

1. In the same application, go to "Credentials" tab
2. Under "Production" section, copy:
   - **Application ID**
   - **Access Token**
3. Go to "Locations" tab and copy your production **Location ID**

**Important Security Notes:**
- Never share your Access Token
- Never commit credentials to version control
- Use Sandbox mode for testing
- Only switch to Production when ready for real payments

### 3. Configure Plugin Settings

1. In WordPress, go to **Settings > Square Payment**
2. Enter your Square credentials:
   - Application ID
   - Access Token
   - Location ID
3. Select **Sandbox Mode** for testing (or Production for live payments)
4. Set your desired **Service Fee Percentage** (default: 3.5%)
5. Customize success and failure messages
6. Configure email notification preferences
7. Click **Save Settings**
8. Click **Test Connection** to verify your credentials

### 4. Add Payment Form to Your Site

Use the shortcode `[square_payment_form]` on any page or post:

```
[square_payment_form]
```

The form will automatically display with all required fields.

## Usage

### Customer Experience

1. Customer navigates to a page with the payment form
2. Enters the amount they owe
3. Sees real-time breakdown of original amount, service fee, and total
4. Fills in required customer information:
   - Full Name
   - Email Address
   - Phone Number
   - Street Address
   - City, State, ZIP
5. Enters card details in Square's secure payment fields
6. Clicks "Pay Now"
7. Receives confirmation message and email receipt

### Admin Experience

#### View Transaction History

1. Go to **Settings > Payment History**
2. View all transactions with:
   - Date and time
   - Transaction ID
   - Customer information
   - Amount breakdown
   - Status
3. Use filters to search by:
   - Customer name or email
   - Date range
   - Transaction status
4. Export transactions to CSV

#### Monitor Statistics

The Payment History page displays:
- Total number of transactions
- Successful vs. failed payments
- Total revenue collected
- Total service fees collected

## Testing

### Sandbox Mode Testing

1. Enable **Sandbox Mode** in plugin settings
2. Use Square's test card numbers:
   - **Visa**: `4111 1111 1111 1111`
   - **Mastercard**: `5105 1051 0510 5100`
   - **Amex**: `3782 822463 10005`
   - **Discover**: `6011 1111 1111 1117`
3. Use any future expiration date
4. Use any 3-4 digit CVV
5. Use any ZIP code (e.g., 12345)

### Test Scenarios

- **Successful Payment**: Use valid test card numbers
- **Declined Card**: Use card number `4000 0000 0000 0002`
- **Insufficient Funds**: Use card number `4000 0000 0000 9995`
- **Invalid CVV**: Use card number `4000 0000 0000 0127`

### Validation Testing

Test the following validation scenarios:
- Amount less than $1.00 (should fail)
- Invalid email format (should fail)
- Phone number with less than 10 digits (should fail)
- ZIP code that's not 5 digits (should fail)
- Empty required fields (submit button should be disabled)

## Field Validation Rules

| Field | Rules | Error Messages |
|-------|-------|----------------|
| Amount | Required, minimum $1.00, numeric | "Amount must be at least $1.00" |
| Name | Required, minimum 2 characters | "Name must be at least 2 characters" |
| Email | Required, valid email format | "Please enter a valid email address" |
| Phone | Required, exactly 10 digits | "Phone number must be 10 digits" |
| Address | Required, minimum 5 characters | "Address must be at least 5 characters" |
| City | Required, minimum 2 characters | "City must be at least 2 characters" |
| State | Required, valid US state | "Please select a valid state" |
| ZIP | Required, exactly 5 digits | "ZIP code must be 5 digits" |

## Security Features

### Card Data Security
- **PCI Compliance**: Card data never touches your WordPress server
- **Tokenization**: Square SDK tokenizes card data before transmission
- **HTTPS Required**: All payment data transmitted over SSL
- **No Card Storage**: Card numbers are never stored in your database

### Data Protection
- **Input Sanitization**: All user inputs are sanitized and validated
- **SQL Injection Prevention**: Uses prepared statements for all database queries
- **XSS Protection**: All output is properly escaped
- **CSRF Protection**: WordPress nonces used for all form submissions
- **Encrypted Storage**: Access tokens stored with encryption
- **Secure Headers**: Proper security headers for API communication

### WordPress Security Best Practices
- Follows WordPress Coding Standards
- Uses WordPress HTTP API for external requests
- Implements proper capability checks for admin functions
- Sanitizes and validates all inputs
- Escapes all outputs

## Troubleshooting

### Payment Form Not Displaying

**Issue**: Shortcode shows but form doesn't appear

**Solutions**:
1. Check that API credentials are configured in Settings
2. Verify JavaScript is enabled in browser
3. Check browser console for errors
4. Ensure site is using HTTPS
5. Clear WordPress cache if using caching plugin

### "Square.js Failed to Load" Error

**Issue**: Square SDK not loading

**Solutions**:
1. Verify internet connection
2. Check that site is using HTTPS
3. Verify no Content Security Policy blocking Square CDN
4. Check browser console for specific errors
5. Try different browser

### Test Connection Fails

**Issue**: API credentials not working

**Solutions**:
1. Verify you're using Sandbox credentials in Sandbox mode
2. Verify you're using Production credentials in Production mode
3. Check for typos in Application ID, Access Token, or Location ID
4. Ensure credentials are from the same Square application
5. Verify your Square application is active
6. Try regenerating Access Token in Square Dashboard

### Payment Declined

**Issue**: Valid card being declined

**Solutions**:
1. In Sandbox mode, ensure using test card numbers
2. Verify card expiration date is in the future
3. Check Square dashboard for declined reason
4. Ensure amount meets minimum requirements
5. Try different test card number

### Email Notifications Not Sending

**Issue**: Customers/admins not receiving emails

**Solutions**:
1. Check email settings are enabled in Settings > Square Payment
2. Verify site can send emails (test with standard WordPress email)
3. Check spam folders
4. Configure SMTP plugin if WordPress mail() not working
5. Check email address in Settings > General

### Transaction History Not Showing

**Issue**: Payments processed but not appearing in history

**Solutions**:
1. Check database table was created (wp_sqpmt_transactions)
2. Verify transaction completed successfully
3. Try clearing filters in transaction history page
4. Check PHP error logs for database errors

## API Reference

### Shortcode

```php
[square_payment_form]
```

Currently, the shortcode accepts no parameters, but all settings are controlled via the admin settings page.

### Action Hooks

```php
// Add custom code after plugin activation
do_action('sqpmt_activated');

// Add custom code before payment processing
do_action('sqpmt_before_payment', $payment_data);

// Add custom code after successful payment
do_action('sqpmt_payment_success', $transaction_data);

// Add custom code after failed payment
do_action('sqpmt_payment_failed', $transaction_data);
```

### Filter Hooks

```php
// Modify service fee percentage
$fee = apply_filters('sqpmt_service_fee_percentage', $fee, $amount);

// Modify success message
$message = apply_filters('sqpmt_success_message', $message, $transaction_id);

// Modify failure message
$message = apply_filters('sqpmt_failure_message', $message, $error);

// Modify customer email content
$content = apply_filters('sqpmt_customer_email_content', $content, $transaction_data);

// Modify admin email content
$content = apply_filters('sqpmt_admin_email_content', $content, $transaction_data);
```

## File Structure

```
square-payment-service-fee/
├── square-payment-service-fee.php  # Main plugin file
├── README.md                       # This file
├── includes/
│   ├── class-activator.php         # Plugin activation
│   ├── class-deactivator.php       # Plugin deactivation
│   ├── class-square-api.php        # Square API integration
│   ├── class-payment-form.php      # Payment form handling
│   ├── class-settings.php          # Admin settings
│   ├── class-calculator.php        # Fee calculations
│   ├── class-validator.php         # Input validation
│   ├── class-transaction-logger.php # Transaction logging
│   ├── class-email-notifications.php # Email handling
│   └── class-transaction-history.php # Admin history page
├── assets/
│   ├── js/
│   │   ├── payment-form.js         # Frontend form JavaScript
│   │   └── admin.js                # Admin JavaScript
│   └── css/
│       ├── payment-form.css        # Frontend form styles
│       └── admin.css               # Admin styles
└── languages/                      # Translation files
```

## Database Schema

### Table: wp_sqpmt_transactions

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| transaction_id | varchar(255) | Square transaction ID |
| amount | decimal(10,2) | Original amount |
| service_fee | decimal(10,2) | Service fee charged |
| total_amount | decimal(10,2) | Total amount charged |
| status | varchar(50) | Transaction status |
| customer_name | varchar(255) | Customer's name |
| customer_email | varchar(255) | Customer's email |
| customer_phone | varchar(50) | Customer's phone |
| customer_address_line1 | varchar(255) | Address line 1 |
| customer_address_line2 | varchar(255) | Address line 2 (optional) |
| customer_city | varchar(100) | City |
| customer_state | varchar(50) | State |
| customer_zip | varchar(20) | ZIP code |
| customer_country | varchar(50) | Country (default: US) |
| error_message | text | Error message (if failed) |
| created_at | datetime | Timestamp |

**Security Note**: Card numbers are NEVER stored in the database.

## Changelog

### Version 1.0.0
- Initial release
- Square Web Payments SDK integration
- Automatic service fee calculation
- Customer information collection
- Transaction logging
- Email notifications
- Admin transaction history
- CSV export functionality
- Sandbox mode support
- Mobile responsive design

## Support

For issues, questions, or feature requests:
1. Check this documentation
2. Review the Troubleshooting section
3. Submit an issue on GitHub: [GitHub Issues](https://github.com/binarybcc/sqpmt/issues)

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Credits

- **Author**: Binary BCC
- **Square API**: [Square Developer](https://developer.squareup.com/)
- **WordPress**: [WordPress.org](https://wordpress.org/)

## Development

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Coding Standards

This plugin follows:
- WordPress Coding Standards
- PHP PSR-12 style guide
- WordPress security best practices
- Accessibility guidelines (WCAG 2.1)

## Additional Resources

- [Square Developer Documentation](https://developer.squareup.com/docs)
- [Square Web Payments SDK](https://developer.squareup.com/docs/web-payments/overview)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

---

**Version**: 1.0.0
**Last Updated**: 2025-11-12
**Requires WordPress**: 5.0+
**Requires PHP**: 7.2+
**Tested up to WordPress**: 6.4
**License**: GPL v2 or later
