<?php
/**
 * Plugin Name: CookieConsent Loader
 * Description: A Wordpress plugin to load <a href="https://cookieconsent.orestbida.com/" target="_blank">CookieConsent by Orest Bida</a>. Syncs the main JS and CSS files from GitHub, and provides editors for the configuration JS and optional custom CSS files. Integrates with the <a href="https://wpconsentapi.org/" target="_blank">WP Consent API</a>.
 * Version: 0.3.1
 * Author: <a href="https://github.com/codewithzac" target="_blank">codewithzac</a>
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define constants
define( 'CCLOAD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCLOAD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CCLOAD_ASSETS_DIR', CCLOAD_PLUGIN_DIR . 'assets/' );
define( 'CCLOAD_ASSETS_URL', CCLOAD_PLUGIN_URL . 'assets/' );

// Create assets directory if it doesn't exist
if ( ! file_exists( CCLOAD_ASSETS_DIR ) ) {
    wp_mkdir_p( CCLOAD_ASSETS_DIR );
}

// Load dependencies
require_once CCLOAD_PLUGIN_DIR . 'includes/class-github-updater.php';
require_once CCLOAD_PLUGIN_DIR . 'includes/class-assets.php';
require_once CCLOAD_PLUGIN_DIR . 'includes/class-file-editor.php';
require_once CCLOAD_PLUGIN_DIR . 'includes/class-wp-consent-api.php';
require_once CCLOAD_PLUGIN_DIR . 'includes/class-admin-page.php';

// Initialize classes
add_action( 'plugins_loaded', function() {
    $github_updater = new CCLOAD_GitHub_Updater([
        'repo_url' => 'https://api.github.com/repos/orestbida/cookieconsent',
        'plugin_dir' => CCLOAD_PLUGIN_DIR,
        'assets_dir' => CCLOAD_ASSETS_DIR
    ]);
    //$github_updater->maybe_update_files();

    $assets = new CCLOAD_Assets();
    $assets->init();

    $admin_page = new CCLOAD_Admin_Page();
    $admin_page->init();

    $wp_consent_api = new CCLOAD_WP_Consent_API();
    $wp_consent_api->init();
});

// Show an admin notice if JS and CSS haven't been downloaded yet.
add_action( 'admin_notices', function() {
    if ( current_user_can('manage_options') ) {
        $js_path  = CCLOAD_ASSETS_DIR . 'cookieconsent.umd.js';
        $css_path = CCLOAD_ASSETS_DIR . 'cookieconsent.css';

        $js_exists = file_exists($js_path);
        $css_exists = file_exists($css_path);

        if ( ! $js_exists || ! $css_exists ) {
            // Display a dismissible admin notice
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>CookieConsent Loader:</strong> The CookieConsent JS and CSS files have not been downloaded yet. Please go to the <a href="'.esc_url(admin_url('options-general.php?page=ccload-admin')).'">CookieConsent Loader settings page</a> and select a version to download.</p>';
            echo '</div>';
        }
    }
});

// Add settings link to installed plugins page
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'ccload_add_action_links' );

function ccload_add_action_links ( $links ) {
    $mylinks[] = '<a href="' . admin_url( 'options-general.php?page=ccload-admin' ) . '">Settings</a>';
    return array_merge( $mylinks, $links );
}

// Clean up on uninstall
register_uninstall_hook( __FILE__, 'ccload_uninstall' );

function ccload_uninstall() {
    // Remove stored options
    delete_option('ccload_cookieconsent_version');
    delete_option('ccload_cookieconsent_last_updated');

    // Remove transients
    delete_transient('ccload_github_releases');

    // Remove configuration and custom CSS files created by the plugin
    $dist_js = CCLOAD_ASSETS_DIR . 'cookieconsent.config.dist.js';
    $config_js  = CCLOAD_ASSETS_DIR . 'cookieconsent.config.js';
    $custom_css = CCLOAD_ASSETS_DIR . 'cookieconsent.custom.css';

    if (file_exists($dist_js)) {
        unlink($dist_js);
    }

    if (file_exists($config_js)) {
        unlink($config_js);
    }

    if (file_exists($custom_css)) {
        unlink($custom_css);
    }

    // Remove the main library files for the plugin
    $library_js  = CCLOAD_ASSETS_DIR . 'cookieconsent.umd.js';
    $library_css = CCLOAD_ASSETS_DIR . 'cookieconsent.css';

    if (file_exists($library_js)) {
        unlink($library_js);
    }

    if (file_exists($library_css)) {
        unlink($library_css);
    }

    // Finally, remove the entire assets directory
    if (is_dir(CCLOAD_ASSETS_DIR)) {
        // Ensure directory is empty before removal
        array_map('unlink', glob(CCLOAD_ASSETS_DIR . '*.*'));
        rmdir(CCLOAD_ASSETS_DIR);
    }
}
