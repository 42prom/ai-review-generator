<?php
/**
 * Reviews page partial
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ai-review-list">
    <h1><?php _e('AI-Generated Reviews', 'ai-review-generator'); ?></h1>
    
    <?php if (!empty($message)) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($reviews)) : ?>
        <div class="notice notice-info">
            <p><?php _e('No reviews generated yet.', 'ai-review-generator'); ?></p>
        </div>
    <?php else : ?>
        <?php foreach ($reviews as $review) : 
            $post = get_post($review->post_id);
            if (!$post) continue;
            
            $post_title = $post->post_title;
            $post_edit_link = get_edit_post_link($review->post_id);
            $review_date = mysql2date(get_option('date_format'), $review->generated_date);
            $pros = !empty($review->review_pros) ? explode("\n", $review->review_pros) : [];
            $cons = !empty($review->review_cons) ? explode("\n", $review->review_cons) : [];
        ?>
            <div class="ai-review-item">
                <div class="ai-review-item-header">
                    <div>
                        <h2 class="ai-review-item-title">
                            <a href="<?php echo esc_url($post_edit_link); ?>"><?php echo esc_html($post_title); ?></a>
                        </h2>
                        <div class="ai-review-item-meta">
                            <?php echo esc_html(sprintf(__('Generated on %s with %s', 'ai-review-generator'), $review_date, $review->ai_model)); ?>
                            | <?php echo esc_html(sprintf(__('Rating: %s/5', 'ai-review-generator'), number_format($review->rating, 1))); ?>
                            | <?php echo $review->published ? esc_html__('Published', 'ai-review-generator') : esc_html__('Draft', 'ai-review-generator'); ?>
                            <?php if ($review->modified_by_user) : ?>
                                | <?php esc_html_e('Edited', 'ai-review-generator'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <a href="#" class="ai-review-edit-toggle button"><?php _e('Edit', 'ai-review-generator'); ?></a>
                    </div>
                </div>
                
                <div class="ai-review-item-content">
                    <?php if (!empty($review->review_summary)) : ?>
                        <h4><?php _e('Summary', 'ai-review-generator'); ?></h4>
                        <p><?php echo esc_html($review->review_summary); ?></p>
                    <?php endif; ?>
                    
                    <div class="ai-review-columns" style="display: flex; gap: 20px;">
                        <?php if (!empty($pros)) : ?>
                            <div style="flex: 1;">
                                <h4><?php _e('Pros', 'ai-review-generator'); ?></h4>
                                <ul>
                                    <?php foreach ($pros as $pro) : ?>
                                        <li><?php echo esc_html($pro); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($cons)) : ?>
                            <div style="flex: 1;">
                                <h4><?php _e('Cons', 'ai-review-generator'); ?></h4>
                                <ul>
                                    <?php foreach ($cons as $con) : ?>
                                        <li><?php echo esc_html($con); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h4><?php _e('Full Review', 'ai-review-generator'); ?></h4>
                    <div class="ai-review-full-content">
                        <?php echo wpautop(esc_html($review->review_content)); ?>
                    </div>
                    <a href="#" class="ai-review-expand-toggle"><?php _e('Show More', 'ai-review-generator'); ?></a>
                    
                    <!-- Edit Form -->
                    <div class="ai-review-edit-form">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                            <input type="hidden" name="action" value="update_ai_review">
                            <input type="hidden" name="review_id" value="<?php echo esc_attr($review->id); ?>">
                            <?php wp_nonce_field('update_ai_review_' . $review->id); ?>
                            
                            <p>
                                <label for="review_rating_<?php echo esc_attr($review->id); ?>"><?php _e('Rating (out of 5)', 'ai-review-generator'); ?></label>
                                <input type="number" name="review_rating" id="review_rating_<?php echo esc_attr($review->id); ?>" value="<?php echo esc_attr($review->rating); ?>" min="0" max="5" step="0.1" style="width: 80px;">
                            </p>
                            
                            <p>
                                <label for="review_summary_<?php echo esc_attr($review->id); ?>"><?php _e('Summary', 'ai-review-generator'); ?></label>
                                <textarea name="review_summary" id="review_summary_<?php echo esc_attr($review->id); ?>" rows="3"><?php echo esc_textarea($review->review_summary); ?></textarea>
                            </p>
                            
                            <div style="display: flex; gap: 20px;">
                                <div style="flex: 1;">
                                    <label for="review_pros_<?php echo esc_attr($review->id); ?>"><?php _e('Pros (one per line)', 'ai-review-generator'); ?></label>
                                    <textarea name="review_pros" id="review_pros_<?php echo esc_attr($review->id); ?>" rows="5"><?php echo esc_textarea($review->review_pros); ?></textarea>
                                </div>
                                
                                <div style="flex: 1;">
                                    <label for="review_cons_<?php echo esc_attr($review->id); ?>"><?php _e('Cons (one per line)', 'ai-review-generator'); ?></label>
                                    <textarea name="review_cons" id="review_cons_<?php echo esc_attr($review->id); ?>" rows="5"><?php echo esc_textarea($review->review_cons); ?></textarea>
                                </div>
                            </div>
                            
                            <p>
                                <label for="reviewer_name_<?php echo esc_attr($review->id); ?>"><?php _e('Reviewer Name', 'ai-review-generator'); ?></label>
                                <input type="text" name="reviewer_name" id="reviewer_name_<?php echo esc_attr($review->id); ?>" value="<?php echo esc_attr($review->reviewer_name); ?>" class="regular-text">
                                <span class="description"><?php _e('Enter a name for the reviewer (optional)', 'ai-review-generator'); ?></span>
                            </p>
                            
                            <p>
                                <label for="review_content_<?php echo esc_attr($review->id); ?>"><?php _e('Full Review', 'ai-review-generator'); ?></label>
                                <textarea name="review_content" id="review_content_<?php echo esc_attr($review->id); ?>" rows="10"><?php echo esc_textarea($review->review_content); ?></textarea>
                            </p>
                            
                            <p>
                                <label>
                                    <input type="checkbox" name="review_published" value="1" <?php checked($review->published, 1); ?>>
                                    <?php _e('Published', 'ai-review-generator'); ?>
                                </label>
                            </p>
                            
                            <p>
                                <button type="submit" class="button button-primary"><?php _e('Save Changes', 'ai-review-generator'); ?></button>
                                <button type="button" class="button ai-review-edit-cancel"><?php _e('Cancel', 'ai-review-generator'); ?></button>
                            </p>
                            
                            <div class="ai-review-edit-status"></div>
                        </form>
                    </div>
                </div>
                
                <div class="ai-review-item-footer">
                    <?php if ($review->published) : ?>
                        <form method="post">
                            <input type="hidden" name="ai_review_action" value="unpublish">
                            <input type="hidden" name="review_id" value="<?php echo esc_attr($review->id); ?>">
                            <?php wp_nonce_field('ai_review_unpublish_' . $review->id); ?>
                            <button type="submit" class="button"><?php _e('Unpublish', 'ai-review-generator'); ?></button>
                        </form>
                    <?php else : ?>
                        <form method="post">
                            <input type="hidden" name="ai_review_action" value="publish">
                            <input type="hidden" name="review_id" value="<?php echo esc_attr($review->id); ?>">
                            <?php wp_nonce_field('ai_review_publish_' . $review->id); ?>
                            <button type="submit" class="button"><?php _e('Publish', 'ai-review-generator'); ?></button>
                        </form>
                    <?php endif; ?>
                    
                    <form method="post" onsubmit="return confirm('<?php echo esc_js(__('Are you sure you want to delete this review?', 'ai-review-generator')); ?>');">
                        <input type="hidden" name="ai_review_action" value="delete">
                        <input type="hidden" name="review_id" value="<?php echo esc_attr($review->id); ?>">
                        <?php wp_nonce_field('ai_review_delete_' . $review->id); ?>
                        <button type="submit" class="button"><?php _e('Delete', 'ai-review-generator'); ?></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php
        // Pagination
        $total_pages = ceil($total_reviews / $per_page);
        
        if ($total_pages > 1) {
            echo '<div class="tablenav">';
            echo '<div class="tablenav-pages">';
            
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $page,
            ]);
            
            echo '</div>';
            echo '</div>';
        }
        ?>
    <?php endif; ?>
</div>

<script>
    jQuery(document).ready(function($) {
        // Cancel button handler
        $('.ai-review-edit-cancel').on('click', function() {
            $(this).closest('.ai-review-edit-form').hide();
        });
    });
</script>