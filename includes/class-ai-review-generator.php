<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 * @subpackage AI_Review_Generator/includes
 */

class AI_Review_Generator {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $loader    Maintains all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('AI_REVIEW_GENERATOR_VERSION')) {
            $this->version = AI_REVIEW_GENERATOR_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'ai-review-generator';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Load required files
        require_once AI_REVIEW_GENERATOR_PLUGIN_DIR . 'includes/class-database.php';
        require_once AI_REVIEW_GENERATOR_PLUGIN_DIR . 'includes/class-api.php';
        require_once AI_REVIEW_GENERATOR_PLUGIN_DIR . 'includes/class-ai-models.php';
        require_once AI_REVIEW_GENERATOR_PLUGIN_DIR . 'includes/class-review-generator.php';
        require_once AI_REVIEW_GENERATOR_PLUGIN_DIR . 'includes/class-review-display.php';
        
        require_once AI_REVIEW_GENERATOR_PLUGIN_DIR . 'admin/class-admin.php';
        require_once AI_REVIEW_GENERATOR_PLUGIN_DIR . 'admin/class-settings.php';
        
        require_once AI_REVIEW_GENERATOR_PLUGIN_DIR . 'public/class-public.php';
        
        // Initialize loader array
        $this->loader = [];
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        add_action('plugins_loaded', function() {
            load_plugin_textdomain(
                'ai-review-generator',
                false,
                dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
            );
        });
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new AI_Review_Generator_Admin($this->get_plugin_name(), $this->get_version());
        $plugin_settings = new AI_Review_Generator_Settings($this->get_plugin_name(), $this->get_version());
        
        // Admin hooks
        add_action('admin_enqueue_scripts', [$plugin_admin, 'enqueue_styles']);
        add_action('admin_enqueue_scripts', [$plugin_admin, 'enqueue_scripts']);
        add_action('admin_menu', [$plugin_admin, 'add_admin_menu']);
        
        // Post/Product edit hooks
        add_action('add_meta_boxes', [$plugin_admin, 'add_meta_boxes']);
        add_action('save_post', [$plugin_admin, 'save_meta_box_data']);
        
        // Settings hooks
        add_action('admin_init', [$plugin_settings, 'register_settings']);
        
        // Ajax hooks
        add_action('wp_ajax_generate_ai_review', [$plugin_admin, 'ajax_generate_review']);
        add_action('wp_ajax_test_ai_connection', [$plugin_admin, 'ajax_test_ai_connection']);
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new AI_Review_Generator_Public($this->get_plugin_name(), $this->get_version());
        
        // Public hooks
        add_action('wp_enqueue_scripts', [$plugin_public, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$plugin_public, 'enqueue_scripts']);
        
        // Display review hooks
        add_filter('the_content', [$plugin_public, 'display_review']);
        
        // WooCommerce product hooks
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_after_single_product_summary', [$plugin_public, 'display_product_review'], 15);
        }
        
        // Content creation hooks
        add_action('save_post', [$plugin_public, 'maybe_generate_review'], 20, 3);
        add_action('transition_post_status', [$plugin_public, 'check_post_transition'], 10, 3);
    }

    /**
     * Run the loader to execute all the hooks.
     *
     * @since    1.0.0
     */
    public function run() {
        // The loader is now an array of action/filter hooks that are added in the define_* methods
        // No need to iterate over it since we're directly using add_action/add_filter
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}