<?php
/**
 * MarketingHub Theme Functions
 * Organized, OOP-based, and uses absolute paths for includes.
 * All theme setup and includes are managed by the MarketingHubTheme class.
 */

if (!defined('ABSPATH')) {
    exit; 
}

class MarketingHubTheme {
    public function __construct() {
        $this->define_constants();
        $this->setup_theme_features();
        $this->autoload_inc_files();
    }

    private function define_constants() {
        if (!defined('MH_THEME_DIR')) {
            define('MH_THEME_DIR', get_template_directory());
        }
        if (!defined('MH_THEME_URI')) {
            define('MH_THEME_URI', get_template_directory_uri());
        }
        if (!defined('MH_INC_DIR')) {
            define('MH_INC_DIR', MH_THEME_DIR . '/inc');
        }
        if (!defined('MH_BUILD_URI')) {
            define('MH_BUILD_URI', MH_THEME_URI . '/build');
        }
    }

    private function setup_theme_features() {
        add_action('after_setup_theme', function() {
            add_theme_support('title-tag');
            add_theme_support('post-thumbnails');
            add_theme_support('menus');
            add_theme_support('html5', [
                'search-form', 'comment-form', 'comment-list', 'gallery', 'caption'
            ]);
        });
    }

    private function autoload_inc_files() {
        if (is_dir(MH_INC_DIR)) {
            foreach (glob(MH_INC_DIR . '/*.php') as $file) {
                require_once $file;
            }
        }
    }
}

// Initialize the theme
new MarketingHubTheme();


