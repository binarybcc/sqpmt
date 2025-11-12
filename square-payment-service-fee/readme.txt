=== Square Payment with Service Fee ===
Contributors: binarybcc
Tags: payment, square, service fee, payment gateway, credit card
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept secure payments through Square with an automatic service fee added to each transaction.

== Description ==

Square Payment with Service Fee is a comprehensive WordPress plugin that allows you to accept credit card payments through Square's secure payment gateway while automatically adding a configurable service fee to each transaction.

= Key Features =

* **Secure Payment Processing** - Uses Square's Web Payments SDK for PCI-compliant card processing
* **Automatic Service Fee** - Configurable percentage fee automatically added to payments
* **Real-time Calculations** - Payment breakdown displayed instantly as users enter amounts
* **Customer Information** - Collects complete customer details including billing address
* **Transaction Logging** - Comprehensive database logging of all transactions
* **Email Notifications** - Automatic receipts to customers and notifications to admins
* **Transaction History** - Admin interface to view, search, and filter transactions
* **CSV Export** - Export transaction data for reporting
* **Sandbox Mode** - Test payments safely before going live
* **Mobile Responsive** - Optimized for all device sizes

= Use Cases =

* Service providers who need to pass credit card processing fees to customers
* Organizations accepting donations or payments with transparent fee disclosure
* Businesses offering payment plans with processing fees
* Event registration with service charges
* Any business that needs to collect payments with an additional fee

= Security =

* PCI compliant - card data never touches your server
* Square's tokenization protects sensitive information
* All payments transmitted over HTTPS
* Card numbers are never stored in your database
* Input validation and sanitization
* Encrypted credential storage

= Simple Setup =

1. Install and activate the plugin
2. Get your Square API credentials (free Square account)
3. Configure settings in WordPress admin
4. Add `[square_payment_form]` shortcode to any page
5. Start accepting payments!

= Requirements =

* WordPress 5.0 or higher
* PHP 7.2 or higher
* SSL certificate (HTTPS)
* Square account (free to create)

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "Square Payment with Service Fee"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Log in to your WordPress admin panel
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the zip file and click "Install Now"
5. Activate the plugin

= After Activation =

1. Go to Settings > Square Payment
2. Enter your Square API credentials
3. Configure your service fee percentage
4. Save settings and test the connection
5. Add the shortcode `[square_payment_form]` to any page

== Frequently Asked Questions ==

= Do I need a Square account? =

Yes, you need a free Square account to use this plugin. Sign up at https://squareup.com/

= Where do I get my API credentials? =

1. Visit https://developer.squareup.com/apps
2. Create or select an application
3. Go to the Credentials tab
4. Copy your Application ID, Access Token, and Location ID

= Is this plugin secure? =

Yes! This plugin uses Square's Web Payments SDK, which means card data goes directly to Square and never touches your WordPress server. This makes the plugin PCI compliant.

= Can I test before going live? =

Absolutely! The plugin includes Sandbox mode for testing. Use Square's test card numbers to process test payments.

= What happens to the service fee? =

The service fee is added to the payment amount and the total is charged to the customer's card. All funds (original amount + service fee) are deposited to your Square account.

= Can I customize the service fee percentage? =

Yes, you can set any percentage in the plugin settings. The default is 3.5%.

= Will customers receive a receipt? =

Yes, customers automatically receive an email receipt after successful payment, showing the breakdown of amount, service fee, and total.

= Can I see payment history? =

Yes, go to Settings > Payment History to view all transactions, search by customer, filter by date, and export to CSV.

= Is the form mobile-friendly? =

Yes, the payment form is fully responsive and optimized for mobile devices.

= What countries are supported? =

This plugin currently supports US-based businesses using Square. The form collects US addresses and ZIP codes.

== Screenshots ==

1. Payment form with real-time fee calculation
2. Admin settings page
3. Transaction history page
4. Email receipt sent to customers
5. Mobile responsive payment form

== Changelog ==

= 1.0.0 =
* Initial release
* Square Web Payments SDK integration
* Automatic service fee calculation
* Customer information collection
* Transaction logging
* Email notifications
* Admin transaction history
* CSV export functionality
* Sandbox mode support
* Mobile responsive design

== Upgrade Notice ==

= 1.0.0 =
Initial release of Square Payment with Service Fee plugin.

== Support ==

For support, please visit our GitHub repository:
https://github.com/binarybcc/sqpmt/issues

== Privacy Policy ==

This plugin collects and stores the following information:
* Customer name, email, phone, and billing address
* Transaction amounts and fees
* Payment status and transaction IDs

This plugin does NOT store:
* Credit card numbers
* CVV codes
* Full card details

All payment processing is handled securely by Square. Card data goes directly from the customer's browser to Square's servers and never touches your WordPress installation.

== Third-Party Services ==

This plugin relies on Square's services:
* Square Web Payments SDK (https://web.squarecdn.com/v1/square.js)
* Square Payments API (https://connect.squareup.com/)

By using this plugin, you agree to Square's Terms of Service:
https://squareup.com/us/en/legal/general/ua

== Credits ==

* Square API - https://developer.squareup.com/
* WordPress - https://wordpress.org/

== Developer Notes ==

This plugin follows WordPress coding standards and best practices:
* Uses WordPress HTTP API for external requests
* Implements proper nonce verification
* Sanitizes all inputs and escapes all outputs
* Uses prepared statements for database queries
* Follows WordPress plugin security guidelines
