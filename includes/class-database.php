<?php
/**
 * Database operations for the plugin.
 *
 * @since      1.0.0
 * @package    AI_Review_Generator
 * @subpackage AI_Review_Generator/includes
 */

class AI_Review_Generator_Database {

    /**
     * Table name for storing reviews
     *
     * @var string
     */
    private $table_name;

    /**
     * Initialize the class
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ai_reviews';
    }

    /**
     * Create the plugin database tables
     *
     * @return void
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            rating tinyint(1) NOT NULL,
            review_content longtext NOT NULL,
            review_summary text NOT NULL,
            review_pros text,
            review_cons text,
            reviewer_name varchar(100) DEFAULT NULL,
            generated_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            ai_model varchar(100) NOT NULL,
            modified_by_user tinyint(1) DEFAULT 0 NOT NULL,
            published tinyint(1) DEFAULT 1 NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Save a review to the database
     *
     * @param array $review_data Review data to save
     * @return int|false The ID of the inserted review or false on failure
     */
    public function save_review($review_data) {
        global $wpdb;
        
        // Check if review already exists for this post
        $existing_review = $this->get_review_by_post_id($review_data['post_id']);
        
        if ($existing_review) {
            // Update existing review
            $updated = $wpdb->update(
                $this->table_name,
                [
                    'rating' => $review_data['rating'],
                    'review_content' => $review_data['review_content'],
                    'review_summary' => $review_data['review_summary'],
                    'review_pros' => isset($review_data['review_pros']) ? $review_data['review_pros'] : '',
                    'review_cons' => isset($review_data['review_cons']) ? $review_data['review_cons'] : '',
                    'reviewer_name' => isset($review_data['reviewer_name']) ? $review_data['reviewer_name'] : null,
                    'ai_model' => $review_data['ai_model'],
                    'modified_by_user' => isset($review_data['modified_by_user']) ? $review_data['modified_by_user'] : 0,
                    'published' => isset($review_data['published']) ? $review_data['published'] : 1,
                ],
                ['id' => $existing_review->id],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'],
                ['%d']
            );
            
            return $updated ? $existing_review->id : false;
        } else {
            // Insert new review
            $wpdb->insert(
                $this->table_name,
                [
                    'post_id' => $review_data['post_id'],
                    'rating' => $review_data['rating'],
                    'review_content' => $review_data['review_content'],
                    'review_summary' => $review_data['review_summary'],
                    'review_pros' => isset($review_data['review_pros']) ? $review_data['review_pros'] : '',
                    'review_cons' => isset($review_data['review_cons']) ? $review_data['review_cons'] : '',
                    'reviewer_name' => isset($review_data['reviewer_name']) ? $review_data['reviewer_name'] : null,
                    'ai_model' => $review_data['ai_model'],
                    'modified_by_user' => isset($review_data['modified_by_user']) ? $review_data['modified_by_user'] : 0,
                    'published' => isset($review_data['published']) ? $review_data['published'] : 1,
                ],
                ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d']
            );
            
            return $wpdb->insert_id;
        }
    }

    /**
     * Get all reviews for a post
     *
     * @param int $post_id Post ID
     * @param array $args Query arguments
     * @return array Array of review objects
     */
    public function get_reviews_by_post_id($post_id, $args = []) {
        global $wpdb;
        
        $defaults = [
            'orderby' => 'generated_date',
            'order' => 'DESC',
            'published' => null,
            'limit' => 0,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT * FROM {$this->table_name} WHERE post_id = %d";
        $prepare = [$post_id];
        
        if (isset($args['published'])) {
            $sql .= " AND published = %d";
            $prepare[] = $args['published'];
        }
        
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d";
            $prepare[] = $args['limit'];
        }
        
        if (empty($prepare)) {
            $query = $sql;
        } else {
            $query = $wpdb->prepare($sql, $prepare);
        }
        
        $results = $wpdb->get_results($query);
        
        return $results ?: [];
    }
    
    /**
     * Get review by post ID
     *
     * @param int $post_id Post ID
     * @return object|null Review object or null if not found
     */
    public function get_review_by_post_id($post_id) {
        // For backward compatibility, get the first review
        $reviews = $this->get_reviews_by_post_id($post_id, ['limit' => 1]);
        return !empty($reviews) ? $reviews[0] : null;
    }

    /**
     * Get a review by review ID
     *
     * @param int $review_id Review ID
     * @return object|null Review object or null if not found
     */
    public function get_review_by_id($review_id) {
        global $wpdb;
        
        $review = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $this->table_name WHERE id = %d",
                $review_id
            )
        );
        
        return $review;
    }

    /**
     * Get all reviews
     *
     * @param array $args Optional. Query arguments.
     * @return array Array of review objects
     */
    public function get_reviews($args = []) {
        global $wpdb;
        
        $defaults = [
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'generated_date',
            'order' => 'DESC',
            'published' => null,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT * FROM $this->table_name";
        $where = [];
        $prepare = [];
        
        if (isset($args['published'])) {
            $where[] = "published = %d";
            $prepare[] = $args['published'];
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        $sql .= " LIMIT %d OFFSET %d";
        
        $prepare[] = $args['limit'];
        $prepare[] = $args['offset'];
        
        $query = $wpdb->prepare($sql, $prepare);
        
        return $wpdb->get_results($query);
    }

    /**
     * Delete a review
     *
     * @param int $review_id Review ID
     * @return bool True on success, false on failure
     */
    public function delete_review($review_id) {
        global $wpdb;
        
        $deleted = $wpdb->delete(
            $this->table_name,
            ['id' => $review_id],
            ['%d']
        );
        
        return $deleted !== false;
    }

    /**
     * Delete all reviews for a post
     *
     * @param int $post_id Post ID
     * @return bool True on success, false on failure
     */
    public function delete_reviews_by_post_id($post_id) {
        global $wpdb;
        
        $deleted = $wpdb->delete(
            $this->table_name,
            ['post_id' => $post_id],
            ['%d']
        );
        
        return $deleted !== false;
    }

    /**
     * Count reviews
     *
     * @param array $args Optional. Query arguments.
     * @return int Number of reviews
     */
    public function count_reviews($args = []) {
        global $wpdb;
        
        $defaults = [
            'published' => null,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT COUNT(*) FROM $this->table_name";
        $where = [];
        $prepare = [];
        
        if (isset($args['published'])) {
            $where[] = "published = %d";
            $prepare[] = $args['published'];
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        if (!empty($prepare)) {
            $query = $wpdb->prepare($sql, $prepare);
        } else {
            $query = $sql;
        }
        
        return (int) $wpdb->get_var($query);
    }

    /**
     * Log API usage
     *
     * @param array $log_data Log data
     * @return int|false The ID of the inserted log or false on failure
     */
    public function log_api_usage($log_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_review_logs';
        
        $wpdb->insert(
            $table_name,
            [
                'post_id' => $log_data['post_id'],
                'ai_model' => $log_data['ai_model'],
                'tokens_used' => $log_data['tokens_used'],
                'request_time' => isset($log_data['request_time']) ? $log_data['request_time'] : current_time('mysql'),
                'status' => $log_data['status'],
                'error_message' => isset($log_data['error_message']) ? $log_data['error_message'] : '',
            ],
            ['%d', '%s', '%d', '%s', '%s', '%s']
        );
        
        return $wpdb->insert_id;
    }

    /**
     * Create logs table
     *
     * @return void
     */
    public function create_logs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_review_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            ai_model varchar(100) NOT NULL,
            tokens_used int(11) NOT NULL DEFAULT 0,
            request_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            status varchar(50) NOT NULL,
            error_message text,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}