<?php
/**
 * Dashboard page partial
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ai-review-dashboard">
    <h1><?php _e('AI Review Generator Dashboard', 'ai-review-generator'); ?></h1>
    
    <div class="ai-review-stats-grid">
        <div class="ai-review-stat-card">
            <p class="ai-review-stat-title"><?php _e('Total Reviews', 'ai-review-generator'); ?></p>
            <div class="ai-review-stat-value"><?php echo esc_html($total_reviews); ?></div>
        </div>
        
        <div class="ai-review-stat-card">
            <p class="ai-review-stat-title"><?php _e('Published Reviews', 'ai-review-generator'); ?></p>
            <div class="ai-review-stat-value"><?php echo esc_html($published_reviews); ?></div>
        </div>
        
        <div class="ai-review-stat-card">
            <p class="ai-review-stat-title"><?php _e('Current AI Model', 'ai-review-generator'); ?></p>
            <div class="ai-review-stat-value" style="font-size: 24px;">
                <?php
                $settings = get_option('ai_review_generator_settings', []);
                $current_model = isset($settings['ai_model']) ? $settings['ai_model'] : 'deepseek';
                $models = $this->ai_models->get_available_models();
                echo esc_html($models[$current_model]['name']);
                ?>
            </div>
        </div>
    </div>
    
    <div class="ai-review-latest">
        <h2><?php _e('Latest Reviews', 'ai-review-generator'); ?></h2>
        
        <?php if (empty($latest_reviews)) : ?>
            <p><?php _e('No reviews generated yet.', 'ai-review-generator'); ?></p>
        <?php else : ?>
            <table>
                <thead>
                    <tr>
                        <th><?php _e('Title', 'ai-review-generator'); ?></th>
                        <th><?php _e('Rating', 'ai-review-generator'); ?></th>
                        <th><?php _e('Date', 'ai-review-generator'); ?></th>
                        <th><?php _e('AI Model', 'ai-review-generator'); ?></th>
                        <th><?php _e('Status', 'ai-review-generator'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latest_reviews as $review) : 
                        $post = get_post($review->post_id);
                        if (!$post) continue;
                        
                        $post_title = $post->post_title;
                        $post_edit_link = get_edit_post_link($review->post_id);
                        $review_date = mysql2date(get_option('date_format'), $review->generated_date);
                        $status = $review->published ? __('Published', 'ai-review-generator') : __('Draft', 'ai-review-generator');
                    ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($post_edit_link); ?>"><?php echo esc_html($post_title); ?></a>
                            </td>
                            <td>
                                <?php echo esc_html(number_format($review->rating, 1)); ?> / 5
                            </td>
                            <td>
                                <?php echo esc_html($review_date); ?>
                            </td>
                            <td>
                                <?php echo esc_html($review->ai_model); ?>
                            </td>
                            <td>
                                <?php echo esc_html($status); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ai-review-generator-reviews')); ?>" class="button"><?php _e('View All Reviews', 'ai-review-generator'); ?></a>
        </p>
    </div>
    
    <div class="ai-review-latest">
        <h2><?php _e('Quick Actions', 'ai-review-generator'); ?></h2>
        
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ai-review-generator-settings')); ?>" class="button"><?php _e('Configure Settings', 'ai-review-generator'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ai-review-generator-logs')); ?>" class="button"><?php _e('View API Logs', 'ai-review-generator'); ?></a>
        </p>
    </div>
</div>