<?php
/**
 * The settings-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 * @subpackage AI_Review_Generator/admin
 */

class AI_Review_Generator_Settings {

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
     * AI Models instance
     *
     * @var AI_Review_Generator_AI_Models
     */
    private $ai_models;

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
        
        if (file_exists(AI_REVIEW_GENERATOR_PLUGIN_DIR . 'includes/class-ai-models.php')) {
            require_once AI_REVIEW_GENERATOR_PLUGIN_DIR . 'includes/class-ai-models.php';
            $this->ai_models = new AI_Review_Generator_AI_Models();
        }
    }

    /**
     * Set default settings if methods don't exist
     * 
     * This is a fallback to prevent fatal errors during activation
     * 
     * @param string $name Method name
     * @param array $arguments Method arguments
     * @return void
     */
    public function __call($name, $arguments) {
        // If a method doesn't exist, provide a default callback implementation
        if (strpos($name, '_callback') !== false) {
            echo '<p class="description">' . __('This setting is currently not available.', 'ai-review-generator') . '</p>';
        }
    }

    /**
     * Register settings
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            'ai_review_generator_settings',
            'ai_review_generator_settings',
            [$this, 'sanitize_settings']
        );
        
        // General settings section
        add_settings_section(
            'ai_review_generator_general',
            __('General Settings', 'ai-review-generator'),
            [$this, 'general_section_callback'],
            'ai_review_generator_settings'
        );
        
        // AI Model settings section
        add_settings_section(
            'ai_review_generator_ai_model',
            __('AI Model Settings', 'ai-review-generator'),
            [$this, 'ai_model_section_callback'],
            'ai_review_generator_settings'
        );
        
        // Review settings section
        add_settings_section(
            'ai_review_generator_review',
            __('Review Settings', 'ai-review-generator'),
            [$this, 'review_section_callback'],
            'ai_review_generator_settings'
        );
        
        // Style settings section
        add_settings_section(
            'ai_review_generator_style',
            __('Style Settings', 'ai-review-generator'),
            [$this, 'style_section_callback'],
            'ai_review_generator_settings'
        );
        
        // Register general settings fields
        add_settings_field(
            'auto_generate_default',
            __('Default Auto-Generation', 'ai-review-generator'),
            [$this, 'auto_generate_default_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_general'
        );
        
        add_settings_field(
            'review_position',
            __('Review Position', 'ai-review-generator'),
            [$this, 'review_position_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_general'
        );
        
        add_settings_field(
            'review_display_style',
            __('Review Display Style', 'ai-review-generator'),
            [$this, 'review_display_style_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_general'
        );
        
        add_settings_field(
            'reviews_per_post',
            __('Reviews Per Post/Product', 'ai-review-generator'),
            [$this, 'reviews_per_post_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_general'
        );
        
        add_settings_field(
            'cache_expiration',
            __('Cache Expiration (seconds)', 'ai-review-generator'),
            [$this, 'cache_expiration_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_general'
        );
        
        // Register AI model settings fields
        add_settings_field(
            'ai_model',
            __('AI Model', 'ai-review-generator'),
            [$this, 'ai_model_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_ai_model'
        );
        
        // Dynamic fields for each model
        $models = $this->ai_models->get_available_models();
        
        foreach ($models as $model_id => $model) {
            // API key field
            if ($model['requires_key']) {
                add_settings_field(
                    $model_id . '_api_key',
                    sprintf(__('%s API Key', 'ai-review-generator'), $model['name']),
                    [$this, 'model_api_key_callback'],
                    'ai_review_generator_settings',
                    'ai_review_generator_ai_model',
                    ['model_id' => $model_id, 'model' => $model]
                );
            }
            
            // Endpoint field
            add_settings_field(
                $model_id . '_endpoint',
                sprintf(__('%s Endpoint', 'ai-review-generator'), $model['name']),
                [$this, 'model_endpoint_callback'],
                'ai_review_generator_settings',
                'ai_review_generator_ai_model',
                ['model_id' => $model_id, 'model' => $model]
            );
            
            // Model name field
            add_settings_field(
                $model_id . '_model_name',
                sprintf(__('%s Model Name', 'ai-review-generator'), $model['name']),
                [$this, 'model_name_callback'],
                'ai_review_generator_settings',
                'ai_review_generator_ai_model',
                ['model_id' => $model_id, 'model' => $model]
            );
        }
        
        // Temperature field
        add_settings_field(
            'ai_temperature',
            __('Temperature', 'ai-review-generator'),
            [$this, 'ai_temperature_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_ai_model'
        );
        
        // Register review settings fields
        add_settings_field(
            'review_tone',
            __('Review Tone', 'ai-review-generator'),
            [$this, 'review_tone_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_review'
        );
        
        // Reviewer Names Settings
        add_settings_field(
            'enable_reviewer_names',
            __('Enable Reviewer Names', 'ai-review-generator'),
            [$this, 'enable_reviewer_names_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_review'
        );
        
        add_settings_field(
            'reviewer_name_type',
            __('Reviewer Name Type', 'ai-review-generator'),
            [$this, 'reviewer_name_type_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_review'
        );
        
        add_settings_field(
            'reviewer_name_format',
            __('Name Format', 'ai-review-generator'),
            [$this, 'reviewer_name_format_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_review'
        );
        
        add_settings_field(
            'min_word_count',
            __('Minimum Word Count', 'ai-review-generator'),
            [$this, 'min_word_count_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_review'
        );
        
        add_settings_field(
            'max_word_count',
            __('Maximum Word Count', 'ai-review-generator'),
            [$this, 'max_word_count_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_review'
        );
        
        add_settings_field(
            'review_structure',
            __('Review Structure', 'ai-review-generator'),
            [$this, 'review_structure_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_review'
        );
        
        // Register style settings fields
        add_settings_field(
            'box_bg_color',
            __('Box Background Color', 'ai-review-generator'),
            [$this, 'color_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'box_bg_color']
        );
        
        add_settings_field(
            'box_border_color',
            __('Box Border Color', 'ai-review-generator'),
            [$this, 'color_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'box_border_color']
        );
        
        add_settings_field(
            'box_border_width',
            __('Box Border Width', 'ai-review-generator'),
            [$this, 'text_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'box_border_width']
        );
        
        add_settings_field(
            'box_border_radius',
            __('Box Border Radius', 'ai-review-generator'),
            [$this, 'text_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'box_border_radius']
        );
        
        add_settings_field(
            'box_padding',
            __('Box Padding', 'ai-review-generator'),
            [$this, 'text_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'box_padding']
        );
        
        add_settings_field(
            'box_margin',
            __('Box Margin', 'ai-review-generator'),
            [$this, 'text_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'box_margin']
        );
        
        add_settings_field(
            'title_color',
            __('Title Color', 'ai-review-generator'),
            [$this, 'color_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'title_color']
        );
        
        add_settings_field(
            'text_color',
            __('Text Color', 'ai-review-generator'),
            [$this, 'color_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'text_color']
        );
        
        add_settings_field(
            'star_color_primary',
            __('Star Color (Filled)', 'ai-review-generator'),
            [$this, 'color_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'star_color_primary']
        );
        
        add_settings_field(
            'star_color_secondary',
            __('Star Color (Empty)', 'ai-review-generator'),
            [$this, 'color_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'star_color_secondary']
        );
        
        add_settings_field(
            'enable_dark_mode',
            __('Enable Dark Mode', 'ai-review-generator'),
            [$this, 'enable_dark_mode_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style'
        );
        
        // Dark mode fields
        add_settings_field(
            'dark_box_bg_color',
            __('Dark Mode: Box Background Color', 'ai-review-generator'),
            [$this, 'color_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'dark_box_bg_color']
        );
        
        add_settings_field(
            'dark_box_border_color',
            __('Dark Mode: Box Border Color', 'ai-review-generator'),
            [$this, 'color_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'dark_box_border_color']
        );
        
        add_settings_field(
            'dark_title_color',
            __('Dark Mode: Title Color', 'ai-review-generator'),
            [$this, 'color_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'dark_title_color']
        );
        
        add_settings_field(
            'dark_text_color',
            __('Dark Mode: Text Color', 'ai-review-generator'),
            [$this, 'color_field_callback'],
            'ai_review_generator_settings',
            'ai_review_generator_style',
            ['field' => 'dark_text_color']
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $input Settings input
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        
        // General settings
        if (isset($input['auto_generate_default'])) {
            $sanitized['auto_generate_default'] = sanitize_text_field($input['auto_generate_default']);
        }
        
        if (isset($input['review_position'])) {
            $sanitized['review_position'] = sanitize_text_field($input['review_position']);
        }
        
        if (isset($input['review_display_style'])) {
            $sanitized['review_display_style'] = sanitize_text_field($input['review_display_style']);
        }
        
        if (isset($input['reviews_per_post'])) {
            $sanitized['reviews_per_post'] = intval($input['reviews_per_post']);
            // Limit between 1-10
            $sanitized['reviews_per_post'] = max(1, min(10, $sanitized['reviews_per_post']));
        }
        
        if (isset($input['cache_expiration'])) {
            $sanitized['cache_expiration'] = intval($input['cache_expiration']);
        }
        
        // AI model settings
        if (isset($input['ai_model'])) {
            $sanitized['ai_model'] = sanitize_text_field($input['ai_model']);
        }
        
        // API keys for each model
        $models = $this->ai_models->get_available_models();
        
        foreach ($models as $model_id => $model) {
            if (isset($input[$model_id . '_api_key'])) {
                $sanitized[$model_id . '_api_key'] = sanitize_text_field($input[$model_id . '_api_key']);
            }
            
            if (isset($input[$model_id . '_endpoint'])) {
                $sanitized[$model_id . '_endpoint'] = esc_url_raw($input[$model_id . '_endpoint']);
            }
            
            if (isset($input[$model_id . '_model_name'])) {
                $sanitized[$model_id . '_model_name'] = sanitize_text_field($input[$model_id . '_model_name']);
            }
        }
        
        if (isset($input['ai_temperature'])) {
            $sanitized['ai_temperature'] = floatval($input['ai_temperature']);
            $sanitized['ai_temperature'] = max(0, min(1, $sanitized['ai_temperature']));
        }
        
        // Review settings
        if (isset($input['review_tone'])) {
            $sanitized['review_tone'] = sanitize_text_field($input['review_tone']);
        }
        
        // Reviewer name settings
        if (isset($input['enable_reviewer_names'])) {
            $sanitized['enable_reviewer_names'] = sanitize_text_field($input['enable_reviewer_names']);
        }
        
        if (isset($input['reviewer_name_type'])) {
            $sanitized['reviewer_name_type'] = sanitize_text_field($input['reviewer_name_type']);
        }
        
        if (isset($input['reviewer_name_format'])) {
            $sanitized['reviewer_name_format'] = sanitize_text_field($input['reviewer_name_format']);
        }
        
        if (isset($input['min_word_count'])) {
            $sanitized['min_word_count'] = intval($input['min_word_count']);
        }
        
        if (isset($input['max_word_count'])) {
            $sanitized['max_word_count'] = intval($input['max_word_count']);
        }
        
        if (isset($input['review_structure'])) {
            $sanitized['review_structure'] = sanitize_text_field($input['review_structure']);
        }
        
        // Style settings
        if (isset($input['box_bg_color'])) {
            $sanitized['box_bg_color'] = sanitize_hex_color($input['box_bg_color']);
        }
        
        if (isset($input['box_border_color'])) {
            $sanitized['box_border_color'] = sanitize_hex_color($input['box_border_color']);
        }
        
        if (isset($input['box_border_width'])) {
            $sanitized['box_border_width'] = sanitize_text_field($input['box_border_width']);
        }
        
        if (isset($input['box_border_radius'])) {
            $sanitized['box_border_radius'] = sanitize_text_field($input['box_border_radius']);
        }
        
        if (isset($input['box_padding'])) {
            $sanitized['box_padding'] = sanitize_text_field($input['box_padding']);
        }
        
        if (isset($input['box_margin'])) {
            $sanitized['box_margin'] = sanitize_text_field($input['box_margin']);
        }
        
        if (isset($input['title_color'])) {
            $sanitized['title_color'] = sanitize_hex_color($input['title_color']);
        }
        
        if (isset($input['text_color'])) {
            $sanitized['text_color'] = sanitize_hex_color($input['text_color']);
        }
        
        if (isset($input['star_color_primary'])) {
            $sanitized['star_color_primary'] = sanitize_hex_color($input['star_color_primary']);
        }
        
        if (isset($input['star_color_secondary'])) {
            $sanitized['star_color_secondary'] = sanitize_hex_color($input['star_color_secondary']);
        }
        
        if (isset($input['enable_dark_mode'])) {
            $sanitized['enable_dark_mode'] = sanitize_text_field($input['enable_dark_mode']);
        }
        
        if (isset($input['dark_box_bg_color'])) {
            $sanitized['dark_box_bg_color'] = sanitize_hex_color($input['dark_box_bg_color']);
        }
        
        if (isset($input['dark_box_border_color'])) {
            $sanitized['dark_box_border_color'] = sanitize_hex_color($input['dark_box_border_color']);
        }
        
        if (isset($input['dark_title_color'])) {
            $sanitized['dark_title_color'] = sanitize_hex_color($input['dark_title_color']);
        }
        
        if (isset($input['dark_text_color'])) {
            $sanitized['dark_text_color'] = sanitize_hex_color($input['dark_text_color']);
        }
        
        return $sanitized;
    }

    /**
     * General section callback
     *
     * @return void
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general plugin settings.', 'ai-review-generator') . '</p>';
    }

    /**
     * AI model section callback
     *
     * @return void
     */
    public function ai_model_section_callback() {
        echo '<p>' . __('Configure AI model settings.', 'ai-review-generator') . '</p>';
    }

    /**
     * Review section callback
     *
     * @return void
     */
    public function review_section_callback() {
        echo '<p>' . __('Configure review generation settings.', 'ai-review-generator') . '</p>';
    }

    /**
     * Style section callback
     *
     * @return void
     */
    public function style_section_callback() {
        echo '<p>' . __('Configure review display styles.', 'ai-review-generator') . '</p>';
    }

    /**
     * Auto-generate default callback
     *
     * @return void
     */
    public function auto_generate_default_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['auto_generate_default']) ? $settings['auto_generate_default'] : 'enabled';
        ?>
        <select name="ai_review_generator_settings[auto_generate_default]">
            <option value="enabled" <?php selected($value, 'enabled'); ?>><?php _e('Enabled', 'ai-review-generator'); ?></option>
            <option value="disabled" <?php selected($value, 'disabled'); ?>><?php _e('Disabled', 'ai-review-generator'); ?></option>
        </select>
        <p class="description"><?php _e('Default setting for new posts/products.', 'ai-review-generator'); ?></p>
        <?php
    }

    /**
     * Review position callback
     *
     * @return void
     */
    public function review_position_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['review_position']) ? $settings['review_position'] : 'after';
        ?>
        <select name="ai_review_generator_settings[review_position]">
            <option value="before" <?php selected($value, 'before'); ?>><?php _e('Before content', 'ai-review-generator'); ?></option>
            <option value="after" <?php selected($value, 'after'); ?>><?php _e('After content', 'ai-review-generator'); ?></option>
            <option value="first_paragraph" <?php selected($value, 'first_paragraph'); ?>><?php _e('After first paragraph', 'ai-review-generator'); ?></option>
        </select>
        <p class="description"><?php _e('Where to display the review in post/page content.', 'ai-review-generator'); ?></p>
        <?php
    }

    /**
     * Review display style callback
     *
     * @return void
     */
    public function review_display_style_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['review_display_style']) ? $settings['review_display_style'] : 'boxed';
        ?>
        <select name="ai_review_generator_settings[review_display_style]">
            <option value="boxed" <?php selected($value, 'boxed'); ?>><?php _e('Boxed', 'ai-review-generator'); ?></option>
            <option value="minimal" <?php selected($value, 'minimal'); ?>><?php _e('Minimal', 'ai-review-generator'); ?></option>
            <option value="card" <?php selected($value, 'card'); ?>><?php _e('Card with shadow', 'ai-review-generator'); ?></option>
        </select>
        <p class="description"><?php _e('Style of the review display.', 'ai-review-generator'); ?></p>
        <?php
    }

    /**
     * Reviews per post callback
     *
     * @return void
     */
    public function reviews_per_post_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['reviews_per_post']) ? intval($settings['reviews_per_post']) : 1;
        ?>
        <input type="number" name="ai_review_generator_settings[reviews_per_post]" value="<?php echo esc_attr($value); ?>" min="1" max="10" step="1" />
        <p class="description"><?php _e('Default number of reviews to generate per post/product. Can be overridden on individual posts.', 'ai-review-generator'); ?></p>
        <?php
    }

    /**
     * Cache expiration callback
     *
     * @return void
     */
    public function cache_expiration_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['cache_expiration']) ? $settings['cache_expiration'] : 86400;
        ?>
        <input type="number" name="ai_review_generator_settings[cache_expiration]" value="<?php echo esc_attr($value); ?>" min="0" step="1" />
        <p class="description"><?php _e('Cache expiration time in seconds. Set to 0 to disable caching.', 'ai-review-generator'); ?></p>
        <?php
    }

    /**
     * AI model callback
     *
     * @return void
     */
    public function ai_model_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['ai_model']) ? $settings['ai_model'] : 'deepseek';
        $models = $this->ai_models->get_available_models();
        ?>
        <select name="ai_review_generator_settings[ai_model]" id="ai_model_select">
            <?php foreach ($models as $model_id => $model) : ?>
                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($value, $model_id); ?>><?php echo esc_html($model['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Select the AI model to use for generating reviews.', 'ai-review-generator'); ?></p>
        <?php
    }

    /**
     * Model API key callback
     *
     * @param array $args Callback arguments
     * @return void
     */
    public function model_api_key_callback($args) {
        $model_id = $args['model_id'];
        $model = $args['model'];
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings[$model_id . '_api_key']) ? $settings[$model_id . '_api_key'] : '';
        ?>
        <div class="ai-model-field ai-model-field-<?php echo esc_attr($model_id); ?>">
            <input type="password" name="ai_review_generator_settings[<?php echo esc_attr($model_id); ?>_api_key]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
            <p class="description"><?php printf(__('API key for %s.', 'ai-review-generator'), $model['name']); ?></p>
        </div>
        <?php
    }

    /**
     * Model endpoint callback
     *
     * @param array $args Callback arguments
     * @return void
     */
    public function model_endpoint_callback($args) {
        $model_id = $args['model_id'];
        $model = $args['model'];
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings[$model_id . '_endpoint']) ? $settings[$model_id . '_endpoint'] : $model['default_endpoint'];
        ?>
        <div class="ai-model-field ai-model-field-<?php echo esc_attr($model_id); ?>">
            <input type="text" name="ai_review_generator_settings[<?php echo esc_attr($model_id); ?>_endpoint]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
            <p class="description"><?php printf(__('API endpoint for %s.', 'ai-review-generator'), $model['name']); ?></p>
            <?php if (isset($model['self_hosted']) && $model['self_hosted']) : ?>
                <p class="description"><?php _e('This model supports self-hosting. You can set a custom endpoint if you\'re hosting it yourself.', 'ai-review-generator'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Model name callback
     *
     * @param array $args Callback arguments
     * @return void
     */
    public function model_name_callback($args) {
        $model_id = $args['model_id'];
        $model = $args['model'];
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings[$model_id . '_model_name']) ? $settings[$model_id . '_model_name'] : $model['default_model'];
        ?>
        <div class="ai-model-field ai-model-field-<?php echo esc_attr($model_id); ?>">
            <select name="ai_review_generator_settings[<?php echo esc_attr($model_id); ?>_model_name]">
                <?php foreach ($model['model_names'] as $model_name => $model_label) : ?>
                    <option value="<?php echo esc_attr($model_name); ?>" <?php selected($value, $model_name); ?>><?php echo esc_html($model_label); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php printf(__('Model name/version for %s.', 'ai-review-generator'), $model['name']); ?></p>
        </div>
        <?php
    }

    /**
     * AI temperature callback
     *
     * @return void
     */
    public function ai_temperature_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['ai_temperature']) ? $settings['ai_temperature'] : 0.7;
        ?>
        <input type="range" name="ai_review_generator_settings[ai_temperature]" value="<?php echo esc_attr($value); ?>" min="0" max="1" step="0.1" class="ai-temperature-slider" />
        <span class="ai-temperature-value"><?php echo esc_html($value); ?></span>
        <p class="description"><?php _e('Controls randomness: lower values (0.0) are more deterministic, higher values (1.0) are more creative.', 'ai-review-generator'); ?></p>
        <?php
    }

    /**
     * Review tone callback
     *
     * @return void
     */
    public function review_tone_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['review_tone']) ? $settings['review_tone'] : 'professional';
        ?>
        <select name="ai_review_generator_settings[review_tone]">
            <option value="professional" <?php selected($value, 'professional'); ?>><?php _e('Professional', 'ai-review-generator'); ?></option>
            <option value="casual" <?php selected($value, 'casual'); ?>><?php _e('Casual', 'ai-review-generator'); ?></option>
            <option value="friendly" <?php selected($value, 'friendly'); ?>><?php _e('Friendly', 'ai-review-generator'); ?></option>
            <option value="detailed" <?php selected($value, 'detailed'); ?>><?php _e('Detailed', 'ai-review-generator'); ?></option>
            <option value="concise" <?php selected($value, 'concise'); ?>><?php _e('Concise', 'ai-review-generator'); ?></option>
            <option value="technical" <?php selected($value, 'technical'); ?>><?php _e('Technical', 'ai-review-generator'); ?></option>
        </select>
        <p class="description"><?php _e('Tone of voice for the reviews.', 'ai-review-generator'); ?></p>
        <?php
    }
    
    /**
     * Enable reviewer names callback
     * 
     * @return void
     */
    public function enable_reviewer_names_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['enable_reviewer_names']) ? $settings['enable_reviewer_names'] : 'no';
        ?>
        <select name="ai_review_generator_settings[enable_reviewer_names]" id="enable_reviewer_names">
            <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Yes', 'ai-review-generator'); ?></option>
            <option value="no" <?php selected($value, 'no'); ?>><?php _e('No', 'ai-review-generator'); ?></option>
        </select>
        <p class="description"><?php _e('Enable AI-generated reviewer names for reviews.', 'ai-review-generator'); ?></p>
        <?php
    }
    
    /**
     * Reviewer name type callback
     * 
     * @return void
     */
    public function reviewer_name_type_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['reviewer_name_type']) ? $settings['reviewer_name_type'] : 'random';
        ?>
        <select name="ai_review_generator_settings[reviewer_name_type]" id="reviewer_name_type" class="reviewer-name-setting">
            <option value="random" <?php selected($value, 'random'); ?>><?php _e('Random Names', 'ai-review-generator'); ?></option>
            <option value="location" <?php selected($value, 'location'); ?>><?php _e('Location-Based Names', 'ai-review-generator'); ?></option>
            <option value="manual" <?php selected($value, 'manual'); ?>><?php _e('Manual Entry Only', 'ai-review-generator'); ?></option>
        </select>
        <p class="description"><?php _e('Choose how reviewer names should be generated.', 'ai-review-generator'); ?></p>
        <?php
    }
    
    /**
     * Reviewer name format callback
     * 
     * @return void
     */
    public function reviewer_name_format_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['reviewer_name_format']) ? $settings['reviewer_name_format'] : 'full';
        ?>
        <select name="ai_review_generator_settings[reviewer_name_format]" id="reviewer_name_format" class="reviewer-name-setting">
            <option value="full" <?php selected($value, 'full'); ?>><?php _e('Full Name (John Smith)', 'ai-review-generator'); ?></option>
            <option value="first_initial" <?php selected($value, 'first_initial'); ?>><?php _e('First Initial (J. Smith)', 'ai-review-generator'); ?></option>
            <option value="last_initial" <?php selected($value, 'last_initial'); ?>><?php _e('Last Initial (John S.)', 'ai-review-generator'); ?></option>
            <option value="first_name" <?php selected($value, 'first_name'); ?>><?php _e('First Name Only (John)', 'ai-review-generator'); ?></option>
        </select>
        <p class="description"><?php _e('Choose how reviewer names should be displayed.', 'ai-review-generator'); ?></p>
        <?php
    }

    /**
     * Minimum word count callback
     *
     * @return void
     */
    public function min_word_count_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['min_word_count']) ? $settings['min_word_count'] : 200;
        ?>
        <input type="number" name="ai_review_generator_settings[min_word_count]" value="<?php echo esc_attr($value); ?>" min="50" step="10" />
        <p class="description"><?php _e('Minimum word count for generated reviews.', 'ai-review-generator'); ?></p>
        <?php
    }

    /**
     * Maximum word count callback
     *
     * @return void
     */
    public function max_word_count_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['max_word_count']) ? $settings['max_word_count'] : 500;
        ?>
        <input type="number" name="ai_review_generator_settings[max_word_count]" value="<?php echo esc_attr($value); ?>" min="100" step="10" />
        <p class="description"><?php _e('Maximum word count for generated reviews.', 'ai-review-generator'); ?></p>
        <?php
    }

    /**
     * Review structure callback
     *
     * @return void
     */
    public function review_structure_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['review_structure']) ? $settings['review_structure'] : 'pros_cons';
        ?>
        <select name="ai_review_generator_settings[review_structure]">
            <option value="pros_cons" <?php selected($value, 'pros_cons'); ?>><?php _e('Pros and Cons', 'ai-review-generator'); ?></option>
            <option value="detailed" <?php selected($value, 'detailed'); ?>><?php _e('Detailed Analysis', 'ai-review-generator'); ?></option>
            <option value="concise" <?php selected($value, 'concise'); ?>><?php _e('Concise Summary', 'ai-review-generator'); ?></option>
        </select>
        <p class="description"><?php _e('Structure format for the reviews.', 'ai-review-generator'); ?></p>
        <?php
    }

    /**
     * Color field callback
     *
     * @param array $args Callback arguments
     * @return void
     */
    public function color_field_callback($args) {
        $field = $args['field'];
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings[$field]) ? $settings[$field] : '';
        ?>
        <input type="text" name="ai_review_generator_settings[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($value); ?>" class="ai-color-picker" />
        <?php
    }

    /**
     * Text field callback
     *
     * @param array $args Callback arguments
     * @return void
     */
    public function text_field_callback($args) {
        $field = $args['field'];
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings[$field]) ? $settings[$field] : '';
        ?>
        <input type="text" name="ai_review_generator_settings[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <?php
    }

    /**
     * Enable dark mode callback
     *
     * @return void
     */
    public function enable_dark_mode_callback() {
        $settings = get_option('ai_review_generator_settings', []);
        $value = isset($settings['enable_dark_mode']) ? $settings['enable_dark_mode'] : 'yes';
        ?>
        <select name="ai_review_generator_settings[enable_dark_mode]">
            <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Yes', 'ai-review-generator'); ?></option>
            <option value="no" <?php selected($value, 'no'); ?>><?php _e('No', 'ai-review-generator'); ?></option>
        </select>
        <p class="description"><?php _e('Enable dark mode support for review boxes.', 'ai-review-generator'); ?></p>
        <?php
    }
}