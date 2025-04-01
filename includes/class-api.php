<?php
/**
 * API handling for external services.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 * @subpackage AI_Review_Generator/includes
 */

class AI_Review_Generator_API {

    /**
     * Cache expiration time in seconds
     *
     * @var int
     */
    private $cache_expiration;

    /**
     * Initialize the class
     */
    public function __construct() {
        $settings = get_option('ai_review_generator_settings', []);
        $this->cache_expiration = isset($settings['cache_expiration']) ? intval($settings['cache_expiration']) : 86400; // Default: 1 day
    }

    /**
     * Make an API request
     *
     * @param string $endpoint API endpoint URL
     * @param array $data Request data
     * @param array $headers Request headers
     * @param string $method Request method (GET, POST, etc.)
     * @return array|WP_Error Response data or error
     */
    public function make_request($endpoint, $data = [], $headers = [], $method = 'POST') {
        // Check if response is cached
        $cache_key = $this->get_cache_key($endpoint, $data);
        $cached_response = $this->get_cached_response($cache_key);
        
        if ($cached_response !== false) {
            return $cached_response;
        }
        
        // Prepare request arguments
        $args = [
            'method'      => $method,
            'timeout'     => 90, // Longer timeout
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking'    => true,
            'headers'     => $headers,
            'body'        => $method === 'GET' ? null : wp_json_encode($data),
            'cookies'     => [],
            'sslverify'   => true,
        ];

        // Add response validation
        if (is_wp_error($response)) {
            error_log('AI Review Generator API Error: ' . $response->get_error_message());
            return $response;
        }
        
        // Add query parameters for GET requests
        if ($method === 'GET' && !empty($data)) {
            $endpoint = add_query_arg($data, $endpoint);
        }
        
        // Make the request
        $response = wp_remote_request($endpoint, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code >= 400) {
            $body = wp_remote_retrieve_body($response);
            $error_data = json_decode($body, true);
            $error_message = '';
            
            // Extract detailed error message based on API provider format
            if (isset($error_data['error'])) {
                if (is_string($error_data['error'])) {
                    $error_message = $error_data['error'];
                } elseif (isset($error_data['error']['message'])) {
                    $error_message = $error_data['error']['message'];
                }
            }
            
            if (empty($error_message)) {
                $error_message = sprintf(__('HTTP Error: %d', 'ai-review-generator'), $response_code);
            }
            
            return new WP_Error('api_error', $error_message, [
                'status' => $response_code,
                'response' => $error_data,
            ]);
        }
        
        // Parse response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Cache the response
        $this->cache_response($cache_key, $data);
        
        return $data;
    }

    /**
     * Get cache key for request
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return string Cache key
     */
    private function get_cache_key($endpoint, $data) {
        return 'ai_review_' . md5($endpoint . serialize($data));
    }

    /**
     * Get cached response
     *
     * @param string $cache_key Cache key
     * @return array|false Cached response or false if not found
     */
    private function get_cached_response($cache_key) {
        // Skip caching for development/testing
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return false;
        }
        
        return get_transient($cache_key);
    }

    /**
     * Cache response
     *
     * @param string $cache_key Cache key
     * @param array $data Response data
     * @return bool True on success, false on failure
     */
    private function cache_response($cache_key, $data) {
        // Skip caching for development/testing
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return false;
        }
        
        return set_transient($cache_key, $data, $this->cache_expiration);
    }

    /**
     * Clear API cache
     *
     * @param string $endpoint Optional. Specific endpoint to clear
     * @return void
     */
    public function clear_cache($endpoint = '') {
        global $wpdb;
        
        if (!empty($endpoint)) {
            // Clear cache for specific endpoint
            $cache_key = $this->get_cache_key($endpoint, []);
            delete_transient($cache_key);
        } else {
            // Clear all API cache
            $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_ai_review_%' OR option_name LIKE '_transient_timeout_ai_review_%'");
        }
    }

    /**
     * Get response from cache or make a new request
     *
     * @param string $endpoint API endpoint URL
     * @param array $data Request data
     * @param array $headers Request headers
     * @param string $method Request method (GET, POST, etc.)
     * @param bool $force_refresh Whether to force a fresh request
     * @return array|WP_Error Response data or error
     */
    public function get_or_make_request($endpoint, $data = [], $headers = [], $method = 'POST', $force_refresh = false) {
        $cache_key = $this->get_cache_key($endpoint, $data);
        
        if (!$force_refresh) {
            $cached_response = $this->get_cached_response($cache_key);
            
            if ($cached_response !== false) {
                return $cached_response;
            }
        }
        
        return $this->make_request($endpoint, $data, $headers, $method);
    }
}