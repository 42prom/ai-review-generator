<?php
/**
 * AI Review Generator
 *
 * @package           AI_Review_Generator
 * @author            Mikheili Nakeuri
 * @copyright         2025 Mikheili Nakeuri
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       AI Review Generator
 * Plugin URI:        https://github.com/42prom
 * Description:       Automatically generates AI-powered reviews for blog posts and WooCommerce products using free or low-cost AI models.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Mikheili Nakeuri
 * Author URI:        https://github.com/42prom
 * Text Domain:       ai-review-generator
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://github.com/42prom
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('AI_REVIEW_GENERATOR_VERSION', '1.0.0');
define('AI_REVIEW_GENERATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_REVIEW_GENERATOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_REVIEW_GENERATOR_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_ai_review_generator() {
    // Create tables and set default options directly without loading classes
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Create reviews table
    $table_name = $wpdb->prefix . 'ai_reviews';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
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
    
    // Create logs table
    $logs_table = $wpdb->prefix . 'ai_review_logs';
    
    $logs_sql = "CREATE TABLE IF NOT EXISTS $logs_table (
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
    
    // Set default options
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
    
    // Only add settings if they don't exist
    if (!get_option('ai_review_generator_settings')) {
        add_option('ai_review_generator_settings', $default_settings);
    }
    
    // Set plugin version
    update_option('ai_review_generator_version', AI_REVIEW_GENERATOR_VERSION);
    
    // Clear any caches
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_ai_review_generator() {
    // Simple deactivation - just clear any caches
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'activate_ai_review_generator');
register_deactivation_hook(__FILE__, 'deactivate_ai_review_generator');

/**
 * Initialize plugin after plugins are loaded
 */
function ai_review_init() {
    // Include and initialize the main plugin class
    $core_file = AI_REVIEW_GENERATOR_PLUGIN_DIR . 'includes/class-ai-review-generator.php';
    
    if (file_exists($core_file)) {
        try {
            require_once $core_file;
            if (class_exists('AI_Review_Generator')) {
                $plugin = new AI_Review_Generator();
                $plugin->run();
            } else {
                error_log('AI Review Generator: Core class not found even though file exists.');
            }
        } catch (Exception $e) {
            error_log('AI Review Generator Error: ' . $e->getMessage());
            add_action('admin_notices', function() use ($e) {
                echo '<div class="error"><p>';
                echo 'AI Review Generator Error: ' . esc_html($e->getMessage());
                echo '</p></div>';
            });
        }
    } else {
        error_log('AI Review Generator: Core file missing - ' . $core_file);
        add_action('admin_notices', function() {
            echo '<div class="error"><p>';
            echo 'AI Review Generator: Core plugin files are missing. Please reinstall the plugin.';
            echo '</p></div>';
        });
    }
}

// Hook into WordPress init with our function
add_action('plugins_loaded', 'ai_review_init');