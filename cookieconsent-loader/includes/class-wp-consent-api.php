<?php

class CCLOAD_WP_Consent_API {

    public function init() {
        // Check if the WP Consent API is available and then hook into it.
        $config_path = CCLOAD_ASSETS_DIR . 'cookieconsent.config.js';
        if ($this->is_wp_consent_api_active() && file_exists($config_path)) {
            $plugin = plugin_basename(CCLOAD_PLUGIN_DIR . 'cookieconsent-loader.php');
            add_filter("wp_consent_api_registered_{$plugin}", '__return_true');

            // Set consent type based on plugin settings
            add_filter('wp_get_consent_type', [$this, 'set_consent_type'], 100);
        }
    }

    /**
     * Check if the WP Consent API plugin is active.
     *
     * @return bool
     */
    public function is_wp_consent_api_active() {
        return defined('WP_CONSENT_API_VERSION');
    }

    /**
     * Set the consent type based on the plugin's settings.
     *
     * @param string $consent_type The default consent type.
     * @return string The modified consent type ('optin' or 'optout').
     */
    public function set_consent_type($consent_type) {
        $mode = $this->get_mode_from_config();
        if ($mode === 'opt-out') {
            return 'optout';
        }
        return 'optin';
    }

    /**
     * Get the consent mode from the JS config file.
     *
     * @return string 'opt-in' or 'opt-out'.
     */
    private function get_mode_from_config() {
        $config_path = CCLOAD_ASSETS_DIR . 'cookieconsent.config.js';
        if (!file_exists($config_path)) {
            return 'opt-in'; // Default
        }

        $config_content = file_get_contents($config_path);

        // Reliably find the mode setting
        // Remove all whitespace (spaces, tabs, newlines), single quotes and double quotes
        $normalized_content = preg_replace('/\s+/', '', $config_content);
        $normalized_content = str_replace(array("'", '"'), '', $normalized_content);

        // Check for the supported modes and return
        if (strpos($normalized_content, 'mode:opt-out') !== false) {
            return 'opt-out';
        }

        if (strpos($normalized_content, 'mode:opt-in') !== false) {
            return 'opt-in';
        }

        return 'opt-in'; // Default
    }

    /**
     * Get the available WP Consent API categories.
     *
     * @return array
     */
    public function get_wp_consent_api_categories() {
        return [
            'statistics' => __('Statistics', 'cookieconsent-loader'),
            'statistics-anonymous' => __('Statistics (Anonymous)', 'cookieconsent-loader'),
            'marketing' => __('Marketing', 'cookieconsent-loader'),
            'functional' => __('Functional', 'cookieconsent-loader'),
            'preferences' => __('Preferences', 'cookieconsent-loader'),
        ];
    }

    /**
     * Get the saved mapping between CookieConsent categories and WP Consent API categories.
     *
     * @return array
     */
    public function get_category_mapping() {
        return get_option('ccload_wp_consent_api_mapping', []);
    }
}
