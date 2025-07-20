<?php
/**
 * MarketingHubApi class
 * Handles the custom REST API endpoint for MarketingHub
 */
class MarketingHubApi {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('rest_api_init', [$this, 'enable_cors'], 15);
    }

    public function register_routes() {
        register_rest_route('api/v1', '/marketinghub', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_request'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_request($request) {
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Hello from MarketingHub API!'
        ], 200);
    }

    public function enable_cors() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }
}

global $marketingHubApi;
$marketingHubApi = new MarketingHubApi(); 