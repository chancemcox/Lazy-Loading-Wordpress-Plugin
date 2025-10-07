<?php
/**
 * Plugin Installation and Activation Handler
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Runs on plugin activation
 */
function lazy_loading_plugin_activate() {
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '4.7', '<')) {
        deactivate_plugins(basename(__FILE__));
        wp_die('This plugin requires WordPress version 4.7 or higher.');
    }

    // Check PHP version
    if (version_compare(PHP_VERSION, '5.6', '<')) {
        deactivate_plugins(basename(__FILE__));
        wp_die('This plugin requires PHP version 5.6 or higher.');
    }

    // Set default options
    $default_options = array(
        'lazy_loading_enabled' => '1',
        'lazy_loading_threshold' => '0.1',
        'lazy_loading_root_margin' => '50px',
        'lazy_loading_placeholder' => 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect width="100%25" height="100%25" fill="%23f1f1fa"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" fill="%23999" font-family="Arial, sans-serif" font-size="14"%3ELoading...%3C/text%3E%3C/svg%3E',
        'lazy_loading_fade_in' => '1',
        'lazy_loading_version' => LAZY_LOADING_PLUGIN_VERSION
    );

    foreach ($default_options as $option => $value) {
        if (get_option($option) === false) {
            add_option($option, $value);
        }
    }

    // Create plugin tables if needed (for future features)
    // lazy_loading_create_tables();

    // Set activation flag
    set_transient('lazy_loading_plugin_activated', true, 30);

    // Flush rewrite rules if needed
    flush_rewrite_rules();
}

/**
 * Runs on plugin deactivation
 */
function lazy_loading_plugin_deactivate() {
    // Clean up transients
    delete_transient('lazy_loading_plugin_activated');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Runs on plugin uninstall
 */
function lazy_loading_plugin_uninstall() {
    // Remove options
    $options_to_remove = array(
        'lazy_loading_enabled',
        'lazy_loading_threshold',
        'lazy_loading_root_margin',
        'lazy_loading_placeholder',
        'lazy_loading_fade_in',
        'lazy_loading_version'
    );

    foreach ($options_to_remove as $option) {
        delete_option($option);
    }

    // Remove any custom tables
    // lazy_loading_remove_tables();

    // Clean up any scheduled events
    wp_clear_scheduled_hook('lazy_loading_cleanup');
}

/**
 * Handle plugin updates
 */
function lazy_loading_plugin_update_check() {
    $current_version = get_option('lazy_loading_version', '0.0.0');
    
    if (version_compare($current_version, LAZY_LOADING_PLUGIN_VERSION, '<')) {
        // Run update routines
        lazy_loading_run_updates($current_version);
        
        // Update version number
        update_option('lazy_loading_version', LAZY_LOADING_PLUGIN_VERSION);
    }
}

/**
 * Run update routines for different versions
 */
function lazy_loading_run_updates($from_version) {
    // Example update routine
    if (version_compare($from_version, '1.0.0', '<')) {
        // Update from pre-1.0.0 to 1.0.0
        // Add any necessary database changes, option migrations, etc.
    }
}

/**
 * Display admin notice on activation
 */
function lazy_loading_activation_notice() {
    if (get_transient('lazy_loading_plugin_activated')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong>Lazy Loading Plugin activated!</strong> 
                Your images will now load efficiently. 
                <a href="<?php echo admin_url('options-general.php?page=lazy-loading-settings'); ?>">Configure settings</a>
            </p>
        </div>
        <?php
        delete_transient('lazy_loading_plugin_activated');
    }
}

// Hook into WordPress
add_action('admin_notices', 'lazy_loading_activation_notice');
add_action('plugins_loaded', 'lazy_loading_plugin_update_check');