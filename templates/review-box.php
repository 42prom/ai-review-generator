<?php
/**
 * Review box template.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Available variables:
 *
 * @var array $review Review data
 * @var bool $is_product Whether this is a product review
 */

// Get settings
$settings = get_option('ai_review_generator_settings', []);
$display_style = isset($settings['review_display_style']) ? $settings['review_display_style'] : 'boxed';

// Get post title
$post = get_post($review['post_id']);
$post_title = $post ? $post->post_title : '';

// Format rating
$rating_html = $this->get_star_rating($review['rating']);

// Format date
$date = mysql2date(get_option('date_format'), $review['generated_date']);

// CSS classes
$box_classes = [
    'ai-review-box',
    $display_style,
];
?>

<div id="ai-review-<?php echo esc_attr($review['post_id']); ?>" class="<?php echo esc_attr(implode(' ', $box_classes)); ?>">
    <div class="ai-review-header">
        <h3 class="ai-review-box-title">
            <?php if ($is_product) : ?>
                <?php echo esc_html(sprintf(__('Our Review of %s', 'ai-review-generator'), $post_title)); ?>
            <?php else : ?>
                <?php echo esc_html(sprintf(__('Review: %s', 'ai-review-generator'), $post_title)); ?>
            <?php endif; ?>
        </h3>
        
        <div class="ai-review-rating">
            <?php echo $rating_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </div>
    
    <div class="ai-review-summary">
        <h4><?php _e('Summary', 'ai-review-generator'); ?></h4>
        <p><?php echo esc_html($review['summary']); ?></p>
    </div>
    
    <?php if (!empty($review['pros'])) : ?>
        <div class="ai-review-pros">
            <h4><?php _e('Pros', 'ai-review-generator'); ?></h4>
            <ul>
                <?php foreach ($review['pros'] as $pro) : ?>
                    <li><?php echo esc_html($pro); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($review['cons'])) : ?>
        <div class="ai-review-cons">
            <h4><?php _e('Cons', 'ai-review-generator'); ?></h4>
            <ul>
                <?php foreach ($review['cons'] as $con) : ?>
                    <li><?php echo esc_html($con); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="ai-review-content">
        <?php echo wp_kses_post($review['full_review']); ?>
    </div>
    
    <div class="ai-review-footer">
        <div class="ai-review-meta">
            <?php if (!empty($review['reviewer_name'])) : ?>
                <div class="ai-review-author">
                    <?php echo esc_html(sprintf(__('Review by %s', 'ai-review-generator'), $review['reviewer_name'])); ?>
                </div>
            <?php endif; ?>
            
            <div class="ai-review-date">
                <?php echo esc_html(sprintf(__('Review generated on %s', 'ai-review-generator'), $date)); ?>
            </div>
        </div>
        
        <?php if (current_user_can('edit_posts')) : ?>
            <div class="ai-review-admin-links">
                <a href="<?php echo esc_url(admin_url('admin.php?page=ai-review-generator-reviews&post_id=' . $review['post_id'])); ?>" target="_blank">
                    <?php _e('Edit Review', 'ai-review-generator'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>