<?php
/**
 * ReactAssetsLoader class
 * Loads only the main React build assets (JS/CSS) from app/dist/assets
 */
class ReactAssetsLoader {
    public static function enqueue_scripts() {
        $assets_dir = get_template_directory() . '/dist/assets';
        $assets_uri = get_template_directory_uri() . '/dist/assets';

        $js_files = glob($assets_dir . '/index-*.js');
        $main_js_file = null;
        foreach ($js_files as $js_file) {
            $filename = basename($js_file);
            if (strpos($filename, 'index-') === 0 && strpos($filename, '.js') !== false) {
                $main_js_file = $filename;
                break;
            }
        }
        if ($main_js_file) {
            $handle = 'marketinghub-react-app';
            wp_enqueue_script(
                $handle,
                $assets_uri . '/' . $main_js_file,
                array(),
                null,
                true
            );
            add_filter('script_loader_tag', function($tag, $handle_filter) use ($handle) {
                if ($handle_filter === $handle) {
                    return str_replace('<script ', '<script type="module" ', $tag);
                }
                return $tag;
            }, 10, 2);
        }

        $css_files = glob($assets_dir . '/index-*.css');
        $main_css_file = null;
        foreach ($css_files as $css_file) {
            $filename = basename($css_file);
            if (strpos($filename, 'index-') === 0 && strpos($filename, '.css') !== false) {
                $main_css_file = $filename;
                break;
            }
        }
        if ($main_css_file) {
            wp_enqueue_style(
                'marketinghub-react-app',
                $assets_uri . '/' . $main_css_file,
                array(),
                null
            );
        }
    }
}
add_action('wp_enqueue_scripts', ['ReactAssetsLoader', 'enqueue_scripts']);



global $reactAssetsLoader;
$reactAssetsLoader = new ReactAssetsLoader(); 