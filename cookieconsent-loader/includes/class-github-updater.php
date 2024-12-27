<?php

class CCLOAD_GitHub_Updater {
    protected $repo_url;
    protected $plugin_dir;
    protected $assets_dir;

    public function __construct( $args ) {
        $this->repo_url = rtrim($args['repo_url'], '/');
        $this->plugin_dir = $args['plugin_dir'];
        $this->assets_dir = $args['assets_dir'];
    }

    /**
     * Fetch a list of all releases from GitHub.
     * Returns an array of associative arrays with 'tag_name', 'published_at', etc.
     */
    public function get_all_releases() {
        // Try caching
        $cached = get_transient('ccload_github_releases');
        if ( $cached ) {
            return $cached;
        }

        $response = wp_remote_get($this->repo_url . '/releases', [
            'timeout' => 10,
            'headers' => ['Accept' => 'application/vnd.github.v3+json']
        ]);

        if ( is_wp_error($response) ) {
            return [];
        }

        $data = json_decode( wp_remote_retrieve_body($response), true );
        if (!is_array($data)) {
            return [];
        }

        // Filter out any releases starting with "v2"
        $filtered = [];
        foreach ($data as $release) {
            if (!empty($release['tag_name'])) {
                $tag = $release['tag_name'];
                // Check if the tag starts with "v2"
                if (substr($tag, 0, 2) === 'v2') {
                    // Skip this release
                    continue;
                }
                $filtered[] = $release;
            }
        }

        // Cache for 12 hours
        set_transient('ccload_github_releases', $filtered, 12 * HOUR_IN_SECONDS);
        return $filtered;
    }

    /**
     * Download and save the specified version's files.
     */
    public function download_files($version) {
        $js_url  = 'https://cdn.jsdelivr.net/gh/orestbida/cookieconsent@'.$version.'/dist/cookieconsent.umd.js';
        $css_url = 'https://cdn.jsdelivr.net/gh/orestbida/cookieconsent@'.$version.'/dist/cookieconsent.css';

        $js  = wp_remote_get($js_url);
        $css = wp_remote_get($css_url);

        $success = false;

        if ( !is_wp_error($js) && wp_remote_retrieve_response_code($js) == 200 ) {
            file_put_contents($this->assets_dir . 'cookieconsent.umd.js', wp_remote_retrieve_body($js));
            $success = true;
        }

        if ( !is_wp_error($css) && wp_remote_retrieve_response_code($css) == 200 ) {
            file_put_contents($this->assets_dir . 'cookieconsent.css', wp_remote_retrieve_body($css));
            $success = true;
        }

        if ($success) {
            update_option('ccload_cookieconsent_version', $version);
            update_option('ccload_cookieconsent_last_updated', current_time('timestamp', true));
        }

        return $success;
    }
}
