<?php

/**
 * Uninstall Script
 * 
 * Cleanup when plugin is deleted
 */

// Exit if accessed directly or not uninstalling
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('fmi_funding_calc_options');

// For multisite installations
if (is_multisite()) {
    global $wpdb;

    $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        delete_option('fmi_funding_calc_options');
        restore_current_blog();
    }
}
