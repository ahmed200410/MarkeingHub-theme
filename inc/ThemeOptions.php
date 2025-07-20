<?php
/**
 * ThemeOptions class
 * Theme Settings menu page in the WordPress admin sidebar
 */
class ThemeOptions {
    private $sections = [];
    public function __construct() {
        add_action('admin_menu', [ $this, 'register_theme_options_pages' ]);
        add_action('admin_init', [ $this, 'load_sections_from_json' ]);
    }

    public function load_sections_from_json() {
        $file = get_template_directory() . '/translations.json';
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $data = json_decode($json, true);
            if (is_array($data)) {
                $this->sections = array_keys($data[array_key_first($data)] ?? []);
            }
        }
    }

    public function register_theme_options_pages() {
        add_menu_page(
            'MarketingHub Settings',
            'MarketingHub Settings',
            'manage_options',
            'theme-settings',
            [ $this, 'render_main_page' ],
            'dashicons-admin-generic',
            61
        );
        $this->load_sections_from_json();
        foreach ($this->sections as $section) {
            add_submenu_page(
                'theme-settings',
                ucfirst($section) . ' Settings',
                ucfirst($section),
                'manage_options',
                'theme-settings-' . $section,
                function() use ($section) { $this->render_section_page($section); }
            );
        }
        add_submenu_page(
            'theme-settings',
            'Logo',
            'Logo',
            'manage_options',
            'theme-settings-logo',
            [ $this, 'render_logo_page' ]
        );
    }

    public function render_main_page() {
        echo '<div class="wrap"><h1>MarketingHub Settings</h1><p>Welcome to the theme options. Please select a section from the submenu to edit translations.</p></div>';
    }

    public function render_section_page($section) {
        $nonce = wp_create_nonce('wp_rest');
        ?>
        <div class="wrap">
            <h1><?php echo ucfirst($section); ?> Settings</h1>
            <p>Edit all translation texts for the <b><?php echo ucfirst($section); ?></b> section below. Changes affect the entire site.</p>
            <div id="themeoptions-messages"></div>
            <div id="themeoptions-tabs"></div>
            <form id="themeoptions-form">
                <div id="themeoptions-fields"></div>
                <p><button type="submit" class="button button-primary">Save All Translations</button></p>
            </form>
        </div>
        <script>window.themeOptionsNonce = "<?php echo esc_js($nonce); ?>";</script>
        <style>
        .themeoptions-tabs { margin-bottom: 20px; }
        .themeoptions-tab { display: inline-block; margin-right: 10px; padding: 6px 16px; border: 1px solid #ccc; border-bottom: none; background: #f9f9f9; cursor: pointer; border-radius: 6px 6px 0 0; }
        .themeoptions-tab.active { background: #fff; font-weight: bold; border-bottom: 1px solid #fff; }
        .themeoptions-card { background: #fff; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 24px; padding: 18px 20px; box-shadow: 0 2px 8px #0001; }
        .themeoptions-section-title { font-size: 1.2em; font-weight: bold; margin-bottom: 12px; margin-top: 0; }
        .themeoptions-field { margin-bottom: 16px; }
        .themeoptions-label { display: block; font-weight: bold; margin-bottom: 4px; }
        .themeoptions-input, .themeoptions-textarea { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px; }
        .themeoptions-textarea { min-height: 60px; font-family: monospace; }
        .themeoptions-subheading { font-weight: bold; margin: 10px 0 6px 0; color: #333; }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.getElementById('themeoptions-messages');
            const form = document.getElementById('themeoptions-form');
            const fieldsDiv = document.getElementById('themeoptions-fields');
            const tabsDiv = document.getElementById('themeoptions-tabs');
            let translations = {};
            let currentLang = '';

            function makeLabel(pathArr) {
                return pathArr
                    .map((part, i) => {
                        if (/^\d+$/.test(part)) return `Item ${parseInt(part)+1}`;
                        return part.charAt(0).toUpperCase() + part.slice(1).replace(/_/g, ' ');
                    })
                    .join(' > ');
            }

            function renderFields(obj, pathArr = [], sectionLevel = 0) {
                let html = '';
                if (Array.isArray(obj)) {
                    obj.forEach((item, idx) => {
                        html += renderFields(item, pathArr.concat([String(idx)]), sectionLevel+1);
                    });
                } else if (typeof obj === 'object' && obj !== null) {
                    if (sectionLevel === 0) {
                        Object.keys(obj).forEach(key => {
                            html += renderFields(obj[key], [key], 1);
                        });
                    } else {
                        if (sectionLevel === 1) {
                            html += `<div class="themeoptions-subheading">${makeLabel(pathArr)}</div>`;
                        }
                        Object.keys(obj).forEach(key => {
                            html += renderFields(obj[key], pathArr.concat([key]), sectionLevel+1);
                        });
                    }
                } else {
                    const fieldName = pathArr.join('.');
                    const label = makeLabel(pathArr);
                    const isLong = typeof obj === 'string' && obj.length > 60;
                    html += `<div class="themeoptions-field">
                        <label class="themeoptions-label" for="${fieldName}">${label}</label>
                        ${isLong ?
                            `<textarea class="themeoptions-textarea" id="${fieldName}" name="${fieldName}">${obj !== undefined ? obj : ''}</textarea>` :
                            `<input class="themeoptions-input" type="text" id="${fieldName}" name="${fieldName}" value="${obj !== undefined ? obj : ''}">`
                        }
                    </div>`;
                }
                return html;
            }

            function setNested(obj, path, value) {
                const keys = path.split('.');
                let o = obj;
                for (let i = 0; i < keys.length - 1; i++) {
                    if (!o[keys[i]]) o[keys[i]] = {};
                    o = o[keys[i]];
                }
                o[keys[keys.length - 1]] = value;
            }

            fetch('/wp-json/marketinghub/v1/themeoptions')
                .then(res => res.json())
                .then(data => {
                    translations = data;
                    const languages = Object.keys(translations);
                    currentLang = languages[0];
                    let tabsHtml = '<div class="themeoptions-tabs">';
                    for (const lang of languages) {
                        tabsHtml += `<span class="themeoptions-tab${lang === currentLang ? ' active' : ''}" data-lang="${lang}">${lang.toUpperCase()}</span>`;
                    }
                    tabsHtml += '</div>';
                    tabsDiv.innerHTML = tabsHtml;
                    tabsDiv.querySelectorAll('.themeoptions-tab').forEach(tab => {
                        tab.addEventListener('click', function() {
                            currentLang = this.getAttribute('data-lang');
                            tabsDiv.querySelectorAll('.themeoptions-tab').forEach(t => t.classList.remove('active'));
                            this.classList.add('active');
                            renderCurrentLang();
                        });
                    });
                    renderCurrentLang();
                });

            function renderCurrentLang() {
                const langObj = translations[currentLang];
                let html = '';
                if (langObj && langObj['<?php echo $section; ?>']) {
                    html += renderFields(langObj['<?php echo $section; ?>'], [], 0);
                } else {
                    html = '<p>No data for this section.</p>';
                }
                fieldsDiv.innerHTML = html;
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                messages.innerHTML = '';
                const formData = new FormData(form);
                const updated = JSON.parse(JSON.stringify(translations));
                for (const [name, value] of formData.entries()) {
                    setNested(updated[currentLang]['<?php echo $section; ?>'], name, value);
                }
                fetch('/wp-json/marketinghub/v1/themeoptions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.themeOptionsNonce
                    },
                    body: JSON.stringify(updated)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        messages.innerHTML = '<div style="color:green;">Saved successfully!</div>';
                        translations = updated;
                        renderCurrentLang();
                    } else {
                        messages.innerHTML = '<div style="color:red;">Error: ' + (data.message || 'Unknown error') + '</div>';
                    }
                })
                .catch(err => {
                    messages.innerHTML = '<div style="color:red;">Error: ' + err.message + '</div>';
                });
            });
        });
        </script>
        <?php
    }

    public function render_logo_page() {
        $nonce = wp_create_nonce('wp_rest');
        ?>
        <div class="wrap">
            <h1>Logo Settings</h1>
            <p>Upload and manage the site logo. The logo will be used in the header, footer, and all relevant places.</p>
            <div id="logo-messages"></div>
            <div id="logo-current"></div>
            <form id="logo-upload-form" enctype="multipart/form-data">
                <input type="file" name="logo" id="logo-input" accept="image/*" required />
                <button type="submit" class="button button-primary">Upload Logo</button>
            </form>
        </div>
        <script>window.themeOptionsNonce = "<?php echo esc_js($nonce); ?>";</script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.getElementById('logo-messages');
            const current = document.getElementById('logo-current');
            const form = document.getElementById('logo-upload-form');
            const input = document.getElementById('logo-input');

            function fetchLogo() {
                fetch('/wp-json/marketinghub/v1/logo')
                    .then(res => res.json())
                    .then(data => {
                        if (data.url) {
                            current.innerHTML = '<p>Current Logo:</p><img src="' + data.url + '" alt="Logo" style="max-width:200px;max-height:120px;display:block;margin-bottom:10px;">';
                        } else {
                            current.innerHTML = '<p>No logo set.</p>';
                        }
                    });
            }
            fetchLogo();

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                messages.innerHTML = '';
                if (!input.files.length) {
                    messages.innerHTML = '<div style="color:red;">Please select a file.</div>';
                    return;
                }
                const formData = new FormData();
                formData.append('logo', input.files[0]);
                fetch('/wp-json/marketinghub/v1/logo', {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': window.themeOptionsNonce
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.url) {
                        messages.innerHTML = '<div style="color:green;">Logo uploaded successfully!</div>';
                        fetchLogo();
                        input.value = '';
                    } else {
                        messages.innerHTML = '<div style="color:red;">Error: ' + (data.message || 'Unknown error') + '</div>';
                    }
                })
                .catch(err => {
                    messages.innerHTML = '<div style="color:red;">Error: ' + err.message + '</div>';
                });
            });
        });
        </script>
        <?php
    }
}


global $themeOptions;
$themeOptions = new ThemeOptions(); 