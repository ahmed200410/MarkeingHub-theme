<?php
/**
 * ThemeOptionsApi class
 * REST API for reading and updating translations.json in the theme root
 */
class ThemeOptionsApi {
    private $file_path;

    public function __construct() {
        $this->file_path = get_template_directory() . '/translations.json';
        add_action('rest_api_init', [ $this, 'register_routes' ]);
    }

    public function register_routes() {
        register_rest_route('marketinghub/v1', '/themeoptions', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_translations' ],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('marketinghub/v1', '/themeoptions', [
            'methods' => 'POST',
            'callback' => [ $this, 'update_translations' ],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route('marketinghub/v1', '/logo', [
            'methods' => ['GET', 'POST'],
            'callback' => [ $this, 'logo_endpoint_callback' ],
            'permission_callback' => function() {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    return current_user_can('manage_options');
                }
                return true;
            },
        ]);
    }

    public function get_translations() {
        if (!file_exists($this->file_path)) {
            return new WP_Error('file_not_found', 'translations.json not found', [ 'status' => 404 ]);
        }
        $json = file_get_contents($this->file_path);
        $data = json_decode($json, true);
        if ($data === null) {
            return new WP_Error('invalid_json', 'Invalid JSON in translations.json', [ 'status' => 500 ]);
        }
        return $data;
    }

    public function update_translations($request) {
        $body = $request->get_body();
        $data = json_decode($body, true);
        if ($data === null) {
            return new WP_Error('invalid_json', 'Invalid JSON in request body', [ 'status' => 400 ]);
        }
        $result = file_put_contents($this->file_path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($result === false) {
            return new WP_Error('write_error', 'Failed to write translations.json', [ 'status' => 500 ]);
        }
        return [ 'success' => true ];
    }

    public function logo_endpoint_callback($request) {
        if ($request->get_method() === 'GET') {
            $logo_id = get_option('marketinghub_logo_id');
            $url = $logo_id ? wp_get_attachment_url($logo_id) : null;
            if (!$url) {
                // Default logo in theme
                $url = get_template_directory_uri() . '/assets/images/logo-OR.svg';
            }
            return [ 'id' => $logo_id, 'url' => $url ];
        }
        if ($request->get_method() === 'POST') {
            if (!current_user_can('manage_options')) {
                return new WP_Error('forbidden', 'You do not have permission.', [ 'status' => 403 ]);
            }
            if (empty($_FILES['logo'])) {
                return new WP_Error('no_file', 'No file uploaded.', [ 'status' => 400 ]);
            }
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_id = media_handle_upload('logo', 0);
            if (is_wp_error($attachment_id)) {
                return $attachment_id;
            }
            update_option('marketinghub_logo_id', $attachment_id);
            $url = wp_get_attachment_url($attachment_id);
            return [ 'id' => $attachment_id, 'url' => $url ];
        }
        return new WP_Error('invalid_method', 'Invalid request method.', [ 'status' => 405 ]);
    }
}




global $themeOptionsApi;
$themeOptionsApi = new ThemeOptionsApi();

