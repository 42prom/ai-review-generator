<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 * @subpackage AI_Review_Generator/admin
 */

class AI_Review_Generator_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;
    
    /**
     * Database instance
     *
     * @var AI_Review_Generator_Database
     */
    private $db;
    
    /**
     * Review Generator instance
     *
     * @var AI_Review_Generator_Review_Generator
     */
    private $review_generator;
    
    /**
     * AI Models instance
     *
     * @var AI_Review_Generator_AI_Models
     */
    private $ai_models;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db = new AI_Review_Generator_Database();
        $this->review_generator = new AI_Review_Generator_Review_Generator();
        $this->ai_models = new AI_Review_Generator_AI_Models();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/admin.css', [], $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery', 'wp-color-picker'], $this->version, false);
        
        wp_localize_script($this->plugin_name, 'ai_review_generator', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_review_generator_nonce'),
            'strings' => [
                'generating' => __('Generating review...', 'ai-review-generator'),
                'success' => __('Review generated successfully!', 'ai-review-generator'),
                'error' => __('Error generating review:', 'ai-review-generator'),
                'confirm_delete' => __('Are you sure you want to delete this review?', 'ai-review-generator'),
                'confirm_regenerate' => __('Are you sure you want to regenerate this review? This will overwrite any existing review.', 'ai-review-generator'),
                'testing' => __('Testing connection...', 'ai-review-generator'),
                'test_success' => __('Connection successful!', 'ai-review-generator'),
                'test_error' => __('Connection failed:', 'ai-review-generator'),
            ],
        ]);
    }

    /**
     * Add admin menu pages
     *
     * @return void
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('AI Review Generator', 'ai-review-generator'),
            __('AI Reviews', 'ai-review-generator'),
            'manage_options',
            'ai-review-generator',
            [$this, 'display_dashboard_page'],
            'dashicons-star-filled',
            25
        );
        
        // Dashboard submenu
        add_submenu_page(
            'ai-review-generator',
            __('Dashboard', 'ai-review-generator'),
            __('Dashboard', 'ai-review-generator'),
            'manage_options',
            'ai-review-generator',
            [$this, 'display_dashboard_page']
        );
        
        // Reviews submenu
        add_submenu_page(
            'ai-review-generator',
            __('Reviews', 'ai-review-generator'),
            __('Reviews', 'ai-review-generator'),
            'manage_options',
            'ai-review-generator-reviews',
            [$this, 'display_reviews_page']
        );
        
        // Settings submenu
        add_submenu_page(
            'ai-review-generator',
            __('Settings', 'ai-review-generator'),
            __('Settings', 'ai-review-generator'),
            'manage_options',
            'ai-review-generator-settings',
            [$this, 'display_settings_page']
        );
        
        // Logs submenu
        add_submenu_page(
            'ai-review-generator',
            __('Logs', 'ai-review-generator'),
            __('Logs', 'ai-review-generator'),
            'manage_options',
            'ai-review-generator-logs',
            [$this, 'display_logs_page']
        );
    }

    /**
     * Display dashboard page
     *
     * @return void
     */
    public function display_dashboard_page() {
        // Get stats
        $total_reviews = $this->db->count_reviews();
        $published_reviews = $this->db->count_reviews(['published' => 1]);
        
        // Get latest reviews
        $latest_reviews = $this->db->get_reviews([
            'limit' => 5,
            'orderby' => 'generated_date',
            'order' => 'DESC',
        ]);
        
        include AI_REVIEW_GENERATOR_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Display reviews page
     *
     * @return void
     */
    public function display_reviews_page() {
        // Process actions
        $message = '';
        $error = '';
        
        if (isset($_POST['ai_review_action']) && isset($_POST['review_id']) && isset($_POST['_wpnonce'])) {
            $action = sanitize_text_field($_POST['ai_review_action']);
            $review_id = intval($_POST['review_id']);
            
            if (wp_verify_nonce($_POST['_wpnonce'], 'ai_review_' . $action . '_' . $review_id)) {
                if ($action === 'delete') {
                    // Delete review
                    $deleted = $this->db->delete_review($review_id);
                    
                    if ($deleted) {
                        $message = __('Review deleted successfully.', 'ai-review-generator');
                    } else {
                        $error = __('Error deleting review.', 'ai-review-generator');
                    }
                } elseif ($action === 'publish') {
                    // Publish review
                    $review = $this->db->get_review_by_id($review_id);
                    
                    if ($review) {
                        $updated = $this->review_generator->update_review($review_id, [
                            'published' => 1,
                        ]);
                        
                        if ($updated) {
                            $message = __('Review published successfully.', 'ai-review-generator');
                        } else {
                            $error = __('Error publishing review.', 'ai-review-generator');
                        }
                    } else {
                        $error = __('Review not found.', 'ai-review-generator');
                    }
                } elseif ($action === 'unpublish') {
                    // Unpublish review
                    $review = $this->db->get_review_by_id($review_id);
                    
                    if ($review) {
                        $updated = $this->review_generator->update_review($review_id, [
                            'published' => 0,
                        ]);
                        
                        if ($updated) {
                            $message = __('Review unpublished successfully.', 'ai-review-generator');
                        } else {
                            $error = __('Error unpublishing review.', 'ai-review-generator');
                        }
                    } else {
                        $error = __('Review not found.', 'ai-review-generator');
                    }
                }
            } else {
                $error = __('Invalid nonce.', 'ai-review-generator');
            }
        }
        
        // Get reviews with pagination
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $reviews = $this->db->get_reviews([
            'limit' => $per_page,
            'offset' => $offset,
            'orderby' => 'generated_date',
            'order' => 'DESC',
        ]);
        
        $total_reviews = $this->db->count_reviews();
        
        include AI_REVIEW_GENERATOR_PLUGIN_DIR . 'admin/partials/reviews.php';
    }

    /**
     * Display settings page
     *
     * @return void
     */
    public function display_settings_page() {
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        // Load settings
        $settings = get_option('ai_review_generator_settings', []);
        
        // Get available AI models
        $available_models = $this->ai_models->get_available_models();
        
        // Get the settings class instance
        $settings_instance = new AI_Review_Generator_Settings($this->plugin_name, $this->version);
        
        include AI_REVIEW_GENERATOR_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * Display logs page
     *
     * @return void
     */
    public function display_logs_page() {
        global $wpdb;
        
        // Get logs with pagination
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;
        
        $logs_table = $wpdb->prefix . 'ai_review_logs';
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $logs_table ORDER BY request_time DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );
        
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table");
        
        include AI_REVIEW_GENERATOR_PLUGIN_DIR . 'admin/partials/logs.php';
    }

    /**
     * Add meta boxes to post edit screen
     *
     * @return void
     */
    public function add_meta_boxes() {
        // Add meta box to posts
        add_meta_box(
            'ai_review_meta_box',
            __('AI Review Generator', 'ai-review-generator'),
            [$this, 'display_meta_box'],
            ['post', 'page'],
            'side',
            'high'
        );
        
        // Add meta box to WooCommerce products
        if (class_exists('WooCommerce')) {
            add_meta_box(
                'ai_review_meta_box',
                __('AI Review Generator', 'ai-review-generator'),
                [$this, 'display_meta_box'],
                'product',
                'side',
                'high'
            );
        }
    }

    /**
     * Display meta box
     *
     * @param WP_Post $post Post object
     * @return void
     */
    public function display_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('ai_review_meta_box', 'ai_review_meta_box_nonce');
        
        // Get current value
        $auto_generate = get_post_meta($post->ID, '_ai_review_auto_generate', true);
        
        // If not set, get default from settings
        if ($auto_generate === '') {
            $settings = get_option('ai_review_generator_settings', []);
            $auto_generate = isset($settings['auto_generate_default']) ? $settings['auto_generate_default'] : 'enabled';
        }
        
        // Check if review exists
        $review = $this->db->get_review_by_post_id($post->ID);
        
        include AI_REVIEW_GENERATOR_PLUGIN_DIR . 'admin/partials/meta-box.php';
    }

    /**
     * Save meta box data
     *
     * @param int $post_id Post ID
     * @return void
     */
    public function save_meta_box_data($post_id) {
        // Check if nonce is set
        if (!isset($_POST['ai_review_meta_box_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['ai_review_meta_box_nonce'], 'ai_review_meta_box')) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (isset($_POST['post_type'])) {
            if ($_POST['post_type'] === 'page') {
                if (!current_user_can('edit_page', $post_id)) {
                    return;
                }
            } else {
                if (!current_user_can('edit_post', $post_id)) {
                    return;
                }
            }
        }
        
        // Save auto-generate setting
        if (isset($_POST['ai_review_auto_generate'])) {
            $auto_generate = sanitize_text_field($_POST['ai_review_auto_generate']);
            update_post_meta($post_id, '_ai_review_auto_generate', $auto_generate);
        }
        
        // Save review count setting
        if (isset($_POST['ai_review_count'])) {
            $review_count = sanitize_text_field($_POST['ai_review_count']);
            if (!empty($review_count)) {
                $review_count = intval($review_count);
                $review_count = max(1, min(10, $review_count)); // Limit between 1-10
                update_post_meta($post_id, '_ai_review_count', $review_count);
            } else {
                delete_post_meta($post_id, '_ai_review_count');
            }
        }
    }

    /**
     * Ajax handler for generating reviews
     *
     * @return void
     */
    public function ajax_generate_review() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_review_generator_nonce')) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'ai-review-generator')]);
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('You do not have permission to do this.', 'ai-review-generator')]);
        }
        
        // Check post ID
        if (!isset($_POST['post_id'])) {
            wp_send_json_error(['message' => __('No post ID provided.', 'ai-review-generator')]);
        }
        
        $post_id = intval($_POST['post_id']);
        $force_regenerate = isset($_POST['force_regenerate']) && $_POST['force_regenerate'] === 'true';
        
        // Get settings
        $settings = get_option('ai_review_generator_settings', []);
        
        // Check if we should generate a reviewer name
        $generate_reviewer_name = isset($settings['enable_reviewer_names']) && $settings['enable_reviewer_names'] === 'yes';
        
        // Generate a reviewer name if enabled
        $reviewer_name = '';
        if ($generate_reviewer_name) {
            // You could implement a name generator here, for now we'll use a filter hook
            $reviewer_name = apply_filters('ai_review_generator_reviewer_name', '', $post_id);
        }
        
        // Check post type
        $post_type = get_post_type($post_id);
        
        if ($post_type === 'product' && class_exists('WooCommerce')) {
            // Generate product review
            $result = $this->review_generator->generate_product_review($post_id, $force_regenerate);
        } else {
            // Generate post review
            $result = $this->review_generator->generate_post_review($post_id, $force_regenerate);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            // Add reviewer name if needed
            if (!empty($reviewer_name) && empty($result['reviewer_name'])) {
                // Update the review with the reviewer name
                $this->review_generator->update_review($result['id'], [
                    'reviewer_name' => $reviewer_name
                ]);
                
                $result['reviewer_name'] = $reviewer_name;
            }
            
            wp_send_json_success([
                'message' => __('Review generated successfully.', 'ai-review-generator'),
                'review' => $result,
            ]);
        }
    }

    /**
     * Ajax handler for testing AI connection
     */
    public function ajax_test_ai_connection() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_review_generator_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'ai-review-generator')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to do this.', 'ai-review-generator')]);
        }
        
        // Check model ID
        if (!isset($_POST['model_id'])) {
            wp_send_json_error(['message' => __('No model ID provided.', 'ai-review-generator')]);
        }
        
        $model_id = sanitize_text_field($_POST['model_id']);
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $endpoint = isset($_POST['endpoint']) ? esc_url_raw($_POST['endpoint']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required.', 'ai-review-generator')]);
            return;
        }
        
        if (empty($endpoint)) {
            wp_send_json_error(['message' => __('Endpoint URL is required.', 'ai-review-generator')]);
            return;
        }
        
        // Log attempt for debugging
        error_log('Testing AI connection for model: ' . $model_id);
        
        // Validate input
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key cannot be empty', 'ai-review-generator')]);
            return;
        }
        
        if (empty($endpoint) || !filter_var($endpoint, FILTER_VALIDATE_URL)) {
            wp_send_json_error(['message' => __('Invalid endpoint URL', 'ai-review-generator')]);
            return;
        }
        
        // Temporarily save settings for testing
        $settings = get_option('ai_review_generator_settings', []);
        $old_settings = $settings;
        
        $settings[$model_id . '_api_key'] = $api_key;
        $settings[$model_id . '_endpoint'] = $endpoint;
        
        update_option('ai_review_generator_settings', $settings);
        
        // Test connection with error capturing
        try {
            $result = $this->ai_models->test_connection($model_id);
            
            // Always restore old settings
            update_option('ai_review_generator_settings', $old_settings);
            
            if (is_wp_error($result)) {
                $error_message = $result->get_error_message();
                error_log('AI connection test failed: ' . $error_message);
                wp_send_json_error(['message' => $error_message]);
            } else {
                wp_send_json_success(['message' => __('Connection successful!', 'ai-review-generator')]);
            }
        } catch (Exception $e) {
            // Restore settings even on exception
            update_option('ai_review_generator_settings', $old_settings);
            error_log('Exception in AI connection test: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}