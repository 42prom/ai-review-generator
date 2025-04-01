<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get uninstall options
$settings = get_option('ai_review_generator_settings', []);
$delete_data = isset($settings['delete_data_on_uninstall']) ? $settings['delete_data_on_uninstall'] : 'no';

// Only delete data if the option is enabled
if ($delete_data === 'yes') {
    // Delete custom tables
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'ai_reviews',
        $wpdb->prefix . 'ai_review_logs',
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    // Delete all post meta
    delete_post_meta_by_key('_ai_review_auto_generate');
    
    // Delete plugin options
    delete_option('ai_review_generator_settings');
    delete_option('ai_review_generator_version');
    
    // Delete any transients
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_ai_review_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_ai_review_%'");
}