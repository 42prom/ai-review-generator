<?php
/**
 * Rating stars template.
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
 * @var float $rating Rating value
 * @var bool $show_number Whether to show numeric rating
 */

// Ensure rating is between 0 and 5
$rating = max(0, min(5, $rating));

// Round to nearest 0.5
$rating_rounded = round($rating * 2) / 2;

// Get colors from settings
$settings = get_option('ai_review_generator_settings', []);
$primary_color = isset($settings['star_color_primary']) ? $settings['star_color_primary'] : '#FFD700';
$secondary_color = isset($settings['star_color_secondary']) ? $settings['star_color_secondary'] : '#E0E0E0';
?>

<div class="ai-review-stars">
    <?php for ($i = 1; $i <= 5; $i++) : ?>
        <?php if ($i <= $rating_rounded) : ?>
            <span class="ai-review-star ai-review-star-full" style="color: <?php echo esc_attr($primary_color); ?>;">&#9733;</span>
        <?php elseif ($i - 0.5 === $rating_rounded) : ?>
            <span class="ai-review-star ai-review-star-half" style="color: <?php echo esc_attr($primary_color); ?>;">&#9733;</span>
        <?php else : ?>
            <span class="ai-review-star ai-review-star-empty" style="color: <?php echo esc_attr($secondary_color); ?>;">&#9734;</span>
        <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($show_number) : ?>
        <span class="ai-review-rating-number"><?php echo esc_html(number_format_i18n($rating, 1)); ?></span>
    <?php endif; ?>
</div>