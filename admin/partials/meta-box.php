<?php
/**
 * Meta box partial
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ai-review-meta-box-content">
    <div class="ai-review-meta-box-options">
        <p>
            <label for="ai_review_auto_generate"><?php _e('AI Review Generation:', 'ai-review-generator'); ?></label>
            <select name="ai_review_auto_generate" id="ai_review_auto_generate">
                <option value="enabled" <?php selected($auto_generate, 'enabled'); ?>><?php _e('Enabled', 'ai-review-generator'); ?></option>
                <option value="disabled" <?php selected($auto_generate, 'disabled'); ?>><?php _e('Disabled', 'ai-review-generator'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="ai_review_count"><?php _e('Number of Reviews:', 'ai-review-generator'); ?></label>
            <?php 
            $settings = get_option('ai_review_generator_settings', []);
            $default_count = isset($settings['reviews_per_post']) ? intval($settings['reviews_per_post']) : 1;
            $count = get_post_meta($post->ID, '_ai_review_count', true);
            $count = !empty($count) ? intval($count) : '';
            ?>
            <input type="number" name="ai_review_count" id="ai_review_count" value="<?php echo esc_attr($count); ?>" min="1" max="10" />
            <span class="description"><?php echo esc_html(sprintf(__('Leave empty for default (%d)', 'ai-review-generator'), $default_count)); ?></span>
        </p>
    </div>
    
    <div class="ai-review-meta-box-status">
        <?php 
        // Try to get reviews safely
        $reviews = [];
        try {
            if (method_exists($this->db, 'get_reviews_by_post_id')) {
                $reviews = $this->db->get_reviews_by_post_id($post->ID, ['published' => 1]);
            } else if (isset($this->db) && is_object($this->db) && method_exists($this->db, 'get_review_by_post_id')) {
                $review = $this->db->get_review_by_post_id($post->ID);
                if ($review && $review->published) {
                    $reviews = [$review];
                }
            }
        } catch (Exception $e) {
            // Silently fail and show no reviews
            $reviews = [];
        }
        
        $review_count = count($reviews);
        
        if ($review_count > 0) : 
            $first_review = $reviews[0];
        ?>
            <p>
                <strong><?php _e('Status:', 'ai-review-generator'); ?></strong>
                <?php if ($first_review->published) : ?>
                    <?php 
                    if ($review_count == 1) {
                        _e('Review published', 'ai-review-generator');
                    } else {
                        echo esc_html(sprintf(__('%d reviews published', 'ai-review-generator'), $review_count));
                    }
                    ?>
                <?php else : ?>
                    <?php _e('Review saved as draft', 'ai-review-generator'); ?>
                <?php endif; ?>
            </p>
            <p>
                <strong><?php _e('Generated on:', 'ai-review-generator'); ?></strong>
                <?php echo esc_html(mysql2date(get_option('date_format'), $first_review->generated_date)); ?>
            </p>
            <p>
                <strong><?php _e('AI Model:', 'ai-review-generator'); ?></strong>
                <?php echo esc_html($first_review->ai_model); ?>
            </p>
            <p>
                <strong><?php _e('Average Rating:', 'ai-review-generator'); ?></strong>
                <?php 
                // Calculate average rating
                $total_rating = 0;
                foreach ($reviews as $review) {
                    $total_rating += $review->rating;
                }
                $avg_rating = $review_count > 0 ? $total_rating / $review_count : 0;
                echo esc_html(number_format($avg_rating, 1));
                ?> / 5
            </p>
        <?php else : ?>
            <p><?php _e('No reviews generated yet.', 'ai-review-generator'); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="ai-review-meta-box-actions">
        <?php if ($review_count > 0) : ?>
            <button type="button" id="ai-review-regenerate-btn" class="button" data-post-id="<?php echo esc_attr($post->ID); ?>"><?php _e('Regenerate Reviews', 'ai-review-generator'); ?></button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ai-review-generator-reviews&post_id=' . $post->ID)); ?>" class="button" target="_blank"><?php _e('Manage Reviews', 'ai-review-generator'); ?></a>
        <?php else : ?>
            <button type="button" id="ai-review-generate-btn" class="button button-primary" data-post-id="<?php echo esc_attr($post->ID); ?>"><?php _e('Generate Reviews Now', 'ai-review-generator'); ?></button>
        <?php endif; ?>
    </div>
    
    <div id="ai-review-status"></div>
</div>