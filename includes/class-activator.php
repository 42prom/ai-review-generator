<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 * @subpackage AI_Review_Generator/includes
 */

class AI_Review_Generator_Activator {

    /**
     * Activate the plugin
     *
     * @return void
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default settings
        self::set_default_settings();
        
        // Clear any existing caches
        self::clear_caches();
        
        // Add version to database
        update_option('ai_review_generator_version', AI_REVIEW_GENERATOR_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create plugin database tables
     *
     * @return void
     */
    private static function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Try to create the database tables with error handling
        try {
            $charset_collate = $wpdb->get_charset_collate();
            
            // Reviews table
            $table_name = $wpdb->prefix . 'ai_reviews';
            
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                post_id bigint(20) NOT NULL,
                rating tinyint(1) NOT NULL,
                review_content longtext NOT NULL,
                review_summary text NOT NULL,
                review_pros text,
                review_cons text,
                reviewer_name varchar(100) DEFAULT NULL,
                generated_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                ai_model varchar(100) NOT NULL,
                modified_by_user tinyint(1) DEFAULT 0 NOT NULL,
                published tinyint(1) DEFAULT 1 NOT NULL,
                PRIMARY KEY  (id),
                KEY post_id (post_id)
            ) $charset_collate;";
            
            dbDelta($sql);
            
            // Logs table
            $logs_table = $wpdb->prefix . 'ai_review_logs';
            
            $logs_sql = "CREATE TABLE $logs_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                post_id bigint(20) NOT NULL,
                ai_model varchar(100) NOT NULL,
                tokens_used int(11) NOT NULL DEFAULT 0,
                request_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                status varchar(50) NOT NULL,
                error_message text,
                PRIMARY KEY  (id),
                KEY post_id (post_id)
            ) $charset_collate;";
            
            dbDelta($logs_sql);
        } catch (Exception $e) {
            // Log the error
            error_log('AI Review Generator - Error creating tables: ' . $e->getMessage());
            
            // Continue with activation despite the error
        }
    }

    /**
     * Set default plugin settings
     *
     * @return void
     */
    private static function set_default_settings() {
        try {
            $default_settings = [
                // General settings
                'auto_generate_default' => 'enabled',
                'review_position' => 'after',
                'review_display_style' => 'boxed',
                'reviews_per_post' => 1,
                'cache_expiration' => 86400, // 1 day
                
                // AI model settings
                'ai_model' => 'deepseek',
                'deepseek_endpoint' => 'https://api.deepseek.com/v1/chat/completions',
                'deepseek_model_name' => 'deepseek-chat',
                'mistral_endpoint' => 'https://api.mistral.ai/v1/chat/completions',
                'mistral_model_name' => 'mistral-tiny',
                'llama3_endpoint' => 'https://api.together.xyz/v1/completions',
                'llama3_model_name' => 'meta-llama/Llama-3-8b-chat',
                'openrouter_endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
                'openrouter_model_name' => 'meta-llama/llama-3-8b-chat',
                'ai_temperature' => 0.7,
                
                // Review settings
                'review_tone' => 'professional',
                'enable_reviewer_names' => 'no',
                'reviewer_name_type' => 'random',
                'reviewer_name_format' => 'full',
                'min_word_count' => 200,
                'max_word_count' => 500,
                'review_structure' => 'pros_cons',
                
                // Style settings
                'box_bg_color' => '#f9f9f9',
                'box_border_color' => '#e0e0e0',
                'box_border_width' => '1px',
                'box_border_radius' => '5px',
                'box_padding' => '20px',
                'box_margin' => '20px 0',
                'title_color' => '#333333',
                'text_color' => '#666666',
                'star_color_primary' => '#FFD700',
                'star_color_secondary' => '#E0E0E0',
                'enable_dark_mode' => 'yes',
                
                // Dark mode settings
                'dark_box_bg_color' => '#2d2d2d',
                'dark_box_border_color' => '#4d4d4d',
                'dark_title_color' => '#ffffff',
                'dark_text_color' => '#cccccc',
            ];
            
            // Get existing settings (if any)
            $existing_settings = get_option('ai_review_generator_settings', []);
            
            // Merge with defaults (preserving existing values)
            $settings = wp_parse_args($existing_settings, $default_settings);
            
            // Save settings
            update_option('ai_review_generator_settings', $settings);
        } catch (Exception $e) {
            // Log error but continue with activation
            error_log('AI Review Generator - Error setting default settings: ' . $e->getMessage());
        }
    }

    /**
     * Clear any existing caches
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
}