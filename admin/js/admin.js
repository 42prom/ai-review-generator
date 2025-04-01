/**
 * Admin JavaScript
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 */

(function($) {
    'use strict';

    // Initialize color pickers
    function initColorPickers() {
        $('.ai-color-picker').wpColorPicker();
    }

    // Initialize AI model fields visibility
    function initAIModelFields() {
        const selectedModel = $('#ai_model_select').val();
        
        // Hide all model fields
        $('.ai-model-field').hide();
        
        // Show fields for selected model
        $('.ai-model-field-' + selectedModel).show();
    }

    // Generate review via AJAX
    function generateReview(postId, force = false) {
        const $generateBtn = $('#ai-review-generate-btn');
        const $regenerateBtn = $('#ai-review-regenerate-btn');
        const $status = $('#ai-review-status');
        
        // Disable all buttons during processing
        $generateBtn.prop('disabled', true);
        if ($regenerateBtn.length) {
            $regenerateBtn.prop('disabled', true);
        }
        
        $status.html(ai_review_generator.strings.generating)
               .removeClass('success error')
               .show();
        
        $.ajax({
            url: ai_review_generator.ajax_url,
            type: 'POST',
            data: {
                action: 'generate_ai_review',
                nonce: ai_review_generator.nonce,
                post_id: postId,
                force_regenerate: force
            },
            success: function(response) {
                if (response.success) {
                    $status.html(ai_review_generator.strings.success).addClass('success');
                    
                    // Refresh meta box content
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $status.html(ai_review_generator.strings.error + ' ' + response.data.message).addClass('error');
                    
                    // Re-enable buttons
                    $generateBtn.prop('disabled', false);
                    if ($regenerateBtn.length) {
                        $regenerateBtn.prop('disabled', false);
                    }
                }
            },
            error: function() {
                $status.html(ai_review_generator.strings.error + ' Unknown error.').addClass('error');
                
                // Re-enable buttons
                $generateBtn.prop('disabled', false);
                if ($regenerateBtn.length) {
                    $regenerateBtn.prop('disabled', false);
                }
            }
        });
    }

    // Test AI connection
    function testAIConnection(modelId) {
        const $button = $('#test-connection-' + modelId);
        const $status = $('#connection-status-' + modelId);
        
        $button.prop('disabled', true);
        $status.html(ai_review_generator.strings.testing)
               .removeClass('success error')
               .show();
        
        // Get API key and endpoint from form
        const apiKey = $('input[name="ai_review_generator_settings[' + modelId + '_api_key]"]').val();
        const endpoint = $('input[name="ai_review_generator_settings[' + modelId + '_endpoint]"]').val();
        
        $.ajax({
            url: ai_review_generator.ajax_url,
            type: 'POST',
            data: {
                action: 'test_ai_connection',
                nonce: ai_review_generator.nonce,
                model_id: modelId,
                api_key: apiKey,
                endpoint: endpoint
            },
            success: function(response) {
                if (response.success) {
                    $status.html(ai_review_generator.strings.test_success)
                           .addClass('success')
                           .removeClass('error');
                } else {
                    let errorMsg = response.data.message || 'Unknown error';
                    $status.html(ai_review_generator.strings.test_error + ' ' + errorMsg)
                           .addClass('error')
                           .removeClass('success');
                    console.error('API Connection Error:', errorMsg);
                }
                $button.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                let errorMsg = '';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.data && response.data.message ? response.data.message : error;
                } catch(e) {
                    errorMsg = error || 'Connection failed';
                }
                
                $status.html(ai_review_generator.strings.test_error + ' ' + errorMsg)
                       .addClass('error')
                       .removeClass('success');
                console.error('AJAX Error:', xhr, status, error);
                $button.prop('disabled', false);
            },
            timeout: 30000 // 30 second timeout
        });
    }

    // Initialize temperature slider
    function initTemperatureSlider() {
        const $slider = $('.ai-temperature-slider');
        const $value = $('.ai-temperature-value');
        
        $slider.on('input', function() {
            $value.text($(this).val());
        });
    }

    // Initialize tabs
    function initTabs() {
        // Get the tab from URL or use default
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab') || 'general';
        
        console.log('Initializing tabs, current tab:', tab);
        
        // Hide all tab content
        $('.ai-review-tab-content').hide();
        
        // Show active tab
        $('#ai-review-tab-' + tab).show();
        
        // Set active tab
        $('.ai-review-tab').removeClass('nav-tab-active');
        $('.ai-review-tab[data-tab="' + tab + '"]').addClass('nav-tab-active');
        
        // Tab click handler
        $('.ai-review-tab').on('click', function(e) {
            e.preventDefault();
            
            const tabId = $(this).data('tab');
            console.log('Tab clicked:', tabId);
            
            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);
            
            // Hide all tab content
            $('.ai-review-tab-content').hide();
            
            // Show active tab
            $('#ai-review-tab-' + tabId).show();
            
            // Set active tab
            $('.ai-review-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
        });
    }

    // Initialize review editor
    function initReviewEditor() {
        // Toggle edit mode
        $('.ai-review-edit-toggle').on('click', function(e) {
            e.preventDefault();
            $(this).closest('.ai-review-item').find('.ai-review-edit-form').toggle();
        });
        
        // Cancel edit button
        $('.ai-review-edit-cancel').on('click', function(e) {
            e.preventDefault();
            $(this).closest('.ai-review-edit-form').hide();
        });
        
        // Handle edit form submission
        $('.ai-review-edit-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const $cancelBtn = $form.find('.ai-review-edit-cancel');
            const $status = $form.find('.ai-review-edit-status');
            
            // Disable buttons during submission
            $submitBtn.prop('disabled', true);
            $cancelBtn.prop('disabled', true);
            
            $status.html('Saving...')
                   .removeClass('success error')
                   .show();
            
            $.ajax({
                url: ai_review_generator.ajax_url,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        $status.html('Saved successfully!').addClass('success');
                        
                        // Refresh after a delay
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $status.html('Error: ' + response.data.message).addClass('error');
                        // Re-enable buttons
                        $submitBtn.prop('disabled', false);
                        $cancelBtn.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    $status.html('Error: ' + (error || 'Unknown error occurred')).addClass('error');
                    // Re-enable buttons
                    $submitBtn.prop('disabled', false);
                    $cancelBtn.prop('disabled', false);
                }
            });
        });
    }

    // Document ready handler
    $(document).ready(function() {
        // Initialize color pickers
        initColorPickers();
        
        // Initialize AI model fields visibility
        initAIModelFields();
        
        // AI model change handler
        $('#ai_model_select').on('change', function() {
            initAIModelFields();
        });
        
        // Initialize temperature slider
        initTemperatureSlider();
        
        // Initialize tabs
        initTabs();
        
        // Initialize review editor
        initReviewEditor();
        
        // Generate review button handler
        $('#ai-review-generate-btn').on('click', function() {
            const postId = $(this).data('post-id');
            generateReview(postId, false);
        });
        
        // Regenerate review button handler
        $('#ai-review-regenerate-btn').on('click', function() {
            const postId = $(this).data('post-id');
            
            if (confirm(ai_review_generator.strings.confirm_regenerate)) {
                generateReview(postId, true);
            }
        });
        
        // Test connection button handler
        $('.test-connection-btn').on('click', function() {
            const modelId = $(this).data('model-id');
            testAIConnection(modelId);
        });
        
        // Expandable review content
        $('.ai-review-expand-toggle').on('click', function(e) {
            e.preventDefault();
            
            const $content = $(this).siblings('.ai-review-full-content');
            
            $content.toggleClass('expanded');
            
            if ($content.hasClass('expanded')) {
                $(this).text('Show Less');
            } else {
                $(this).text('Show More');
            }
        });
    });

})(jQuery);