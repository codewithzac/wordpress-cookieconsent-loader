<?php

class CCLOAD_Admin_Page {

    protected $github_updater;
    protected $releases;

    public function __construct() {
        // We need access to the GitHub updater here.
        // A simple way: global or make it accessible via a global variable.
        // For this example, let's assume we can create a new instance.
        $this->github_updater = new CCLOAD_GitHub_Updater([
            'repo_url' => 'https://api.github.com/repos/orestbida/cookieconsent',
            'plugin_dir' => CCLOAD_PLUGIN_DIR,
            'assets_dir' => CCLOAD_ASSETS_DIR
        ]);
    }

    public function init() {
        add_action('admin_menu', [$this, 'add_options_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'handle_form_submission']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_options_page() {
        add_options_page(
            'CookieConsent Loader',
            'CookieConsent',
            'manage_options',
            'ccload-admin',
            [$this, 'render_admin_page']
        );
    }

    public function register_settings() {
        // Register a setting for role-based loading
        register_setting('ccload_options_group', 'ccload_display_for_roles', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_roles_setting'],
            'default' => [
                'mode' => 'all',
                'roles' => []
            ]
        ]);


        // Register a setting for the WP Consent API mapping
        register_setting('ccload_options_group', 'ccload_wp_consent_api_mapping', [
            'type' => 'array',
            'sanitize_callback' => null, // No sanitization needed for this array
            'default' => []
        ]);

        add_settings_section(
            'ccload_display_section',
            'Display Settings',
            function() {
                echo '<p>Configure who should see the CookieConsent banner - useful for testing.</p>';
            },
            'ccload-admin'
        );
    
        add_settings_field(
            'ccload_roles_field',
            'Display for:',
            [$this, 'roles_field_callback'],
            'ccload-admin',
            'ccload_display_section'
        );

    }

    public function sanitize_roles_setting($input) {
        // Ensure we always have 'mode' and 'roles'
        $mode = isset($input['mode']) ? $input['mode'] : 'all';
        $roles = isset($input['roles']) && is_array($input['roles']) ? array_map('sanitize_text_field', $input['roles']) : [];
    
        return [
            'mode' => ($mode === 'roles' ? 'roles' : 'all'),
            'roles' => $roles
        ];
    }
    
    public function roles_field_callback() {
        $options = get_option('ccload_display_for_roles', ['mode'=>'all','roles'=>[]]);
        $mode = $options['mode'];
        $selected_roles = $options['roles'];
    
        // Fetch all roles
        global $wp_roles;
        $all_roles = $wp_roles->roles;
        $roles_names = array_keys($all_roles);
    
        ?>
        <label>
            <input type="radio" name="ccload_display_for_roles[mode]" value="all" <?php checked($mode, 'all'); ?>> 
            Display for all users
        </label>
        <br>
        <label>
            <input type="radio" name="ccload_display_for_roles[mode]" value="roles" <?php checked($mode, 'roles'); ?>>
            Display only for selected user roles
        </label>
        <div style="margin-left:20px;">
            <?php foreach($roles_names as $role_slug): ?>
                <label style="display:block;">
                    <input type="checkbox" name="ccload_display_for_roles[roles][]" value="<?php echo esc_attr($role_slug); ?>" 
                           <?php checked(in_array($role_slug, $selected_roles), true); ?>>
                    <?php echo esc_html($all_roles[$role_slug]['name']); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }


    public function enqueue_admin_assets($hook) {
        if ($hook !== 'settings_page_ccload-admin') {
            return;
        }

        // Codemirror 5
        wp_enqueue_style('codemirror-css', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.18/lib/codemirror.css', [], null);
        wp_enqueue_script('codemirror-js', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.18/lib/codemirror.js', [], null, true);
        wp_enqueue_script('codemirror-mode-css', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.18/mode/css/css.js', ['codemirror-js'], null, true);
        wp_enqueue_script('codemirror-mode-js', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.18/mode/javascript/javascript.js', ['codemirror-js'], null, true);

        // Codemirror 5 linting - disable CSS linting for now
        wp_enqueue_style('codemirror-lint-css', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.18/addon/lint/lint.css', [], null);
        wp_enqueue_script('codemirror-lint-js', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.18/addon/lint/lint.js', ['codemirror-js'], null, true);
        wp_enqueue_script('jshint', 'https://cdn.jsdelivr.net/npm/jshint@2.13.6/dist/jshint.js', [], null, true);
        wp_enqueue_script('codemirror-js-lint', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.18/addon/lint/javascript-lint.js', ['codemirror-mode-js', 'codemirror-lint-js', 'jshint'], null, true);
        //wp_enqueue_script('csslint', 'https://cdn.jsdelivr.net/npm/csslint@1.0.5/dist/csslint.js', [], null, true);
        //wp_enqueue_script('codemirror-css-lint', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.18/addon/lint/css-lint.js', ['codemirror-mode-css', 'codemirror-lint-js', 'csslint'], null, true);
        
        wp_add_inline_script('codemirror-js', $this->get_inline_editor_init_js());
    }

    protected function get_inline_editor_init_js() {
        $current_mapping = get_option('ccload_wp_consent_api_mapping', []);
        $wp_api_categories = (new CCLOAD_WP_Consent_API())->get_wp_consent_api_categories();

        $json_current_mapping = json_encode($current_mapping);
        $json_wp_api_categories = json_encode($wp_api_categories);

        return "
        jQuery(document).ready(function($){
            var jsEditor = CodeMirror.fromTextArea(document.getElementById('ccload_config_js'), {
                lineNumbers: true,
                mode: 'javascript',
                gutters: ['CodeMirror-lint-markers'],
                lint: true
            });
            var cssEditor = CodeMirror.fromTextArea(document.getElementById('ccload_custom_css'), {
                lineNumbers: true,
                mode: 'css',
                gutters: ['CodeMirror-lint-markers']
            });

            // WP Consent API Mapping Logic
            function updateConsentMappingTable() {
                var configJs = jsEditor.getValue();
                var categories = [];
                try {
                    // A safer way to extract the object
                    var startIndex = configJs.indexOf('categories: {');
                    if (startIndex === -1) throw new Error('Categories not found');
                    
                    var openBraces = 1;
                    var endIndex = -1;
                    for (var i = startIndex + 14; i < configJs.length; i++) {
                        if (configJs[i] === '{') openBraces++;
                        if (configJs[i] === '}') openBraces--;
                        if (openBraces === 0) {
                            endIndex = i;
                            break;
                        }
                    }
                    if (endIndex === -1) throw new Error('Could not find closing brace for categories');

                    var catStr = configJs.substring(startIndex + 12, endIndex + 1);
                    var configCategories = eval('(' + catStr + ')');

                    if (typeof configCategories === 'object') {
                        for (var key in configCategories) {
                            if (!configCategories[key].readOnly) {
                                categories.push(key);
                            }
                        }
                    }
                } catch (e) {
                    console.error('Error parsing CookieConsent config:', e);
                }

                var tableBody = $('#ccload-consent-api-mapping-table tbody');
                tableBody.empty();
                if (categories.length > 0) {
                    var currentMapping = {$json_current_mapping};
                    var wpApiCategories = {$json_wp_api_categories};

                    categories.forEach(function(cat) {
                        var row = '<tr><th>' + cat + '</th><td><select name=\"ccload_wp_consent_api_mapping[' + cat + ']\">';
                        row += '<option value=\"\">' + 'Do not map' + '</option>';
                        for (var wpCat in wpApiCategories) {
                            var selected = (currentMapping[cat] === wpCat) ? ' selected' : '';
                            row += '<option value=\"' + wpCat + '\"' + selected + '>' + wpApiCategories[wpCat] + '</option>';
                        }
                        row += '</select></td></tr>';
                        tableBody.append(row);
                    });
                } else {
                    tableBody.append('<tr><td colspan=\"2\">No categories found in Configuration JS or the configuration is invalid.</td></tr>');
                }
            }

            jsEditor.on('change', updateConsentMappingTable);
            updateConsentMappingTable(); // Initial population
        });
        ";
    }

    public function render_admin_page() {
        // Load current contents
        $js_editor   = new CCLOAD_File_Editor('js');
        $css_editor  = new CCLOAD_File_Editor('css');

        $config_js   = $js_editor->get_contents();
        $custom_css  = $css_editor->get_contents();

        $current_version = get_option('ccload_cookieconsent_version', 'Not Installed');
        $last_updated    = get_option('ccload_cookieconsent_last_updated', 'Never');
        if ($last_updated != 'Never') {
            $last_updated = date('Y-m-d H:i:s e', $last_updated);
        }
        
        // Paths of local files
        $js_path = CCLOAD_ASSETS_DIR . 'cookieconsent.umd.js';
        $css_path = CCLOAD_ASSETS_DIR . 'cookieconsent.css';

        // Check if files exist locally
        $js_exists = file_exists($js_path);
        $css_exists = file_exists($css_path);
        
        $js_filetime = $js_exists ? date('Y-m-d H:i:s e', filemtime($js_path)) : 'N/A';
        $css_filetime = $css_exists ? date('Y-m-d H:i:s e', filemtime($css_path)) : 'N/A';

        // Fetch all releases
        $this->releases = $this->github_updater->get_all_releases();

        ?>
        <div class="wrap">
            <h1>CookieConsent Loader</h1>
            
            <h2>Current Version Info</h2>
            <table class="form-table">
                <tr>
                    <th>Current Version:</th>
                    <td><?php echo esc_html($current_version); ?></td>
                </tr>
                <tr>
                    <th>Last Updated:</th>
                    <td><?php echo esc_html($last_updated); ?></td>
                </tr>
                <tr>
                    <th>JS File Path:</th>
                    <td><?php echo esc_html($js_path); ?> <?php echo $js_exists ? '<br>(Exists, last modified: ' . esc_html($js_filetime) . ')' : '(Not found)'; ?></td>
                </tr>
                <tr>
                    <th>CSS File Path:</th>
                    <td><?php echo esc_html($css_path); ?> <?php echo $css_exists ? '<br>(Exists, last modified: ' . esc_html($css_filetime) . ')' : '(Not found)'; ?></td>
                </tr>
            </table>

            <hr>
            <h2>Select a Version to Update</h2>
            <p class="description">Select a release and click "Update to Selected Release" to download JS and CSS files for that version.</p>
            <form method="post">
                <?php wp_nonce_field('ccload_update_version','ccload_update_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="ccload_selected_version">Available Releases:</label></th>
                        <td>
                            <select id="ccload_selected_version" name="ccload_selected_version">
                                <?php
                                if (!empty($this->releases)) {
                                    foreach ($this->releases as $release) {
                                        $tag = $release['tag_name'] ?? '';
                                        echo '<option value="' . esc_attr($tag) . '">' . esc_html($tag) . ' - ' . esc_html($release['name'] ?? '') . '</option>';
                                    }
                                } else {
                                    echo '<option value="">No releases found.</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <p><input type="submit" class="button button-primary" name="ccload_update_version_action" value="Update to Selected Release"></p>
            </form>

            <hr>
            <form method="post" action="options.php">
                <?php
                settings_fields('ccload_options_group');
                do_settings_sections('ccload-admin');
                submit_button('Save Display Settings');
                ?>
            </form>

            <hr>
            <h2>Configuration JS</h2>
            <form method="post">
                <?php wp_nonce_field('ccload_save_files','ccload_nonce'); ?>
                <textarea id="ccload_config_js" name="ccload_config_js" style="width:100%; height:300px;"><?php echo esc_textarea($config_js); ?></textarea>

                <h2>Custom CSS</h2>
                <textarea id="ccload_custom_css" name="ccload_custom_css" style="width:100%; height:300px;"><?php echo esc_textarea($custom_css); ?></textarea>

                <p><input type="submit" class="button button-primary" value="Save Files"></p>
            </form>

            <hr>
            <form method="post" action="options.php">
                <?php settings_fields('ccload_options_group'); ?>
                <h2>WP Consent API Integration</h2>
                <p class="description">Map the categories from your Configuration JS to the standard WP Consent API categories. Categories with the 'readOnly' flag will be ignored.</p>
                <?php
                $consent_api = new CCLOAD_WP_Consent_API();
                $config_path = CCLOAD_ASSETS_DIR . 'cookieconsent.config.js';
                $config_exists = file_exists($config_path);

                if (!$consent_api->is_wp_consent_api_active()) {
                    echo '<p style="color:red;">WP Consent API is not active. Please install and activate the <a href="https://wordpress.org/plugins/wp-consent-api/" target="_blank">WP Consent API plugin</a> to use this feature.</p>';
                } elseif (!$config_exists) {
                    echo '<p style="color:red;">The <code>cookieconsent.config.js</code> file does not exist. Please save the Configuration JS file above to create it.</p>';
                } else {
                    echo '<p style="color:green;">WP Consent API is installed and active.</p>';
                }
                ?>
                <table id="ccload-consent-api-mapping-table" class="form-table">
                    <tbody>
                        <!-- Rows will be populated by JavaScript -->
                    </tbody>
                </table>
                <?php submit_button('Save WP Consent API Mapping'); ?>
            </form>
        </div>
        <?php
    }

    public function handle_form_submission() {
        // Handle version update form
        if ( isset($_POST['ccload_update_nonce']) && wp_verify_nonce($_POST['ccload_update_nonce'], 'ccload_update_version') ) {
            if ( current_user_can('manage_options') ) {
                $selected_version = sanitize_text_field($_POST['ccload_selected_version'] ?? '');
                if ($selected_version) {
                    $success = $this->github_updater->download_files($selected_version);
                    add_action('admin_notices', function() use ($success, $selected_version){
                        if ($success) {
                            echo '<div class="notice notice-success is-dismissible"><p>Updated to version ' . esc_html($selected_version) . ' successfully.</p></div>';
                        } else {
                            echo '<div class="notice notice-error is-dismissible"><p>Failed to update to version ' . esc_html($selected_version) . '.</p></div>';
                        }
                    });
                }
            }
        }

        // Handle config and css file saving
        if ( isset($_POST['ccload_nonce']) && wp_verify_nonce($_POST['ccload_nonce'], 'ccload_save_files') ) {
            if ( current_user_can('manage_options') ) {
                $js_editor   = new CCLOAD_File_Editor('js');
                $css_editor  = new CCLOAD_File_Editor('css');

                if ( isset($_POST['ccload_config_js']) ) {
                    $js_editor->save_contents(wp_unslash($_POST['ccload_config_js']));
                }

                if ( isset($_POST['ccload_custom_css']) ) {
                    $css_editor->save_contents(wp_unslash($_POST['ccload_custom_css']));
                }

                // Save the consent API mapping
                if (isset($_POST['ccload_wp_consent_api_mapping']) && is_array($_POST['ccload_wp_consent_api_mapping'])) {
                    $mapping = array_map('sanitize_text_field', $_POST['ccload_wp_consent_api_mapping']);
                    update_option('ccload_wp_consent_api_mapping', $mapping);
                }

                add_action('admin_notices', function(){
                    echo '<div class="notice notice-success is-dismissible"><p>Files and settings saved successfully.</p></div>';
                });
            }
        }
    }
}
