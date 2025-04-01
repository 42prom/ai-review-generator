<?php
/**
 * AI Models integrations.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 * @subpackage AI_Review_Generator/includes
 */

class AI_Review_Generator_AI_Models {

    /**
     * API instance
     *
     * @var AI_Review_Generator_API
     */
    private $api;
    
    /**
     * Database instance
     *
     * @var AI_Review_Generator_Database
     */
    private $db;
    
    /**
     * Plugin settings
     *
     * @var array
     */
    private $settings;

    /**
     * Initialize the class
     */
    public function __construct() {
        $this->api = new AI_Review_Generator_API();
        $this->db = new AI_Review_Generator_Database();
        $this->settings = get_option('ai_review_generator_settings', []);
    }

    /**
     * Update the get_available_models() method to include the latest endpoints
     */
    public function get_available_models() {
        return [
            'deepseek' => [
                'name' => 'DeepSeek AI',
                'description' => __('Free AI model with good quality results', 'ai-review-generator'),
                'default_endpoint' => 'https://api.deepseek.com/v1/chat/completions',
                'model_names' => [
                    'deepseek-chat' => 'DeepSeek Chat',
                    'deepseek-coder' => 'DeepSeek Coder',
                ],
                'default_model' => 'deepseek-chat',
                'requires_key' => true,
                'free_tier' => true,
            ],
            'mistral' => [
                'name' => 'Mistral AI',
                'description' => __('High-quality open source model', 'ai-review-generator'),
                'default_endpoint' => 'https://api.mistral.ai/v1/chat/completions',
                'model_names' => [
                    'mistral-tiny' => 'Mistral Tiny (7B)',
                    'mistral-small' => 'Mistral Small (8x7B)',
                    'mistral-medium' => 'Mistral Medium',
                ],
                'default_model' => 'mistral-tiny',
                'requires_key' => true,
                'free_tier' => false,
            ],
            'llama3' => [
                'name' => 'LLaMA 3 (Meta AI)',
                'description' => __('Meta\'s open source model, self-hosted option available', 'ai-review-generator'),
                'default_endpoint' => 'https://api.together.xyz/v1/chat/completions', // Updated endpoint for Together.ai
                'model_names' => [
                    'meta-llama/Llama-3-8b-chat' => 'LLaMA 3 (8B)',
                    'meta-llama/Llama-3-70b-chat' => 'LLaMA 3 (70B)',
                ],
                'default_model' => 'meta-llama/Llama-3-8b-chat',
                'requires_key' => true,
                'free_tier' => false,
                'self_hosted' => true,
            ],
            'openrouter' => [
                'name' => 'OpenRouter',
                'description' => __('Multi-model API with various options', 'ai-review-generator'),
                'default_endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
                'model_names' => [
                    'openai/gpt-3.5-turbo' => 'OpenAI GPT-3.5 Turbo',
                    'anthropic/claude-instant-v1' => 'Anthropic Claude Instant',
                    'google/palm-2-chat-bison' => 'Google PaLM 2',
                    'meta-llama/llama-3-8b-chat' => 'Meta LLaMA 3 (8B)',
                ],
                'default_model' => 'meta-llama/llama-3-8b-chat',
                'requires_key' => true,
                'free_tier' => true,
            ],
        ];
    }

    /**
     * Get currently selected AI model
     *
     * @return string Current model ID
     */
    public function get_current_model() {
        return isset($this->settings['ai_model']) ? $this->settings['ai_model'] : 'deepseek';
    }

    /**
     * Get model details by ID
     *
     * @param string $model_id Model ID
     * @return array|null Model details or null if not found
     */
    public function get_model_details($model_id = '') {
        $models = $this->get_available_models();
        
        if (empty($model_id)) {
            $model_id = $this->get_current_model();
        }
        
        return isset($models[$model_id]) ? $models[$model_id] : null;
    }

    /**
     * Generate a review using AI
     *
     * @param array $content_data Content data for generating the review
     * @return array|WP_Error Review data or error
     */
    public function generate_review($content_data) {
        $model_id = $this->get_current_model();
        $model = $this->get_model_details($model_id);
        
        if (!$model) {
            return new WP_Error('invalid_model', __('Invalid AI model selected', 'ai-review-generator'));
        }
        
        $api_key = isset($this->settings[$model_id . '_api_key']) ? $this->settings[$model_id . '_api_key'] : '';
        
        if ($model['requires_key'] && empty($api_key)) {
            return new WP_Error('missing_api_key', __('API key is required for this model', 'ai-review-generator'));
        }
        
        $endpoint = isset($this->settings[$model_id . '_endpoint']) ? $this->settings[$model_id . '_endpoint'] : $model['default_endpoint'];
        $model_name = isset($this->settings[$model_id . '_model_name']) ? $this->settings[$model_id . '_model_name'] : $model['default_model'];
        
        // Build prompt based on settings
        $prompt = $this->build_review_prompt($content_data);
        
        // Get temperature from settings
        $temperature = isset($this->settings['ai_temperature']) ? floatval($this->settings['ai_temperature']) : 0.7;
        
        // Prepare request data
        $request_data = $this->prepare_request_data($model_id, $model_name, $prompt, $temperature);
        
        // Make API request
        $start_time = microtime(true);
        $response = $this->api->make_request($endpoint, $request_data, [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ]);
        $request_time = microtime(true) - $start_time;
        
        // Log the API usage
        $log_data = [
            'post_id' => $content_data['post_id'],
            'ai_model' => $model_id . '/' . $model_name,
            'tokens_used' => 0, // Will be updated if available in response
            'request_time' => date('Y-m-d H:i:s'),
            'status' => is_wp_error($response) ? 'error' : 'success',
            'error_message' => is_wp_error($response) ? $response->get_error_message() : '',
        ];
        
        if (is_wp_error($response)) {
            $this->db->log_api_usage($log_data);
            return $response;
        }
        
        // Parse the response
        $review_data = $this->parse_review_response($model_id, $response);
        
        if (is_wp_error($review_data)) {
            $log_data['status'] = 'error';
            $log_data['error_message'] = $review_data->get_error_message();
            $this->db->log_api_usage($log_data);
            return $review_data;
        }
        
        // Update tokens if available
        if (isset($response['usage']) && isset($response['usage']['total_tokens'])) {
            $log_data['tokens_used'] = $response['usage']['total_tokens'];
        }
        
        $this->db->log_api_usage($log_data);
        
        return $review_data;
    }

    /**
     * Build the review prompt based on content data and settings
     *
     * @param array $content_data Content data
     * @return string Generated prompt
     */
    private function build_review_prompt($content_data) {
        $tone = isset($this->settings['review_tone']) ? $this->settings['review_tone'] : 'professional';
        $min_words = isset($this->settings['min_word_count']) ? intval($this->settings['min_word_count']) : 200;
        $max_words = isset($this->settings['max_word_count']) ? intval($this->settings['max_word_count']) : 500;
        $structure = isset($this->settings['review_structure']) ? $this->settings['review_structure'] : 'pros_cons';
        
        $title = $content_data['title'];
        $content = isset($content_data['content']) ? $content_data['content'] : '';
        $excerpt = isset($content_data['excerpt']) ? $content_data['excerpt'] : '';
        $is_product = isset($content_data['is_product']) ? $content_data['is_product'] : false;
        
        // Product-specific data
        $price = isset($content_data['price']) ? $content_data['price'] : '';
        $categories = isset($content_data['categories']) ? $content_data['categories'] : [];
        $attributes = isset($content_data['attributes']) ? $content_data['attributes'] : [];
        
        // Build the prompt
        $prompt = "You are a professional reviewer who creates honest, thoughtful reviews. ";
        
        if ($is_product) {
            $prompt .= "Create a product review for the following product:\n\n";
            $prompt .= "Product Name: $title\n";
            
            if (!empty($price)) {
                $prompt .= "Price: $price\n";
            }
            
            if (!empty($categories)) {
                $prompt .= "Categories: " . implode(', ', $categories) . "\n";
            }
            
            if (!empty($attributes)) {
                $prompt .= "Product Attributes:\n";
                foreach ($attributes as $attr_name => $attr_value) {
                    $prompt .= "- $attr_name: $attr_value\n";
                }
            }
            
            if (!empty($content)) {
                $prompt .= "\nProduct Description:\n$content\n";
            }
        } else {
            $prompt .= "Create a review for the following content:\n\n";
            $prompt .= "Title: $title\n";
            
            if (!empty($excerpt)) {
                $prompt .= "Excerpt: $excerpt\n";
            }
            
            if (!empty($content)) {
                $prompt .= "\nContent:\n$content\n";
            }
        }
        
        // Add tone instructions
        $prompt .= "\nUse a $tone tone in your review.\n";
        
        // Add word count instructions
        $prompt .= "The review should be between $min_words and $max_words words.\n";
        
        // Add structure instructions
        if ($structure === 'pros_cons') {
            $prompt .= "Include the following sections in your review:\n";
            $prompt .= "1. A brief summary of the " . ($is_product ? "product" : "content") . "\n";
            $prompt .= "2. Pros (at least 3 points)\n";
            $prompt .= "3. Cons (at least 2 points)\n";
            $prompt .= "4. Final verdict with a rating from 1 to 5 stars\n";
        } elseif ($structure === 'detailed') {
            $prompt .= "Create a detailed review with the following sections:\n";
            $prompt .= "1. Introduction\n";
            $prompt .= "2. Main features and analysis\n";
            $prompt .= "3. Benefits\n";
            $prompt .= "4. Drawbacks\n";
            $prompt .= "5. Conclusion with a rating from 1 to 5 stars\n";
        } elseif ($structure === 'concise') {
            $prompt .= "Create a concise review with a clear summary, key points, and a rating from 1 to 5 stars.\n";
        }
        
        $prompt .= "\nFormat your response in JSON with the following structure:
{
    \"summary\": \"A brief summary of the " . ($is_product ? "product" : "content") . "\",
    \"full_review\": \"The complete review text with sections properly formatted\",
    \"pros\": [\"Pro 1\", \"Pro 2\", \"Pro 3\"],
    \"cons\": [\"Con 1\", \"Con 2\"],
    \"rating\": 4.5
}";
        
        return $prompt;
    }

    /**
     * Prepare request data for the API
     *
     * @param string $model_id Model ID
     * @param string $model_name Model name
     * @param string $prompt Prompt
     * @param float $temperature Temperature
     * @return array Request data
     */
    private function prepare_request_data($model_id, $model_name, $prompt, $temperature) {
        $request_data = [];
        
        // Base format for most models
        if ($model_id === 'deepseek') {
            $request_data = [
                'model' => $model_name,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => $temperature,
                'max_tokens' => 2048,
                'stream' => false
            ];
        } elseif ($model_id === 'mistral') {
            $request_data = [
                'model' => $model_name,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => $temperature,
                'max_tokens' => 2048,
                'stream' => false
            ];
        } elseif ($model_id === 'llama3') {
            $request_data = [
                'model' => $model_name,
                'prompt' => $prompt,
                'temperature' => $temperature,
                'max_tokens' => 2048,
            ];
        } elseif ($model_id === 'openrouter') {
            $request_data = [
                'model' => $model_name,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => $temperature,
                'max_tokens' => 2048,
            ];
        }
        
        return $request_data;
    }

    /**
     * Parse the API response to extract review data
     *
     * @param string $model_id Model ID
     * @param array $response API response
     * @return array|WP_Error Parsed review data or error
     */
    private function parse_review_response($model_id, $response) {
        // Verify response is valid
        if (empty($response) || !is_array($response)) {
            return new WP_Error('empty_response', __('Empty or invalid response received from AI model', 'ai-review-generator'));
        }
        
        $content = '';
        
        // Extract content based on model
        if ($model_id === 'deepseek' || $model_id === 'mistral' || $model_id === 'openrouter') {
            if (isset($response['choices']) && is_array($response['choices']) && !empty($response['choices'])) {
                if (isset($response['choices'][0]['message']) && isset($response['choices'][0]['message']['content'])) {
                    $content = $response['choices'][0]['message']['content'];
                } else if (isset($response['choices'][0]['content'])) {
                    // Alternative format some APIs use
                    $content = $response['choices'][0]['content'];
                }
            }
        } elseif ($model_id === 'llama3') {
            // Together.ai changed their API to match OpenAI format
            if (isset($response['choices']) && is_array($response['choices']) && !empty($response['choices'])) {
                if (isset($response['choices'][0]['message']) && isset($response['choices'][0]['message']['content'])) {
                    $content = $response['choices'][0]['message']['content'];
                } else if (isset($response['choices'][0]['text'])) {
                    // Fallback to older format
                    $content = $response['choices'][0]['text'];
                }
            }
        }
        
        if (empty($content)) {
            // Log the full response for debugging
            error_log('AI Review Generator: Empty content from response: ' . wp_json_encode($response));
            return new WP_Error('invalid_response', __('Could not extract content from AI model response', 'ai-review-generator'));
        }
        
        // Try to extract JSON from the response
        $json_start = strpos($content, '{');
        $json_end = strrpos($content, '}');
        
        if ($json_start !== false && $json_end !== false) {
            $json_content = substr($content, $json_start, $json_end - $json_start + 1);
            $json_data = json_decode($json_content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // Successfully parsed JSON
                $review_data = [
                    'summary' => isset($json_data['summary']) ? $json_data['summary'] : '',
                    'full_review' => isset($json_data['full_review']) ? $json_data['full_review'] : '',
                    'pros' => isset($json_data['pros']) ? $json_data['pros'] : [],
                    'cons' => isset($json_data['cons']) ? $json_data['cons'] : [],
                    'rating' => isset($json_data['rating']) ? floatval($json_data['rating']) : 0,
                ];
                
                return $review_data;
            }
        }
        
        // If JSON parsing failed, try to extract data manually
        $lines = explode("\n", $content);
        $review_data = [
            'summary' => '',
            'full_review' => $content,
            'pros' => [],
            'cons' => [],
            'rating' => 0,
        ];
        
        // Try to find rating
        foreach ($lines as $line) {
            if (preg_match('/rating:?\s*(\d+(?:\.\d+)?)/i', $line, $matches)) {
                $review_data['rating'] = floatval($matches[1]);
                break;
            }
        }
        
        // If we couldn't find a rating, estimate one based on sentiment
        if ($review_data['rating'] === 0) {
            // Default to middle rating
            $review_data['rating'] = 3.5;
        }
        
        return $review_data;
    }

    /**
     * Test API connection
     *
     * @param string $model_id Model ID
     * @return true|WP_Error True on success or error
     */
    public function test_connection($model_id = '') {
        if (empty($model_id)) {
            $model_id = $this->get_current_model();
        }
        
        $model = $this->get_model_details($model_id);
        
        if (!$model) {
            return new WP_Error('invalid_model', __('Invalid AI model selected', 'ai-review-generator'));
        }
        
        $api_key = isset($this->settings[$model_id . '_api_key']) ? $this->settings[$model_id . '_api_key'] : '';
        
        if ($model['requires_key'] && empty($api_key)) {
            return new WP_Error('missing_api_key', __('API key is required for this model', 'ai-review-generator'));
        }
        
        $endpoint = isset($this->settings[$model_id . '_endpoint']) ? $this->settings[$model_id . '_endpoint'] : $model['default_endpoint'];
        $model_name = isset($this->settings[$model_id . '_model_name']) ? $this->settings[$model_id . '_model_name'] : $model['default_model'];
        
        // Simple test prompt
        $prompt = "Hello, this is a connection test.";
        
        // Prepare request data
        $request_data = $this->prepare_request_data($model_id, $model_name, $prompt, 0.7);
        
        // Limit tokens for test
        if (isset($request_data['max_tokens'])) {
            $request_data['max_tokens'] = 20; // Minimal tokens for test
        }

        // Make API request with timeout handling
        try {
            $response = $this->api->make_request(
                $endpoint, 
                $request_data, 
                [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ],
                'POST',
                true // Force fresh request without caching
            );
            
            if (is_wp_error($response)) {
                return $response;
            }
            
            // Just check for basic structure rather than content
            if (empty($response) || !is_array($response) || !isset($response['choices']) || !is_array($response['choices'])) {
                return new WP_Error('invalid_response', __('Invalid response structure from AI model', 'ai-review-generator'));
            }
            
            return true;
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage());
        }
        
        return true;
    }
}