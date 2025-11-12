<?php
/**
 * Fired during plugin deactivation.
 *
 * @package Square_Payment_Service_Fee
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Deactivator class
 */
class SQPMT_Deactivator {

    /**
     * Deactivate the plugin.
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Note: We don't delete the database table or options on deactivation
        // This preserves transaction history in case the plugin is reactivated
        // To completely remove data, user should uninstall the plugin
    }
}
