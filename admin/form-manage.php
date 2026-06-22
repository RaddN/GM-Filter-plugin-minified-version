<?php
if (!defined('ABSPATH')) {
    exit;
}

function dapfforwc_render_checkbox($key) {
    global $wcapf_options;
    ?>
    <label class="switch <?php echo esc_attr($key); ?>">
    <input type='checkbox' name='wcapf_options[<?php echo esc_attr($key); ?>]' <?php checked(isset($wcapf_options[$key]) && $wcapf_options[$key] === "on"); ?>>
    <span class="slider round"></span>
    </label>
    <?php
}

function dapfforwc_primary_color_render() {
    $primary_color = function_exists('dapfforwc_get_primary_color') ? dapfforwc_get_primary_color() : '#c9a84c';
    ?>
    <label class="dapfforwc-color-field">
        <input type="color" name="wcapf_options[primary_color]" value="<?php echo esc_attr($primary_color); ?>">
        <span><?php echo esc_html($primary_color); ?></span>
    </label>
    <?php
}

function dapfforwc_get_taxonomy_ui_setting_rows() {
    $rows = [];

    if (function_exists('dapfforwc_get_woocommerce_attributes_with_terms')) {
        $all_data = dapfforwc_get_woocommerce_attributes_with_terms();

        if (!empty($all_data['categories'])) {
            $rows['category'] = __('Category', 'ajax-product-filter-for-woocommerce');
        }

        if (!empty($all_data['attributes']) && is_array($all_data['attributes'])) {
            foreach ($all_data['attributes'] as $attribute_name => $attribute) {
                $attribute_key = sanitize_key($attribute_name);
                $rows[$attribute_key] = !empty($attribute['attribute_label'])
                    ? sanitize_text_field($attribute['attribute_label'])
                    : dapfforwc_get_filter_group_title($attribute_key);
            }
        }

        if (!empty($all_data['tags'])) {
            $rows['tag'] = __('Tag', 'ajax-product-filter-for-woocommerce');
        }
    }

    if (empty($rows)) {
        $rows = [
            'category' => __('Category', 'ajax-product-filter-for-woocommerce'),
            'conference-by-month' => __('Conference By Month', 'ajax-product-filter-for-woocommerce'),
            'popular-cities' => __('Popular Cities', 'ajax-product-filter-for-woocommerce'),
            'popular-countries' => __('Popular Countries', 'ajax-product-filter-for-woocommerce'),
            'popular-topics' => __('Popular Topics', 'ajax-product-filter-for-woocommerce'),
            'tag' => __('Tag', 'ajax-product-filter-for-woocommerce'),
        ];
    }

    return $rows;
}

function dapfforwc_render_taxonomy_settings_modal() {
    $taxonomy_rows = dapfforwc_get_taxonomy_ui_setting_rows();
    ?>
    <div id="dapfforwc-taxonomy-settings-modal" class="dapfforwc-taxonomy-modal" hidden>
        <div class="dapfforwc-taxonomy-modal__backdrop" data-dapfforwc-modal-close></div>
        <div class="dapfforwc-taxonomy-modal__panel" role="dialog" aria-modal="true" aria-labelledby="dapfforwc-taxonomy-modal-title">
            <div class="dapfforwc-taxonomy-modal__header">
                <h2 id="dapfforwc-taxonomy-modal-title"><?php esc_html_e('Filter taxonomy display', 'ajax-product-filter-for-woocommerce'); ?></h2>
                <button type="button" class="dapfforwc-taxonomy-modal__close" data-dapfforwc-modal-close aria-label="<?php esc_attr_e('Close taxonomy display settings', 'ajax-product-filter-for-woocommerce'); ?>">
                    <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                </button>
            </div>
            <div class="dapfforwc-taxonomy-modal__body">
                <?php foreach ($taxonomy_rows as $taxonomy_key => $taxonomy_label) :
                    $taxonomy_key = sanitize_key($taxonomy_key);
                    $ui_settings = dapfforwc_get_filter_group_ui_settings($taxonomy_key);
                    ?>
                    <div class="dapfforwc-taxonomy-setting">
                        <h3><?php echo esc_html($taxonomy_label); ?></h3>
                        <label>
                            <span><?php esc_html_e('SVG icon', 'ajax-product-filter-for-woocommerce'); ?></span>
                            <textarea name="wcapf_options[taxonomy_settings][<?php echo esc_attr($taxonomy_key); ?>][svg_icon]" rows="4" spellcheck="false"><?php echo esc_textarea($ui_settings['svg_icon']); ?></textarea>
                        </label>
                        <label>
                            <span><?php esc_html_e('View all button text', 'ajax-product-filter-for-woocommerce'); ?></span>
                            <input type="text" name="wcapf_options[taxonomy_settings][<?php echo esc_attr($taxonomy_key); ?>][view_all_text]" value="<?php echo esc_attr($ui_settings['view_all_text']); ?>">
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

function dapfforwc_render_taxonomy_settings_button() {
    ?>
    <button type="button" class="button dapfforwc-taxonomy-settings-trigger" aria-haspopup="dialog" aria-controls="dapfforwc-taxonomy-settings-modal" aria-label="<?php esc_attr_e('Manage taxonomy icons and view all text', 'ajax-product-filter-for-woocommerce'); ?>">
        <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
    </button>
    <?php
    dapfforwc_render_taxonomy_settings_modal();
}

function dapfforwc_show_categories_render() { dapfforwc_render_checkbox('show_categories'); }
function dapfforwc_show_attributes_render() { dapfforwc_render_checkbox('show_attributes'); dapfforwc_render_taxonomy_settings_button(); }
function dapfforwc_show_tags_render() { dapfforwc_render_checkbox('show_tags'); }
function dapfforwc_update_filter_options_render() {dapfforwc_render_checkbox('update_filter_options');}
function dapfforwc_show_loader_render() { dapfforwc_render_checkbox('show_loader'); }
function dapfforwc_use_custom_template_render() {dapfforwc_render_checkbox('use_custom_template');}


function dapfforwc_custom_template_code_render() {
    global $wcapf_options;
    echo '    
    <div class="custom_template_code" >';
    ?>
        <!-- Placeholder List -->
        <div id="placeholder-list" style="margin-bottom: 10px;">
        <?php
            $placeholders = [
                '{{product_link}}' => 'Product Link',
                '{{product_title}}' => 'Product Title',
                '{{product_image}}' => 'Product Image',
                '{{product_price}}' => 'Product Price',
                '{{product_excerpt}}' => 'Product Excerpt',
                '{{product_category}}' => 'Product Category',
                '{{product_sku}}' => 'Product SKU',
                '{{product_stock}}' => 'Product Stock',
                '{{add_to_cart_url}}' => 'Add to Cart URL',
                '{{product_id}}' => 'Product ID'
            ];
            if (is_array($placeholders) || is_object($placeholders)) {
            foreach ($placeholders as $placeholder => $label) {
                echo "<span class='placeholder' onclick=\"insertPlaceholder('".esc_html($placeholder)."')\">".esc_html($placeholder)."</span>";
            }
        }
            ?>
    </div>
    <textarea style="display:none;" id="custom_template_input" name="wcapf_options[custom_template_code]" rows="10" cols="50" class="large-text"><?php if(isset($wcapf_options['custom_template_code'])){echo esc_textarea($wcapf_options['custom_template_code']); } ?></textarea>
    <div id="code-editor"></div>
    <p class="description"><?php esc_html_e('Enter your custom template code here.', 'ajax-product-filter-for-woocommerce'); ?></p>
</div>


    <?php
}

function dapfforwc_pages_render() {
    global $wcapf_options;
    $pages = isset($wcapf_options['pages']) ? array_filter($wcapf_options['pages']) : []; // Filter out empty values
    ?>
    <div class="page-listing">
    <legend>Manage Pages</legend>
    <div class="page-inputs">
        <input type="text" name="wcapf_options[pages][]" value="" placeholder="Add new page" />
        <button type="button" class="add-page">Add Page</button>
    </div>
    <div class="page-list">
        <?php 
        if (is_array($pages) || is_object($pages)) :
        foreach ($pages as $page) : ?>
            <div class="page-item">
                <input type="text" name="wcapf_options[pages][]" value="<?php echo esc_attr($page); ?>" />
                <button type="button" class="remove-page">Remove</button>
            </div>
        <?php endforeach; 
        endif;
        ?>
    </div>
        </div>
    <?php
}


// Helper function to sanitize nested arrays.
function dapfforwc_sanitize_wcapf_options($array) {
    $sanitized_array = dapfforwc_sanitize_nested_array($array);

    if (isset($array['primary_color'])) {
        $primary_color = sanitize_hex_color(wp_unslash($array['primary_color']));
        $sanitized_array['primary_color'] = $primary_color ? $primary_color : '#c9a84c';
    }

    if (isset($array['taxonomy_settings'])) {
        $sanitized_array['taxonomy_settings'] = dapfforwc_sanitize_taxonomy_settings($array['taxonomy_settings']);
    }

    if (isset($array['custom_template_code'])) {
        $sanitized_array['custom_template_code'] = wp_kses_post(wp_unslash($array['custom_template_code']));
    }

    return $sanitized_array;
}

function dapfforwc_sanitize_taxonomy_settings($settings) {
    $sanitized_settings = [];

    if (!is_array($settings)) {
        return $sanitized_settings;
    }

    foreach ($settings as $taxonomy_key => $setting) {
        if (!is_array($setting)) {
            continue;
        }

        $taxonomy_key = sanitize_key($taxonomy_key);
        if ('' === $taxonomy_key) {
            continue;
        }

        $svg_icon = isset($setting['svg_icon']) && is_scalar($setting['svg_icon']) ? (string) wp_unslash($setting['svg_icon']) : '';
        $view_all_text = isset($setting['view_all_text']) && is_scalar($setting['view_all_text']) ? (string) wp_unslash($setting['view_all_text']) : '';

        $sanitized_settings[$taxonomy_key] = [
            'svg_icon' => wp_kses($svg_icon, dapfforwc_get_allowed_svg_tags()),
            'view_all_text' => sanitize_text_field($view_all_text),
        ];
    }

    return $sanitized_settings;
}

function dapfforwc_sanitize_nested_array($array) {
    $sanitized_array = array();
    if (is_array($array) || is_object($array)) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            // Recursively handle nested arrays.
            $sanitized_array[$key] = dapfforwc_sanitize_nested_array($value);
        } else {
            // Apply appropriate sanitization based on key or type.
            if (is_string($value)) {
                // Assume strings need text sanitization.
                $sanitized_array[$key] = sanitize_text_field($value);
            } elseif (is_numeric($value)) {
                // Handle numeric values.
                $sanitized_array[$key] = $value;
            } else {
                // Default sanitization for unexpected types.
                $sanitized_array[$key] = sanitize_text_field((string)$value);
            }
        }
    }
}

    return $sanitized_array;
}
