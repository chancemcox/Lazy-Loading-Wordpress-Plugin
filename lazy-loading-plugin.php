<?php
/**
 * Plugin Name: Lazy Loading Plugin
 * Plugin URI: https://github.com/your-username/lazy-loading-plugin
 * Description: A WordPress plugin that implements lazy loading for images using IntersectionObserver API with fallback support.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: lazy-loading-plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LAZY_LOADING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LAZY_LOADING_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('LAZY_LOADING_PLUGIN_VERSION', '1.0.0');

class LazyLoadingPlugin {

    public function __construct() {
        add_action('init', array($this, 'init'));
    }

    public function init() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add lazy loading to images in content
        add_filter('the_content', array($this, 'add_lazy_loading_to_content'));
        add_filter('post_thumbnail_html', array($this, 'add_lazy_loading_to_thumbnail'), 10, 5);
        
        // Add settings page
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
        }
    }

    public function enqueue_scripts() {
        // Only load on frontend
        if (!is_admin()) {
            wp_enqueue_style(
                'lazy-loading-css',
                LAZY_LOADING_PLUGIN_URL . 'assets/css/lazy-loading.css',
                array(),
                LAZY_LOADING_PLUGIN_VERSION
            );

            wp_enqueue_script(
                'lazy-loading-js',
                LAZY_LOADING_PLUGIN_URL . 'assets/js/lazy-loading.js',
                array(),
                LAZY_LOADING_PLUGIN_VERSION,
                true
            );

            // Pass settings to JavaScript
            $settings = array(
                'threshold' => get_option('lazy_loading_threshold', '0.1'),
                'rootMargin' => get_option('lazy_loading_root_margin', '50px'),
                'placeholder' => get_option('lazy_loading_placeholder', 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect width="100%25" height="100%25" fill="%23f1f1fa"/%3E%3C/svg%3E'),
                'fadeIn' => get_option('lazy_loading_fade_in', '1')
            );

            wp_localize_script('lazy-loading-js', 'lazyLoadingSettings', $settings);
        }
    }

    public function add_lazy_loading_to_content($content) {
        if (!is_admin() && !is_feed()) {
            // Skip if lazy loading is disabled
            if (get_option('lazy_loading_enabled', '1') !== '1') {
                return $content;
            }

            // Process images in content
            $content = preg_replace_callback(
                '/<img([^>]+?)src=[\'"]?([^\'"\s>]+)[\'"]?([^>]*)>/i',
                array($this, 'process_image_tag'),
                $content
            );
        }

        return $content;
    }

    public function add_lazy_loading_to_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (!is_admin() && !is_feed() && get_option('lazy_loading_enabled', '1') === '1') {
            $html = preg_replace_callback(
                '/<img([^>]+?)src=[\'"]?([^\'"\s>]+)[\'"]?([^>]*)>/i',
                array($this, 'process_image_tag'),
                $html
            );
        }

        return $html;
    }

    public function process_image_tag($matches) {
        $img_tag = $matches[0];
        $before_src = $matches[1];
        $src = $matches[2];
        $after_src = $matches[3];

        // Skip if already has lazy class or data-src
        if (strpos($img_tag, 'class="') !== false && strpos($img_tag, 'lazy') !== false) {
            return $img_tag;
        }

        if (strpos($img_tag, 'data-src') !== false) {
            return $img_tag;
        }

        // Skip if it's a base64 image or very small image
        if (strpos($src, 'data:') === 0 || $this->is_small_image($src)) {
            return $img_tag;
        }

        // Get placeholder image
        $placeholder = get_option('lazy_loading_placeholder', 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect width="100%25" height="100%25" fill="%23f1f1fa"/%3E%3C/svg%3E');

        // Add or modify class attribute
        if (strpos($img_tag, 'class=') !== false) {
            $img_tag = preg_replace('/class=[\'"]([^\'"]*)[\'"]/', 'class="$1 lazy"', $img_tag);
        } else {
            $before_src .= ' class="lazy"';
        }

        // Replace src with placeholder and add data-src with original src
        $new_img_tag = '<img' . $before_src . ' src="' . $placeholder . '" data-src="' . $src . '"' . $after_src . '>';

        return $new_img_tag;
    }

    private function is_small_image($src) {
        // Skip very small images (likely icons, tracking pixels, etc.)
        $small_image_patterns = array(
            '/1x1\./',
            '/spacer\./',
            '/pixel\./',
            '/clear\./',
            '/transparent\./'
        );

        foreach ($small_image_patterns as $pattern) {
            if (preg_match($pattern, $src)) {
                return true;
            }
        }

        return false;
    }

    public function add_admin_menu() {
        add_options_page(
            'Lazy Loading Settings',
            'Lazy Loading',
            'manage_options',
            'lazy-loading-settings',
            array($this, 'admin_page')
        );
    }

    public function register_settings() {
        register_setting('lazy_loading_settings', 'lazy_loading_enabled');
        register_setting('lazy_loading_settings', 'lazy_loading_threshold');
        register_setting('lazy_loading_settings', 'lazy_loading_root_margin');
        register_setting('lazy_loading_settings', 'lazy_loading_placeholder');
        register_setting('lazy_loading_settings', 'lazy_loading_fade_in');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Lazy Loading Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('lazy_loading_settings');
                do_settings_sections('lazy_loading_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Lazy Loading</th>
                        <td>
                            <input type="checkbox" name="lazy_loading_enabled" value="1" <?php checked(get_option('lazy_loading_enabled', '1'), '1'); ?> />
                            <p class="description">Enable or disable lazy loading for images.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Intersection Threshold</th>
                        <td>
                            <input type="number" name="lazy_loading_threshold" value="<?php echo esc_attr(get_option('lazy_loading_threshold', '0.1')); ?>" step="0.1" min="0" max="1" />
                            <p class="description">Threshold for triggering lazy load (0-1). Lower values load images earlier.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Root Margin</th>
                        <td>
                            <input type="text" name="lazy_loading_root_margin" value="<?php echo esc_attr(get_option('lazy_loading_root_margin', '50px')); ?>" />
                            <p class="description">Margin around the root. Images will start loading when they are this distance from the viewport.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Placeholder Image</th>
                        <td>
                            <input type="text" name="lazy_loading_placeholder" value="<?php echo esc_attr(get_option('lazy_loading_placeholder', 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect width="100%25" height="100%25" fill="%23f1f1fa"/%3E%3C/svg%3E')); ?>" class="regular-text" />
                            <p class="description">URL or data URI for the placeholder image shown while loading.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Fade In Effect</th>
                        <td>
                            <input type="checkbox" name="lazy_loading_fade_in" value="1" <?php checked(get_option('lazy_loading_fade_in', '1'), '1'); ?> />
                            <p class="description">Enable smooth fade-in animation when images load.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the plugin
new LazyLoadingPlugin();

// Activation hook
register_activation_hook(__FILE__, 'lazy_loading_plugin_activate');

function lazy_loading_plugin_activate() {
    // Set default options
    add_option('lazy_loading_enabled', '1');
    add_option('lazy_loading_threshold', '0.1');
    add_option('lazy_loading_root_margin', '50px');
    add_option('lazy_loading_placeholder', 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect width="100%25" height="100%25" fill="%23f1f1fa"/%3E%3C/svg%3E');
    add_option('lazy_loading_fade_in', '1');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'lazy_loading_plugin_deactivate');

function lazy_loading_plugin_deactivate() {
    // Clean up if needed
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'lazy_loading_plugin_uninstall');

function lazy_loading_plugin_uninstall() {
    // Remove options
    delete_option('lazy_loading_enabled');
    delete_option('lazy_loading_threshold');
    delete_option('lazy_loading_root_margin');
    delete_option('lazy_loading_placeholder');
    delete_option('lazy_loading_fade_in');
}