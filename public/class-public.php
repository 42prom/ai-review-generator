<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 * @subpackage AI_Review_Generator/public
 */

class AI_Review_Generator_Public {

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
     * Review Generator instance
     *
     * @var AI_Review_Generator_Review_Generator
     */
    private $review_generator;
    
    /**
     * Review Display instance
     *
     * @var AI_Review_Generator_Review_Display
     */
    private $review_display;

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
        
        require_once AI_REVIEW_GENERATOR_PLUGIN_DIR . 'includes/class-review-generator.php';
        require_once AI_REVIEW_GENERATOR_PLUGIN_DIR . 'includes/class-review-display.php';
        
        $this->review_generator = new AI_Review_Generator_Review_Generator();
        $this->review_display = new AI_Review_Generator_Review_Display();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/public.css', [], $this->version, 'all');
        
        // Add inline styles from settings
        $this->add_dynamic_styles();
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/public.js', ['jquery'], $this->version, false);
    }

    /**
     * Add dynamic CSS based on settings
     *
     * @return void
     */
    private function add_dynamic_styles() {
        $settings = get_option('ai_review_generator_settings', []);
        
        // Default styles
        $box_bg_color = isset($settings['box_bg_color']) ? $settings['box_bg_color'] : '#f9f9f9';
        $box_border_color = isset($settings['box_border_color']) ? $settings['box_border_color'] : '#e0e0e0';
        $box_border_width = isset($settings['box_border_width']) ? $settings['box_border_width'] : '1px';
        $box_border_radius = isset($settings['box_border_radius']) ? $settings['box_border_radius'] : '5px';
        $box_padding = isset($settings['box_padding']) ? $settings['box_padding'] : '20px';
        $box_margin = isset($settings['box_margin']) ? $settings['box_margin'] : '20px 0';
        $title_color = isset($settings['title_color']) ? $settings['title_color'] : '#333333';
        $text_color = isset($settings['text_color']) ? $settings['text_color'] : '#666666';
        $star_color_primary = isset($settings['star_color_primary']) ? $settings['star_color_primary'] : '#FFD700';
        $star_color_secondary = isset($settings['star_color_secondary']) ? $settings['star_color_secondary'] : '#E0E0E0';
        
        // Dark mode styles
        $enable_dark_mode = isset($settings['enable_dark_mode']) ? $settings['enable_dark_mode'] : 'yes';
        $dark_box_bg_color = isset($settings['dark_box_bg_color']) ? $settings['dark_box_bg_color'] : '#2d2d2d';
        $dark_box_border_color = isset($settings['dark_box_border_color']) ? $settings['dark_box_border_color'] : '#4d4d4d';
        $dark_title_color = isset($settings['dark_title_color']) ? $settings['dark_title_color'] : '#ffffff';
        $dark_text_color = isset($settings['dark_text_color']) ? $settings['dark_text_color'] : '#cccccc';
        
        // Generate CSS
        $css = "
        .ai-review-box {
            background-color: {$box_bg_color};
            border: {$box_border_width} solid {$box_border_color};
            border-radius: {$box_border_radius};
            padding: {$box_padding};
            margin: {$box_margin};
            color: {$text_color};
        }
        
        .ai-review-box-title {
            color: {$title_color};
        }
        
        .ai-review-star-full {
            color: {$star_color_primary};
        }
        
        .ai-review-star-empty {
            color: {$star_color_secondary};
        }
        
        /* Display style variations */
        .ai-review-box.minimal {
            background-color: transparent;
            border: none;
            padding: 0;
        }
        
        .ai-review-box.card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border: none;
        }
        ";
        
        // Add dark mode styles if enabled
        if ($enable_dark_mode === 'yes') {
            $css .= "
            @media (prefers-color-scheme: dark) {
                .ai-review-box {
                    background-color: {$dark_box_bg_color};
                    border-color: {$dark_box_border_color};
                    color: {$dark_text_color};
                }
                
                .ai-review-box-title {
                    color: {$dark_title_color};
                }
                
                .ai-review-box.minimal {
                    background-color: transparent;
                }
            }
            ";
        }
        
        wp_add_inline_style($this->plugin_name, $css);
    }

    /**
     * Display review in post content
     *
     * @param string $content Post content
     * @return string Modified content with review
     */
    public function display_review($content) {
        return $this->review_display->display_in_content($content);
    }

    /**
     * Display review for a WooCommerce product
     *
     * @return void
     */
    public function display_product_review() {
        $this->review_display->display_product_review();
    }

    /**
     * Maybe generate review for post when published
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an update
     * @return void
     */
    public function maybe_generate_review($post_id, $post, $update) {
        // Skip revisions and auto-saves
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        // Only process published posts
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Skip if this is a WooCommerce product (handled separately)
        if ($post->post_type === 'product' && class_exists('WooCommerce')) {
            return;
        }
        
        // Check if auto-generation is enabled for this post
        if (!$this->review_generator->is_auto_generate_enabled($post_id)) {
            return;
        }
        
        // Check if review already exists
        $existing_review = $this->review_display->get_review_data($post_id);
        
        if ($existing_review) {
            return;
        }
        
        // Generate review
        $this->review_generator->generate_post_review($post_id);
    }

    /**
     * Check post transition to publish
     *
     * @param string $new_status New post status
     * @param string $old_status Old post status
     * @param WP_Post $post Post object
     * @return void
     */
    public function check_post_transition($new_status, $old_status, $post) {
        // Only process transitions to publish
        if ($new_status !== 'publish') {
            return;
        }
        
        // Skip if already published
        if ($old_status === 'publish') {
            return;
        }
        
        // Generate review on transition to publish
        $this->maybe_generate_review($post->ID, $post, false);
    }
}