<?php

class CCLOAD_File_Editor {

    protected $file_path;
    protected $file_type; // 'js' or 'css'

    public function __construct($file_type = 'js') {
        $this->file_type = $file_type;
        if ($file_type === 'js') {
            $this->file_path = CCLOAD_ASSETS_DIR . 'cookieconsent.config.js';
        } else {
            $this->file_path = CCLOAD_ASSETS_DIR . 'cookieconsent.custom.css';
        }
    }

    public function get_contents() {
        if ( file_exists($this->file_path) ) {
            return file_get_contents($this->file_path);
        } else {
            // If it's the JS config file and it doesn't exist, use the dist file as fallback
            if ($this->file_type === 'js') {
                $dist_path = CCLOAD_ASSETS_DIR . 'cookieconsent.config.dist.js';
                if (file_exists($dist_path)) {
                    return file_get_contents($dist_path);
                }
            }
        }
        return '';
    }

    public function save_contents($content) {
        // Basic validation can be done here if needed.
        // For JS, consider running a linting process, or rely on CodeMirror/Ace client-side.
        
        // For safety, you might want to sanitize or validate content before writing:
        // E.g., checking for <?php tags, or suspicious strings
        file_put_contents($this->file_path, $content);
    }
}
