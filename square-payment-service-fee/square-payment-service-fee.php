<?php
/**
 * Plugin Name: Square Payment with Service Fee
 * Plugin URI: https://github.com/binarybcc/sqpmt
 * Description: Accept payments through Square with an automatic service fee. Customers can make secure payments with credit cards, and a configurable service fee is automatically added. Features AES-256-GCM encryption and rate limiting protection.
 * Version: 1.0.1
 * Author: Binary BCC
 * Author URI: https://github.com/binarybcc
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: square-payment-service-fee
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SQPMT_VERSION', '1.0.1');
define('SQPMT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SQPMT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SQPMT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_square_payment_service_fee() {
    require_once SQPMT_PLUGIN_DIR . 'includes/class-activator.php';
    SQPMT_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_square_payment_service_fee() {
    require_once SQPMT_PLUGIN_DIR . 'includes/class-deactivator.php';
    SQPMT_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_square_payment_service_fee');
register_deactivation_hook(__FILE__, 'deactivate_square_payment_service_fee');

/**
 * Include required files
 */
require_once SQPMT_PLUGIN_DIR . 'includes/class-square-api.php';
require_once SQPMT_PLUGIN_DIR . 'includes/class-payment-form.php';
require_once SQPMT_PLUGIN_DIR . 'includes/class-settings.php';
require_once SQPMT_PLUGIN_DIR . 'includes/class-calculator.php';
require_once SQPMT_PLUGIN_DIR . 'includes/class-validator.php';
require_once SQPMT_PLUGIN_DIR . 'includes/class-transaction-logger.php';
require_once SQPMT_PLUGIN_DIR . 'includes/class-email-notifications.php';
require_once SQPMT_PLUGIN_DIR . 'includes/class-transaction-history.php';
require_once SQPMT_PLUGIN_DIR . 'includes/class-rate-limiter.php';

/**
 * Main plugin class
 */
class Square_Payment_Service_Fee {

    /**
     * The single instance of the class.
     */
    protected static $_instance = null;

    /**
     * Main Instance.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    /**
     * Initialize plugin components
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('square-payment-service-fee', false, dirname(SQPMT_PLUGIN_BASENAME) . '/languages');

        // Initialize components
        SQPMT_Settings::instance();
        SQPMT_Payment_Form::instance();
        SQPMT_Transaction_History::instance();
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on pages with the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'square_payment_form')) {

            // Square Web Payments SDK
            $sandbox_mode = get_option('sqpmt_sandbox_mode', 'yes');
            if ($sandbox_mode === 'yes') {
                wp_enqueue_script('square-web-sdk', 'https://sandbox.web.squarecdn.com/v1/square.js', array(), null, true);
            } else {
                wp_enqueue_script('square-web-sdk', 'https://web.squarecdn.com/v1/square.js', array(), null, true);
            }

            // Plugin styles
            wp_enqueue_style(
                'sqpmt-payment-form',
                SQPMT_PLUGIN_URL . 'assets/css/payment-form.css',
                array(),
                SQPMT_VERSION
            );

            // Plugin JavaScript
            wp_enqueue_script(
                'sqpmt-payment-form',
                SQPMT_PLUGIN_URL . 'assets/js/payment-form.js',
                array('jquery', 'square-web-sdk'),
                SQPMT_VERSION,
                true
            );

            // Localize script with settings
            wp_localize_script('sqpmt-payment-form', 'sqpmtSettings', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sqpmt_payment_nonce'),
                'applicationId' => get_option('sqpmt_application_id', ''),
                'locationId' => get_option('sqpmt_location_id', ''),
                'serviceFee' => floatval(get_option('sqpmt_service_fee', '3.5')),
                'sandboxMode' => get_option('sqpmt_sandbox_mode', 'yes'),
                'strings' => array(
                    'processing' => __('Processing payment...', 'square-payment-service-fee'),
                    'error' => __('Payment failed. Please try again.', 'square-payment-service-fee'),
                )
            ));
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on plugin settings pages
        if (strpos($hook, 'square-payment') !== false) {
            wp_enqueue_style(
                'sqpmt-admin',
                SQPMT_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                SQPMT_VERSION
            );

            wp_enqueue_script(
                'sqpmt-admin',
                SQPMT_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                SQPMT_VERSION,
                true
            );

            wp_localize_script('sqpmt-admin', 'sqpmtAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sqpmt_admin_nonce'),
            ));
        }
    }
}

/**
 * Returns the main instance of Square_Payment_Service_Fee.
 */
function SQPMT() {
    return Square_Payment_Service_Fee::instance();
}

// Initialize the plugin
SQPMT();
