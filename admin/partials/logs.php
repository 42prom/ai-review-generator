<?php
/**
 * Logs page partial
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ai-review-logs">
    <h1><?php _e('AI Review Generator API Logs', 'ai-review-generator'); ?></h1>
    
    <?php if (empty($logs)) : ?>
        <div class="notice notice-info">
            <p><?php _e('No API logs found.', 'ai-review-generator'); ?></p>
        </div>
    <?php else : ?>
        <table class="widefat ai-review-logs-table">
            <thead>
                <tr>
                    <th><?php _e('Date', 'ai-review-generator'); ?></th>
                    <th><?php _e('Post', 'ai-review-generator'); ?></th>
                    <th><?php _e('AI Model', 'ai-review-generator'); ?></th>
                    <th><?php _e('Tokens Used', 'ai-review-generator'); ?></th>
                    <th><?php _e('Status', 'ai-review-generator'); ?></th>
                    <th><?php _e('Error', 'ai-review-generator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log) : 
                    $post = get_post($log->post_id);
                    $post_title = $post ? $post->post_title : sprintf(__('Post ID: %d', 'ai-review-generator'), $log->post_id);
                    $post_edit_link = $post ? get_edit_post_link($log->post_id) : '';
                    $log_date = mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $log->request_time);
                    $status_class = $log->status === 'success' ? 'success' : 'error';
                ?>
                    <tr>
                        <td><?php echo esc_html($log_date); ?></td>
                        <td>
                            <?php if ($post_edit_link) : ?>
                                <a href="<?php echo esc_url($post_edit_link); ?>"><?php echo esc_html($post_title); ?></a>
                            <?php else : ?>
                                <?php echo esc_html($post_title); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($log->ai_model); ?></td>
                        <td><?php echo esc_html($log->tokens_used); ?></td>
                        <td class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($log->status); ?></td>
                        <td><?php echo esc_html($log->error_message); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php
        // Pagination
        $total_pages = ceil($total_logs / $per_page);
        
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
    
    <div class="ai-review-logs-actions" style="margin-top: 20px;">
        <form method="post">
            <?php wp_nonce_field('ai_review_clear_logs', 'ai_review_clear_logs_nonce'); ?>
            <input type="hidden" name="action" value="clear_logs">
            <button type="submit" class="button" onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear all logs? This cannot be undone.', 'ai-review-generator'); ?>');"><?php _e('Clear All Logs', 'ai-review-generator'); ?></button>
        </form>
    </div>
    
    <div class="ai-review-logs-info" style="margin-top: 20px; background: #fff; padding: 15px; border: 1px solid #e2e4e7;">
        <h3><?php _e('API Usage Information', 'ai-review-generator'); ?></h3>
        <p><?php _e('This page displays logs of all API requests made to AI models for generating reviews. You can use this information to track your API usage and monitor costs.', 'ai-review-generator'); ?></p>
        <p><?php _e('Tokens are the units used by AI models to measure text length. Different models have different pricing structures based on tokens used.', 'ai-review-generator'); ?></p>
    </div>
</div>