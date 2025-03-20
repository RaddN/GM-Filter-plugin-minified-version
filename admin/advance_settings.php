<?php
if (!defined('ABSPATH')) {
    exit;
}

// Define constants for default values
define('DEFAULT_PRODUCT_SELECTOR', '.products');
define('DEFAULT_PAGINATION_SELECTOR', '.woocommerce-pagination ul.page-numbers');
define('DEFAULT_PRODUCT_SHORTCODE', 'products');

// Render the "Product Selector" field
function dapfforwc_product_selector_callback() {
    $wcapf_options = get_option('wcapf_advance_options')?:[];
    $product_selector = isset($wcapf_options['product_selector']) ? esc_attr($wcapf_options['product_selector']) : DEFAULT_PRODUCT_SELECTOR;
    ?>
    <input type="text" name="wcapf_advance_options[product_selector]" value="<?php echo esc_attr($product_selector); ?>" placeholder="<?php echo esc_attr(DEFAULT_PRODUCT_SELECTOR); ?>">
    <p class="description">
        <?php esc_html_e('Enter the CSS selector for the product container. Default is .products.', 'gm-ajax-product-filter-for-woocommerce'); ?>
    </p>
    <?php
}

// Render the "Pagination Selector" field
function dapfforwc_pagination_selector_callback() {
    $wcapf_options = get_option('wcapf_advance_options')?:[];
    $pagination_selector = isset($wcapf_options['pagination_selector']) ? esc_attr($wcapf_options['pagination_selector']) : DEFAULT_PAGINATION_SELECTOR;
    ?>
    <input type="text" name="wcapf_advance_options[pagination_selector]" value="<?php echo esc_attr($pagination_selector); ?>" placeholder="<?php echo esc_attr(DEFAULT_PAGINATION_SELECTOR); ?>">
    <p class="description">
        <?php esc_html_e('Enter the CSS selector for the pagination container. Default is .woocommerce-pagination ul.page-numbers.', 'gm-ajax-product-filter-for-woocommerce'); ?>
    </p>
    <?php
}

// Render the "Product Shortcode Selector" field
function dapfforwc_product_shortcode_callback() {
    $wcapf_options = get_option('wcapf_advance_options')?:[];
    $product_shortcode = isset($wcapf_options['product_shortcode']) ? esc_attr($wcapf_options['product_shortcode']) : DEFAULT_PRODUCT_SHORTCODE;
    ?>
    <input type="text" name="wcapf_advance_options[product_shortcode]" value="<?php echo esc_attr($product_shortcode); ?>" placeholder="<?php echo esc_attr(DEFAULT_PRODUCT_SHORTCODE); ?>">
    <p class="description">
        <?php esc_html_e('Enter the selector for the products shortcode. Default is products.', 'gm-ajax-product-filter-for-woocommerce'); ?>
    </p>
    <?php
}

function dapfforwc_render_advance_checkbox($key) {
    $wcapf_options = get_option('wcapf_advance_options')?:[];
    ?>
    <label class="switch <?php echo esc_attr($key); ?>">
        <input type='checkbox' name='wcapf_advance_options[<?php echo esc_attr($key); ?>]' <?php checked(isset($wcapf_options[$key]) && $wcapf_options[$key] === "on"); ?>>
        <span class="slider round"></span>
    </label>
    <?php
}