<?php
/**
 * Review generation functionality.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 * @subpackage AI_Review_Generator/includes
 */

class AI_Review_Generator_Review_Generator {

    /**
     * AI Models instance
     *
     * @var AI_Review_Generator_AI_Models
     */
    private $ai_models;
    
    /**
     * Database instance
     *
     * @var AI_Review_Generator_Database
     */
    private $db;
    
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
        $this->ai_models = new AI_Review_Generator_AI_Models();
        $this->db = new AI_Review_Generator_Database();
        $this->settings = get_option('ai_review_generator_settings', []);
    }

    /**
     * Generate review for a post
     *
     * @param int $post_id Post ID
     * @param bool $force_regenerate Whether to force regeneration of existing review
     * @return array|WP_Error Review data or error
     */
    public function generate_post_review($post_id, $force_regenerate = false) {
        // Get post
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('invalid_post', __('Invalid post ID', 'ai-review-generator'));
        }
        
        // Check if post is published
        if ($post->post_status !== 'publish' && !$force_regenerate) {
            return new WP_Error('post_not_published', __('Post is not published', 'ai-review-generator'));
        }
        
        // Check if auto-generation is enabled for this post
        $auto_generate = get_post_meta($post_id, '_ai_review_auto_generate', true);
        
        if ($auto_generate === 'disabled' && !$force_regenerate) {
            return new WP_Error('auto_generate_disabled', __('Auto-generation is disabled for this post', 'ai-review-generator'));
        }
        
        // Get the number of reviews to generate
        $settings = get_option('ai_review_generator_settings', []);
        $default_count = isset($settings['reviews_per_post']) ? intval($settings['reviews_per_post']) : 1;
        
        // Check for post-specific override
        $post_count = get_post_meta($post_id, '_ai_review_count', true);
        $count = !empty($post_count) ? intval($post_count) : $default_count;
        $count = max(1, min(10, $count)); // Limit between 1-10
        
        // Check if reviews already exist
        $existing_reviews = $this->db->get_reviews_by_post_id($post_id);
        
        if (!empty($existing_reviews) && !$force_regenerate) {
            // If we already have enough reviews, return the first one
            if (count($existing_reviews) >= $count) {
                return $this->format_review_data($existing_reviews[0]);
            }
            
            // Otherwise, we'll generate more to reach the desired count
            $reviews_to_generate = $count - count($existing_reviews);
        } else {
            // If force regenerate, delete existing reviews
            if ($force_regenerate && !empty($existing_reviews)) {
                foreach ($existing_reviews as $review) {
                    $this->db->delete_review($review->id);
                }
            }
            
            $reviews_to_generate = $count;
            $existing_reviews = [];
        }
        
        $generated_reviews = [];
        
        // Generate the required number of reviews
        for ($i = 0; $i < $reviews_to_generate; $i++) {
            // Prepare content data for AI
            $content_data = $this->prepare_post_content_data($post);
            
            // Generate review using AI
            $review_data = $this->ai_models->generate_review($content_data);
            
            if (is_wp_error($review_data)) {
                continue; // Skip this one if there's an error
            }
            
            // Generate reviewer name if enabled
            $reviewer_name = '';
            if (isset($settings['enable_reviewer_names']) && $settings['enable_reviewer_names'] === 'yes') {
                $reviewer_name = apply_filters('ai_review_generator_reviewer_name', '', $post_id, $i);
            }
            
            // Prepare save data
            $save_data = [
                'post_id' => $post_id,
                'rating' => $review_data['rating'],
                'review_content' => $review_data['full_review'],
                'review_summary' => $review_data['summary'],
                'review_pros' => !empty($review_data['pros']) ? implode("\n", $review_data['pros']) : '',
                'review_cons' => !empty($review_data['cons']) ? implode("\n", $review_data['cons']) : '',
                'reviewer_name' => $reviewer_name,
                'ai_model' => $this->ai_models->get_current_model(),
                'modified_by_user' => 0,
                'published' => 1,
            ];
            
            // Allow filtering the save data
            $save_data = apply_filters('ai_review_generator_before_save', $save_data, $review_data, $post_id);
            
            // Save review to database
            $review_id = $this->db->save_review($save_data);
            
            // Fire action after saving
            do_action('ai_review_generator_after_save', $review_id, $save_data, $post_id);
            
            if ($review_id) {
                $saved_review = $this->db->get_review_by_id($review_id);
                if ($saved_review) {
                    $generated_reviews[] = $saved_review;
                }
            }
        }
        
        // If we have any reviews at all (existing or newly generated), return the first one
        $all_reviews = array_merge($existing_reviews, $generated_reviews);
        
        if (!empty($all_reviews)) {
            return $this->format_review_data($all_reviews[0]);
        }
        
        return new WP_Error('generation_failed', __('Failed to generate any reviews', 'ai-review-generator'));
    }

    /**
     * Generate review for a WooCommerce product
     *
     * @param int $product_id Product ID
     * @param bool $force_regenerate Whether to force regeneration of existing review
     * @return array|WP_Error Review data or error
     */
    public function generate_product_review($product_id, $force_regenerate = false) {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woocommerce_inactive', __('WooCommerce is not active', 'ai-review-generator'));
        }
        
        // Get product
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return new WP_Error('invalid_product', __('Invalid product ID', 'ai-review-generator'));
        }
        
        // Check if auto-generation is enabled for this product
        $auto_generate = get_post_meta($product_id, '_ai_review_auto_generate', true);
        
        if ($auto_generate === 'disabled' && !$force_regenerate) {
            return new WP_Error('auto_generate_disabled', __('Auto-generation is disabled for this product', 'ai-review-generator'));
        }
        
        // Get the number of reviews to generate
        $settings = get_option('ai_review_generator_settings', []);
        $default_count = isset($settings['reviews_per_post']) ? intval($settings['reviews_per_post']) : 1;
        
        // Check for product-specific override
        $product_count = get_post_meta($product_id, '_ai_review_count', true);
        $count = !empty($product_count) ? intval($product_count) : $default_count;
        $count = max(1, min(10, $count)); // Limit between 1-10
        
        // Check if reviews already exist
        $existing_reviews = $this->db->get_reviews_by_post_id($product_id);
        
        if (!empty($existing_reviews) && !$force_regenerate) {
            // If we already have enough reviews, return the first one
            if (count($existing_reviews) >= $count) {
                return $this->format_review_data($existing_reviews[0]);
            }
            
            // Otherwise, we'll generate more to reach the desired count
            $reviews_to_generate = $count - count($existing_reviews);
        } else {
            // If force regenerate, delete existing reviews
            if ($force_regenerate && !empty($existing_reviews)) {
                foreach ($existing_reviews as $review) {
                    $this->db->delete_review($review->id);
                }
            }
            
            $reviews_to_generate = $count;
            $existing_reviews = [];
        }
        
        $generated_reviews = [];
        
        // Generate the required number of reviews
        for ($i = 0; $i < $reviews_to_generate; $i++) {
            // Prepare content data for AI
            $content_data = $this->prepare_product_content_data($product);
            
            // Generate review using AI
            $review_data = $this->ai_models->generate_review($content_data);
            
            if (is_wp_error($review_data)) {
                continue; // Skip this one if there's an error
            }
            
            // Generate reviewer name if enabled
            $reviewer_name = '';
            if (isset($settings['enable_reviewer_names']) && $settings['enable_reviewer_names'] === 'yes') {
                $reviewer_name = apply_filters('ai_review_generator_reviewer_name', '', $product_id, $i);
            }
            
            // Prepare save data
            $save_data = [
                'post_id' => $product_id,
                'rating' => $review_data['rating'],
                'review_content' => $review_data['full_review'],
                'review_summary' => $review_data['summary'],
                'review_pros' => !empty($review_data['pros']) ? implode("\n", $review_data['pros']) : '',
                'review_cons' => !empty($review_data['cons']) ? implode("\n", $review_data['cons']) : '',
                'reviewer_name' => $reviewer_name,
                'ai_model' => $this->ai_models->get_current_model(),
                'modified_by_user' => 0,
                'published' => 1,
            ];
            
            // Allow filtering the save data
            $save_data = apply_filters('ai_review_generator_before_save', $save_data, $review_data, $product_id);
            
            // Save review to database
            $review_id = $this->db->save_review($save_data);
            
            // Fire action after saving
            do_action('ai_review_generator_after_save', $review_id, $save_data, $product_id);
            
            if ($review_id) {
                $saved_review = $this->db->get_review_by_id($review_id);
                if ($saved_review) {
                    $generated_reviews[] = $saved_review;
                }
            }
        }
        
        // If we have any reviews at all (existing or newly generated), return the first one
        $all_reviews = array_merge($existing_reviews, $generated_reviews);
        
        if (!empty($all_reviews)) {
            return $this->format_review_data($all_reviews[0]);
        }
        
        return new WP_Error('generation_failed', __('Failed to generate any reviews', 'ai-review-generator'));
    }

    /**
     * Prepare post content data for AI
     *
     * @param WP_Post $post Post object
     * @return array Post content data
     */
    private function prepare_post_content_data($post) {
        $content_data = [
            'post_id' => $post->ID,
            'title' => $post->post_title,
            'content' => wp_strip_all_tags($post->post_content),
            'excerpt' => $post->post_excerpt,
            'is_product' => false,
        ];
        
        // Truncate content if it's too long
        if (strlen($content_data['content']) > 5000) {
            $content_data['content'] = substr($content_data['content'], 0, 5000) . '...';
        }
        
        // Add categories
        $categories = get_the_category($post->ID);
        $category_names = [];
        
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $category_names[] = $category->name;
            }
        }
        
        $content_data['categories'] = $category_names;
        
        // Add tags
        $tags = get_the_tags($post->ID);
        $tag_names = [];
        
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $tag_names[] = $tag->name;
            }
        }
        
        $content_data['tags'] = $tag_names;
        
        return $content_data;
    }

    /**
     * Prepare product content data for AI
     *
     * @param WC_Product $product Product object
     * @return array Product content data
     */
    private function prepare_product_content_data($product) {
        $content_data = [
            'post_id' => $product->get_id(),
            'title' => $product->get_name(),
            'content' => wp_strip_all_tags($product->get_description()),
            'excerpt' => $product->get_short_description(),
            'is_product' => true,
            'price' => $product->get_price_html(),
        ];
        
        // Truncate content if it's too long
        if (strlen($content_data['content']) > 5000) {
            $content_data['content'] = substr($content_data['content'], 0, 5000) . '...';
        }
        
        // Add categories
        $categories = wc_get_product_category_list($product->get_id());
        $content_data['categories'] = !empty($categories) ? wp_strip_all_tags($categories) : '';
        
        // Add attributes
        $attributes = $product->get_attributes();
        $attribute_data = [];
        
        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                if ($attribute->is_taxonomy()) {
                    $terms = wp_get_post_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
                    if (!empty($terms)) {
                        $attribute_data[$attribute->get_name()] = implode(', ', $terms);
                    }
                } else {
                    $options = $attribute->get_options();
                    if (!empty($options)) {
                        $attribute_data[$attribute->get_name()] = implode(', ', $options);
                    }
                }
            }
        }
        
        $content_data['attributes'] = $attribute_data;
        
        return $content_data;
    }

    /**
     * Format review data from database object
     *
     * @param object $review_obj Review database object
     * @return array Formatted review data
     */
    private function format_review_data($review_obj) {
        $pros = !empty($review_obj->review_pros) ? explode("\n", $review_obj->review_pros) : [];
        $cons = !empty($review_obj->review_cons) ? explode("\n", $review_obj->review_cons) : [];
        
        return [
            'id' => $review_obj->id,
            'post_id' => $review_obj->post_id,
            'rating' => (float) $review_obj->rating,
            'full_review' => $review_obj->review_content,
            'summary' => $review_obj->review_summary,
            'pros' => $pros,
            'cons' => $cons,
            'generated_date' => $review_obj->generated_date,
            'ai_model' => $review_obj->ai_model,
            'modified_by_user' => (bool) $review_obj->modified_by_user,
            'published' => (bool) $review_obj->published,
        ];
    }

    /**
     * Format star rating HTML
     *
     * @param float $rating Rating value
     * @param bool $show_number Whether to show the numeric rating
     * @return string Formatted star rating HTML
     */
    public function format_star_rating($rating, $show_number = true) {
        // Ensure rating is between 0 and 5
        $rating = max(0, min(5, $rating));
        
        // Round to nearest 0.5
        $rating_rounded = round($rating * 2) / 2;
        
        // Get primary and secondary colors from settings
        $primary_color = isset($this->settings['star_color_primary']) ? $this->settings['star_color_primary'] : '#FFD700';
        $secondary_color = isset($this->settings['star_color_secondary']) ? $this->settings['star_color_secondary'] : '#E0E0E0';
        
        // Generate star HTML
        $stars_html = '<div class="ai-review-stars">';
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating_rounded) {
                // Full star
                $stars_html .= '<span class="ai-review-star ai-review-star-full" style="color: ' . esc_attr($primary_color) . ';">&#9733;</span>';
            } elseif ($i - 0.5 === $rating_rounded) {
                // Half star
                $stars_html .= '<span class="ai-review-star ai-review-star-half" style="color: ' . esc_attr($primary_color) . ';">&#9733;</span>';
            } else {
                // Empty star
                $stars_html .= '<span class="ai-review-star ai-review-star-empty" style="color: ' . esc_attr($secondary_color) . ';">&#9734;</span>';
            }
        }
        
        if ($show_number) {
            $stars_html .= '<span class="ai-review-rating-number">' . number_format_i18n($rating, 1) . '</span>';
        }
        
        $stars_html .= '</div>';
        
        return $stars_html;
    }

    /**
     * Format review content with schema markup
     *
     * @param array $review Review data
     * @param bool $is_product Whether this is a product review
     * @return string Formatted review HTML with schema markup
     */
    public function format_review_content($review, $is_product = false) {
        $output = '';
        
        // Load template
        ob_start();
        include AI_REVIEW_GENERATOR_PLUGIN_DIR . 'templates/review-box.php';
        $output = ob_get_clean();
        
        // Generate schema markup
        $schema_markup = $this->generate_schema_markup($review, $is_product);
        
        // Allow filtering the final output
        $final_output = $output . $schema_markup;
        $final_output = apply_filters('ai_review_generator_review_output', $final_output, $review, $is_product);
        
        return $final_output;
    }

    /**
     * Generate schema markup for review
     *
     * @param array $review Review data
     * @param bool $is_product Whether this is a product review
     * @return string Schema markup HTML
     */
    private function generate_schema_markup($review, $is_product = false) {
        $post = get_post($review['post_id']);
        
        if (!$post) {
            return '';
        }
        
        $schema = [
            '@context' => 'https://schema.org',
        ];
        
        if ($is_product) {
            $product = wc_get_product($post->ID);
            
            if (!$product) {
                return '';
            }
            
            $schema['@type'] = 'Product';
            $schema['name'] = $product->get_name();
            $schema['description'] = $product->get_short_description();
            
            // Add image if available
            $image_id = $product->get_image_id();
            if ($image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'full');
                if ($image_url) {
                    $schema['image'] = $image_url;
                }
            }
            
            // Add offers
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => $product->get_price(),
                'priceCurrency' => get_woocommerce_currency(),
                'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => get_permalink($product->get_id()),
            ];
            
            // Allow filtering product schema
            $schema = apply_filters('ai_review_generator_product_schema', $schema, $product, $review);
            
        } else {
            $schema['@type'] = 'Article';
            $schema['headline'] = $post->post_title;
            $schema['description'] = !empty($post->post_excerpt) ? $post->post_excerpt : wp_trim_words($post->post_content, 55);
            $schema['url'] = get_permalink($post->ID);
            $schema['datePublished'] = get_the_date('c', $post->ID);
            $schema['dateModified'] = get_the_modified_date('c', $post->ID);
            
            // Add author
            $author = get_the_author_meta('display_name', $post->post_author);
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $author,
            ];
            
            // Add featured image if available
            if (has_post_thumbnail($post->ID)) {
                $image_url = get_the_post_thumbnail_url($post->ID, 'full');
                if ($image_url) {
                    $schema['image'] = $image_url;
                }
            }
            
            // Allow filtering article schema
            $schema = apply_filters('ai_review_generator_article_schema', $schema, $post, $review);
        }
        
        // Create review schema
        $review_schema = [
            '@type' => 'Review',
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $review['rating'],
                'bestRating' => '5',
            ],
            'reviewBody' => $review['full_review'],
        ];
        
        // Add reviewer name if present
        if (!empty($review['reviewer_name'])) {
            $review_schema['author'] = [
                '@type' => 'Person',
                'name' => $review['reviewer_name'],
            ];
        } else {
            $review_schema['author'] = [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
            ];
        }
        
        // Allow filtering the review schema
        $review_schema = apply_filters('ai_review_generator_review_schema', $review_schema, $review, $is_product);
        
        // Add review to schema
        $schema['review'] = $review_schema;
        
        // Generate HTML
        $schema_html = '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
        
        return $schema_html;
    }

    /**
     * Check if auto-generation is enabled for a post
     *
     * @param int $post_id Post ID
     * @return bool True if auto-generation is enabled
     */
    public function is_auto_generate_enabled($post_id) {
        $auto_generate = get_post_meta($post_id, '_ai_review_auto_generate', true);
        
        // If not explicitly set, check global setting
        if ($auto_generate === '') {
            $global_auto_generate = isset($this->settings['auto_generate_default']) ? $this->settings['auto_generate_default'] : 'enabled';
            return $global_auto_generate === 'enabled';
        }
        
        return $auto_generate === 'enabled';
    }

    /**
     * Update review data
     *
     * @param int $review_id Review ID
     * @param array $review_data Review data to update
     * @return bool True on success, false on failure
     */
    public function update_review($review_id, $review_data) {
        // Get existing review
        $existing_review = $this->db->get_review_by_id($review_id);
        
        if (!$existing_review) {
            return false;
        }
        
        // Prepare data for saving
        $save_data = [
            'post_id' => $existing_review->post_id,
            'rating' => isset($review_data['rating']) ? $review_data['rating'] : $existing_review->rating,
            'review_content' => isset($review_data['full_review']) ? $review_data['full_review'] : $existing_review->review_content,
            'review_summary' => isset($review_data['summary']) ? $review_data['summary'] : $existing_review->review_summary,
            'review_pros' => isset($review_data['pros']) ? (is_array($review_data['pros']) ? implode("\n", $review_data['pros']) : $review_data['pros']) : $existing_review->review_pros,
            'review_cons' => isset($review_data['cons']) ? (is_array($review_data['cons']) ? implode("\n", $review_data['cons']) : $review_data['cons']) : $existing_review->review_cons,
            'ai_model' => $existing_review->ai_model,
            'modified_by_user' => 1, // Mark as modified by user
            'published' => isset($review_data['published']) ? (int) $review_data['published'] : $existing_review->published,
        ];
        
        // Save to database
        $updated = $this->db->save_review($save_data);
        
        return $updated !== false;
    }
}