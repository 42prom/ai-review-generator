<?php
/**
 * Frontend review display.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 * @subpackage AI_Review_Generator/includes
 */

class AI_Review_Generator_Review_Display {

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
     * Plugin settings
     *
     * @var array
     */
    private $settings;

    /**
     * Initialize the class
     */
    public function __construct() {
        $this->db = new AI_Review_Generator_Database();
        $this->review_generator = new AI_Review_Generator_Review_Generator();
        $this->settings = get_option('ai_review_generator_settings', []);
    }

    /**
     * Display review in post content
     *
     * @param string $content Post content
     * @return string Modified content with review
     */
    public function display_in_content($content) {
        // Only add to main content on single post/page
        if (!is_singular()) {
            return $content;
        }
        
        global $post;
        
        // Skip if this is a product
        if (class_exists('WooCommerce') && 'product' === get_post_type()) {
            return $content;
        }
        
        // Get review position
        $position = isset($this->settings['review_position']) ? $this->settings['review_position'] : 'after';
        
        // Try to get reviews safely
        try {
            $reviews = $this->db->get_reviews_by_post_id($post->ID, ['published' => 1]);
        } catch (Exception $e) {
            // If anything goes wrong, just return the original content
            return $content;
        }
        
        if (empty($reviews)) {
            return $content;
        }
        
        // Format all reviews
        $reviews_html = '';
        foreach ($reviews as $review) {
            // Format review data
            $review_data = [
                'id' => $review->id,
                'post_id' => $review->post_id,
                'rating' => (float) $review->rating,
                'full_review' => $review->review_content,
                'summary' => $review->review_summary,
                'pros' => !empty($review->review_pros) ? explode("\n", $review->review_pros) : [],
                'cons' => !empty($review->review_cons) ? explode("\n", $review->review_cons) : [],
                'reviewer_name' => isset($review->reviewer_name) ? $review->reviewer_name : '',
                'generated_date' => $review->generated_date,
                'ai_model' => $review->ai_model,
            ];
            
            // Generate HTML
            try {
                $reviews_html .= $this->review_generator->format_review_content($review_data);
            } catch (Exception $e) {
                // Skip this review if there's an error
                continue;
            }
        }
        
        if (empty($reviews_html)) {
            return $content;
        }
        
        // Add to content based on position
        if ($position === 'before') {
            return $reviews_html . $content;
        } elseif ($position === 'after') {
            return $content . $reviews_html;
        } else {
            // Try to insert after first paragraph
            $parts = preg_split('/<\/p>/', $content, 2, PREG_SPLIT_DELIM_CAPTURE);
            
            if (count($parts) >= 2) {
                return $parts[0] . '</p>' . $reviews_html . $parts[1];
            } else {
                return $content . $reviews_html;
            }
        }
    }

    /**
     * Display review for WooCommerce product
     *
     * @return void
     */
    public function display_product_review() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $product_id = $product->get_id();
        
        // Get all reviews for this product
        $reviews = $this->db->get_reviews_by_post_id($product_id, ['published' => 1]);
        
        if (empty($reviews)) {
            return;
        }
        
        // Display all reviews
        foreach ($reviews as $review) {
            // Format review data
            $review_data = [
                'id' => $review->id,
                'post_id' => $review->post_id,
                'rating' => (float) $review->rating,
                'full_review' => $review->review_content,
                'summary' => $review->review_summary,
                'pros' => !empty($review->review_pros) ? explode("\n", $review->review_pros) : [],
                'cons' => !empty($review->review_cons) ? explode("\n", $review->review_cons) : [],
                'reviewer_name' => $review->reviewer_name,
                'generated_date' => $review->generated_date,
                'ai_model' => $review->ai_model,
            ];
            
            // Generate HTML
            $review_html = $this->review_generator->format_review_content($review_data, true);
            
            echo $review_html;
        }
    }

    /**
     * Get star rating HTML
     *
     * @param float $rating Rating
     * @param bool $show_number Whether to show numeric rating
     * @return string Star rating HTML
     */
    public function get_star_rating($rating, $show_number = true) {
        return $this->review_generator->format_star_rating($rating, $show_number);
    }

    /**
     * Render review box template
     *
     * @param array $review Review data
     * @param bool $is_product Whether this is a product review
     * @return string Rendered template
     */
    public function render_review_template($review, $is_product = false) {
        ob_start();
        include AI_REVIEW_GENERATOR_PLUGIN_DIR . 'templates/review-box.php';
        return ob_get_clean();
    }

    /**
     * Check if review exists for post
     *
     * @param int $post_id Post ID
     * @return bool True if review exists
     */
    public function has_review($post_id) {
        $review = $this->db->get_review_by_post_id($post_id);
        return !empty($review) && $review->published;
    }

    /**
     * Get review data for post
     *
     * @param int $post_id Post ID
     * @return array|false Review data or false if not found
     */
    public function get_review_data($post_id) {
        $review = $this->db->get_review_by_post_id($post_id);
        
        if (!$review || !$review->published) {
            return false;
        }
        
        $review_data = [
            'id' => $review->id,
            'post_id' => $review->post_id,
            'rating' => (float) $review->rating,
            'full_review' => $review->review_content,
            'summary' => $review->review_summary,
            'pros' => !empty($review->review_pros) ? explode("\n", $review->review_pros) : [],
            'cons' => !empty($review->review_cons) ? explode("\n", $review->review_cons) : [],
            'reviewer_name' => $review->reviewer_name,
            'generated_date' => $review->generated_date,
            'ai_model' => $review->ai_model,
            'modified_by_user' => (bool) $review->modified_by_user,
            'published' => (bool) $review->published,
        ];
        
        // Allow filtering the review data
        return apply_filters('ai_review_generator_review_data', $review_data, $review, $post_id);
    }
}