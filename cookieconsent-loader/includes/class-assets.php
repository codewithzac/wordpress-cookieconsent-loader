<?php

class CCLOAD_Assets {

    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    public function enqueue_frontend_assets() {
        // Check if user should see the scripts
        if (!$this->should_load_for_current_user()) {
            return; // Don't enqueue if user doesn't match the criteria
        }
    
        // Main JS and CSS from the plugin (don't load if the config file isn't available)
        if ( file_exists(CCLOAD_ASSETS_DIR . 'cookieconsent.config.js') && filesize(CCLOAD_ASSETS_DIR . 'cookieconsent.config.js') > 10 ) {
            wp_enqueue_style('ccload-cookieconsent-css', CCLOAD_ASSETS_URL . 'cookieconsent.css', [], null);
            wp_enqueue_script('ccload-cookieconsent-js', CCLOAD_ASSETS_URL . 'cookieconsent.umd.js', [], null, true);
		}

        // Configuration JS (edited by the user)
        if ( file_exists(CCLOAD_ASSETS_DIR . 'cookieconsent.config.js') && filesize(CCLOAD_ASSETS_DIR . 'cookieconsent.config.js') > 10 ) {
            wp_enqueue_script('ccload-cookieconsent-config', CCLOAD_ASSETS_URL . 'cookieconsent.config.js', ['ccload-cookieconsent-js'], null, true);
        }

        // Custom CSS (edited by the user)
        if ( file_exists(CCLOAD_ASSETS_DIR . 'cookieconsent.custom.css') && filesize(CCLOAD_ASSETS_DIR . 'cookieconsent.custom.css') > 10 ) {
            wp_enqueue_style('ccload-custom-css', CCLOAD_ASSETS_URL . 'cookieconsent.custom.css', ['ccload-cookieconsent-css'], null);
        }
    }

    protected function should_load_for_current_user() {
        $options = get_option('ccload_display_for_roles', ['mode'=>'all','roles'=>[]]);
        $mode = $options['mode'];
        $roles = $options['roles'];
    
        if ($mode === 'all') {
            return true;
        }
    
        // mode === 'roles'
        if (empty($roles)) {
            // If no roles selected, default to not loading
            return false;
        }
    
        $current_user = wp_get_current_user();
        if (empty($current_user->roles)) {
            // User not logged in or has no roles => Don't load
            return false;
        }
    
        // Check if any of the user's roles match the configured roles
        foreach ($current_user->roles as $user_role) {
            if (in_array($user_role, $roles)) {
                return true;
            }
        }
    
        return false;
    }
}
