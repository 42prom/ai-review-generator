<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 * @subpackage AI_Review_Generator/includes
 */

class AI_Review_Generator_Deactivator {

    /**
     * Deactivate the plugin
     *
     * @return void
     */
    public static function deactivate() {
        // Clear any caches
        self::clear_caches();
        
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Clear any caches
     *
     * @return void
     */
    private static function clear_caches() {
        // Clear API cache
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_ai_review_%' OR option_name LIKE '_transient_timeout_ai_review_%'");
        
        // Clear known caching plugins
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        
        // Clear LiteSpeed Cache if active
        if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
            LiteSpeed_Cache_API::purge_all();
        }
    }

    /**
     * Clear scheduled events
     *
     * @return void
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('ai_review_generator_cleanup_logs');
        wp_clear_scheduled_hook('ai_review_generator_clean_cache');
    }
}