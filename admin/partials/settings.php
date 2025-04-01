<?php
/**
 * Settings page partial
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
?>

<div class="wrap">
    <h1><?php _e('AI Review Generator Settings', 'ai-review-generator'); ?></h1>
    
    <nav class="nav-tab-wrapper ai-review-tabs">
        <a href="?page=ai-review-generator-settings&tab=general" class="nav-tab ai-review-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>" data-tab="general"><?php _e('General', 'ai-review-generator'); ?></a>
        <a href="?page=ai-review-generator-settings&tab=ai_model" class="nav-tab ai-review-tab <?php echo $current_tab === 'ai_model' ? 'nav-tab-active' : ''; ?>" data-tab="ai_model"><?php _e('AI Models', 'ai-review-generator'); ?></a>
        <a href="?page=ai-review-generator-settings&tab=review" class="nav-tab ai-review-tab <?php echo $current_tab === 'review' ? 'nav-tab-active' : ''; ?>" data-tab="review"><?php _e('Review Content', 'ai-review-generator'); ?></a>
        <a href="?page=ai-review-generator-settings&tab=style" class="nav-tab ai-review-tab <?php echo $current_tab === 'style' ? 'nav-tab-active' : ''; ?>" data-tab="style"><?php _e('Styling', 'ai-review-generator'); ?></a>
    </nav>
    
    <form method="post" action="options.php" class="ai-review-settings-form">
        <?php settings_fields('ai_review_generator_settings'); ?>
        
        <div id="ai-review-tab-general" class="ai-review-tab-content" style="<?php echo $current_tab === 'general' ? 'display:block;' : 'display:none;'; ?>">
            <h2><?php _e('General Settings', 'ai-review-generator'); ?></h2>
            <table class="form-table ai-review-settings-table">
                <tr>
                    <th scope="row">
                        <label for="auto_generate_default"><?php _e('Default Auto-Generation', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->auto_generate_default_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="review_position"><?php _e('Review Position', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->review_position_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="review_display_style"><?php _e('Review Display Style', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->review_display_style_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="reviews_per_post"><?php _e('Reviews Per Post/Product', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->reviews_per_post_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="cache_expiration"><?php _e('Cache Expiration (seconds)', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->cache_expiration_callback(); ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="ai-review-tab-ai_model" class="ai-review-tab-content" style="<?php echo $current_tab === 'ai_model' ? 'display:block;' : 'display:none;'; ?>">
            <h2><?php _e('AI Model Settings', 'ai-review-generator'); ?></h2>
            <table class="form-table ai-review-settings-table">
                <tr>
                    <th scope="row">
                        <label for="ai_model"><?php _e('AI Model', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->ai_model_callback(); ?>
                    </td>
                </tr>
                
                <?php foreach ($available_models as $model_id => $model) : ?>
                    <?php if ($model['requires_key']) : ?>
                        <tr class="ai-model-settings ai-model-settings-<?php echo esc_attr($model_id); ?>">
                            <th scope="row">
                                <label for="<?php echo esc_attr($model_id); ?>_api_key"><?php printf(__('%s API Key', 'ai-review-generator'), $model['name']); ?></label>
                            </th>
                            <td>
                                <?php $settings_instance->model_api_key_callback(['model_id' => $model_id, 'model' => $model]); ?>
                                <button type="button" id="test-connection-<?php echo esc_attr($model_id); ?>" class="button test-connection-btn" data-model-id="<?php echo esc_attr($model_id); ?>"><?php _e('Test Connection', 'ai-review-generator'); ?></button>
                                <div id="connection-status-<?php echo esc_attr($model_id); ?>"></div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <tr class="ai-model-settings ai-model-settings-<?php echo esc_attr($model_id); ?>">
                        <th scope="row">
                            <label for="<?php echo esc_attr($model_id); ?>_endpoint"><?php printf(__('%s Endpoint', 'ai-review-generator'), $model['name']); ?></label>
                        </th>
                        <td>
                            <?php $settings_instance->model_endpoint_callback(['model_id' => $model_id, 'model' => $model]); ?>
                        </td>
                    </tr>
                    
                    <tr class="ai-model-settings ai-model-settings-<?php echo esc_attr($model_id); ?>">
                        <th scope="row">
                            <label for="<?php echo esc_attr($model_id); ?>_model_name"><?php printf(__('%s Model Name', 'ai-review-generator'), $model['name']); ?></label>
                        </th>
                        <td>
                            <?php $settings_instance->model_name_callback(['model_id' => $model_id, 'model' => $model]); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <tr>
                    <th scope="row">
                        <label for="ai_temperature"><?php _e('Temperature', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->ai_temperature_callback(); ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="ai-review-tab-review" class="ai-review-tab-content" style="<?php echo $current_tab === 'review' ? 'display:block;' : 'display:none;'; ?>">
            <h2><?php _e('Review Content Settings', 'ai-review-generator'); ?></h2>
            <table class="form-table ai-review-settings-table">
                <tr>
                    <th scope="row">
                        <label for="review_tone"><?php _e('Review Tone', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->review_tone_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="enable_reviewer_names"><?php _e('Enable Reviewer Names', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->enable_reviewer_names_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="reviewer_name_type"><?php _e('Reviewer Name Type', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->reviewer_name_type_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="reviewer_name_format"><?php _e('Name Format', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->reviewer_name_format_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="min_word_count"><?php _e('Minimum Word Count', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->min_word_count_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="max_word_count"><?php _e('Maximum Word Count', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->max_word_count_callback(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="review_structure"><?php _e('Review Structure', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->review_structure_callback(); ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="ai-review-tab-style" class="ai-review-tab-content" style="<?php echo $current_tab === 'style' ? 'display:block;' : 'display:none;'; ?>">
            <h2><?php _e('Style Settings', 'ai-review-generator'); ?></h2>
            <table class="form-table ai-review-settings-table">
                <tr>
                    <th scope="row">
                        <label for="box_bg_color"><?php _e('Box Background Color', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->color_field_callback(['field' => 'box_bg_color']); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="box_border_color"><?php _e('Box Border Color', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->color_field_callback(['field' => 'box_border_color']); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="box_border_width"><?php _e('Box Border Width', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->text_field_callback(['field' => 'box_border_width']); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="box_border_radius"><?php _e('Box Border Radius', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->text_field_callback(['field' => 'box_border_radius']); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="box_padding"><?php _e('Box Padding', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->text_field_callback(['field' => 'box_padding']); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="box_margin"><?php _e('Box Margin', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->text_field_callback(['field' => 'box_margin']); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="title_color"><?php _e('Title Color', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->color_field_callback(['field' => 'title_color']); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="text_color"><?php _e('Text Color', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->color_field_callback(['field' => 'text_color']); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="star_color_primary"><?php _e('Star Color (Filled)', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->color_field_callback(['field' => 'star_color_primary']); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="star_color_secondary"><?php _e('Star Color (Empty)', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->color_field_callback(['field' => 'star_color_secondary']); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="enable_dark_mode"><?php _e('Enable Dark Mode', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->enable_dark_mode_callback(); ?>
                    </td>
                </tr>
                
                <!-- Dark Mode Settings -->
                <tr class="dark-mode-settings">
                    <th scope="row">
                        <label for="dark_box_bg_color"><?php _e('Dark Mode: Box Background Color', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->color_field_callback(['field' => 'dark_box_bg_color']); ?>
                    </td>
                </tr>
                <tr class="dark-mode-settings">
                    <th scope="row">
                        <label for="dark_box_border_color"><?php _e('Dark Mode: Box Border Color', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->color_field_callback(['field' => 'dark_box_border_color']); ?>
                    </td>
                </tr>
                <tr class="dark-mode-settings">
                    <th scope="row">
                        <label for="dark_title_color"><?php _e('Dark Mode: Title Color', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->color_field_callback(['field' => 'dark_title_color']); ?>
                    </td>
                </tr>
                <tr class="dark-mode-settings">
                    <th scope="row">
                        <label for="dark_text_color"><?php _e('Dark Mode: Text Color', 'ai-review-generator'); ?></label>
                    </th>
                    <td>
                        <?php $settings_instance->color_field_callback(['field' => 'dark_text_color']); ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button(); ?>
    </form>
    
    <script>
        jQuery(document).ready(function($) {
            // Handle tab clicks directly
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                // Get tab ID
                var tabId = $(this).data('tab');
                
                // Hide all tabs
                $('.ai-review-tab-content').hide();
                
                // Show selected tab
                $('#ai-review-tab-' + tabId).show();
                
                // Update active tab class
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Update URL
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tabId);
                history.pushState({}, '', url);
            });
            
            // Show/hide model settings based on selected model
            function toggleModelSettings() {
                const selectedModel = $('#ai_model_select').val();
                $('.ai-model-settings').hide();
                $('.ai-model-settings-' + selectedModel).show();
            }
            
            // Initialize
            toggleModelSettings();
            
            // Handle model change
            $('#ai_model_select').on('change', toggleModelSettings);
            
            // Toggle dark mode settings
            function toggleDarkModeSettings() {
                const enableDarkMode = $('select[name="ai_review_generator_settings[enable_dark_mode]"]').val();
                if (enableDarkMode === 'yes') {
                    $('.dark-mode-settings').show();
                } else {
                    $('.dark-mode-settings').hide();
                }
            }
            
            // Initialize
            toggleDarkModeSettings();
            
            // Handle dark mode toggle
            $('select[name="ai_review_generator_settings[enable_dark_mode]"]').on('change', toggleDarkModeSettings);
            
            // Toggle reviewer name settings
            function toggleReviewerNameSettings() {
                const enableReviewerNames = $('#enable_reviewer_names').val();
                if (enableReviewerNames === 'yes') {
                    $('.reviewer-name-setting').closest('tr').show();
                } else {
                    $('.reviewer-name-setting').closest('tr').hide();
                }
            }
            
            // Initialize reviewer settings
            toggleReviewerNameSettings();
            
            // Handle reviewer names toggle
            $('#enable_reviewer_names').on('change', toggleReviewerNameSettings);
        });
    </script>
</div>