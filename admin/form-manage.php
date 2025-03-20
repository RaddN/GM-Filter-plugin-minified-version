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

function dapfforwc_show_categories_render() { dapfforwc_render_checkbox('show_categories'); }
function dapfforwc_show_attributes_render() { dapfforwc_render_checkbox('show_attributes'); }
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
    <p class="description"><?php esc_html_e('Enter your custom template code here.', 'gm-ajax-product-filter-for-woocommerce'); ?></p>
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


