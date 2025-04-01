/**
 * Public JavaScript
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 */

(function($) {
    'use strict';

    // Initialize dynamic elements
    function initDynamicElements() {
        // Handle review expandable content
        $('.ai-review-expandable').each(function() {
            const $content = $(this);
            const originalHeight = $content.height();
            const maxHeight = 200; // Maximum height before collapsing
            
            if (originalHeight > maxHeight) {
                $content.css('max-height', maxHeight + 'px');
                $content.addClass('collapsed');
                
                // Add expand/collapse toggle
                const $toggle = $('<a href="#" class="ai-review-expand-toggle">' + aiReviewPublic.expandText + '</a>');
                $content.after($toggle);
                
                // Toggle handler
                $toggle.on('click', function(e) {
                    e.preventDefault();
                    
                    if ($content.hasClass('collapsed')) {
                        $content.css('max-height', originalHeight + 'px');
                        $content.removeClass('collapsed');
                        $(this).text(aiReviewPublic.collapseText);
                    } else {
                        $content.css('max-height', maxHeight + 'px');
                        $content.addClass('collapsed');
                        $(this).text(aiReviewPublic.expandText);
                    }
                });
            }
        });
    }

    // Detect dark mode changes
    function detectDarkMode() {
        const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        // Add class based on current preference
        if (darkModeMediaQuery.matches) {
            $('body').addClass('ai-review-dark-mode');
        } else {
            $('body').removeClass('ai-review-dark-mode');
        }
        
        // Listen for changes
        darkModeMediaQuery.addEventListener('change', function(e) {
            if (e.matches) {
                $('body').addClass('ai-review-dark-mode');
            } else {
                $('body').removeClass('ai-review-dark-mode');
            }
        });
    }

    // Document ready handler
    $(document).ready(function() {
        // Initialize dynamic elements
        initDynamicElements();
        
        // Detect dark mode
        detectDarkMode();
    });

})(jQuery);