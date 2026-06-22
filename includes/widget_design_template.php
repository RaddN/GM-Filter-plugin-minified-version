<?php

if (!defined('ABSPATH')) {
    exit;
}

function dapfforwc_filter_form($updated_filters, $default_filter)
{
    global $wcapf_options, $dapfforwc_advance_settings;

    if (!isset($wcapf_options)) {
        return "";
    }
    if (!isset($dapfforwc_advance_settings)) {
        return "";
    }

    $formOutPut = '';
    $selected_categories = !empty($default_filter) ? $default_filter : [];

    // Render categories
    if (!empty($updated_filters["categories"])) {
        $formOutPut .= dapfforwc_render_filter_group('category', $updated_filters["categories"], $selected_categories, isset($wcapf_options['show_categories']) ? $wcapf_options['show_categories'] : false);
    }

    // Render attributes
    if (!empty($updated_filters["attributes"])) {
        $formOutPut .= '<div class="filter-group attributes" style="display: ' . (!empty($wcapf_options['show_attributes']) ? 'block' : 'none') . ';">';
        if (is_array($updated_filters["attributes"]) || is_object($updated_filters["attributes"])) {
        foreach ($updated_filters["attributes"] as $attribute_name => $terms) {
            $formOutPut .= dapfforwc_render_filter_group($attribute_name, $terms, $default_filter, true, true);
        }}
        $formOutPut .= '</div>';
    }

    // Render tags
    if (!empty($updated_filters["tags"])) {
        $formOutPut .= dapfforwc_render_filter_group('tag', $updated_filters["tags"], $default_filter, isset($wcapf_options['show_tags']) ? $wcapf_options['show_tags'] : false);
    }

    return $formOutPut;
}

function dapfforwc_get_allowed_svg_tags()
{
    return [
        'svg' => [
            'aria-hidden' => true,
            'class' => true,
            'fill' => true,
            'focusable' => true,
            'height' => true,
            'role' => true,
            'stroke' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'stroke-width' => true,
            'viewBox' => true,
            'viewbox' => true,
            'width' => true,
            'xmlns' => true,
        ],
        'circle' => [
            'cx' => true,
            'cy' => true,
            'fill' => true,
            'r' => true,
            'stroke' => true,
            'stroke-width' => true,
        ],
        'line' => [
            'stroke' => true,
            'stroke-linecap' => true,
            'stroke-width' => true,
            'x1' => true,
            'x2' => true,
            'y1' => true,
            'y2' => true,
        ],
        'path' => [
            'd' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'stroke-width' => true,
        ],
        'polyline' => [
            'fill' => true,
            'points' => true,
            'stroke' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'stroke-width' => true,
        ],
        'rect' => [
            'fill' => true,
            'height' => true,
            'rx' => true,
            'stroke' => true,
            'stroke-width' => true,
            'width' => true,
            'x' => true,
            'y' => true,
        ],
    ];
}

function dapfforwc_get_primary_color()
{
    global $wcapf_options;

    $primary_color = isset($wcapf_options['primary_color']) && is_scalar($wcapf_options['primary_color'])
        ? sanitize_hex_color((string) $wcapf_options['primary_color'])
        : '';

    return $primary_color ? $primary_color : '#c9a84c';
}

function dapfforwc_get_default_taxonomy_icon($group_name)
{
    $icons = [
        'category' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4H10l2 2h6.5A2.5 2.5 0 0 1 21 8.5v8A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5z"></path></svg>',
        'conference-by-month' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="4" width="18" height="17" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg>',
        'popular-cities' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
        'popular-countries' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18"></path><path d="M12 3a13 13 0 0 1 0 18"></path><path d="M12 3a13 13 0 0 0 0 18"></path></svg>',
        'popular-topics' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V4h9l8.6 8.6a2 2 0 0 1 0 2.8Z"></path><circle cx="7.5" cy="7.5" r=".5"></circle></svg>',
        'tag' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V4h9l8.6 8.6a2 2 0 0 1 0 2.8Z"></path><circle cx="7.5" cy="7.5" r=".5"></circle></svg>',
    ];

    $group_name = sanitize_key($group_name);

    return $icons[$group_name] ?? $icons['tag'];
}

function dapfforwc_get_default_view_all_text($group_name)
{
    $defaults = [
        'category' => __('View all categories', 'ajax-product-filter-for-woocommerce'),
        'conference-by-month' => __('View all months', 'ajax-product-filter-for-woocommerce'),
        'popular-cities' => __('View all cities', 'ajax-product-filter-for-woocommerce'),
        'popular-countries' => __('View all countries', 'ajax-product-filter-for-woocommerce'),
        'popular-topics' => __('View all topics', 'ajax-product-filter-for-woocommerce'),
        'tag' => __('View all tags', 'ajax-product-filter-for-woocommerce'),
    ];

    $group_name = sanitize_key($group_name);

    if (isset($defaults[$group_name])) {
        return $defaults[$group_name];
    }

    return sprintf(
        /* translators: %s: filter group title. */
        __('View all %s', 'ajax-product-filter-for-woocommerce'),
        strtolower(dapfforwc_get_filter_group_title($group_name))
    );
}

function dapfforwc_get_default_mobile_title($group_name)
{
    $defaults = [
        'category' => __('Category', 'ajax-product-filter-for-woocommerce'),
        'conference-by-month' => __('Month', 'ajax-product-filter-for-woocommerce'),
        'popular-cities' => __('Cities', 'ajax-product-filter-for-woocommerce'),
        'popular-countries' => __('Countries', 'ajax-product-filter-for-woocommerce'),
        'popular-topics' => __('Topics', 'ajax-product-filter-for-woocommerce'),
        'tag' => __('Tag', 'ajax-product-filter-for-woocommerce'),
    ];

    $group_name = sanitize_key($group_name);

    if (isset($defaults[$group_name])) {
        return $defaults[$group_name];
    }

    return dapfforwc_get_filter_group_title($group_name);
}

function dapfforwc_get_filter_group_title($group_name)
{
    return ucwords(str_replace('-', ' ', sanitize_key($group_name)));
}

function dapfforwc_get_filter_group_ui_settings($group_name)
{
    global $wcapf_options;

    $group_name = sanitize_key($group_name);
    $settings = isset($wcapf_options['taxonomy_settings'][$group_name]) && is_array($wcapf_options['taxonomy_settings'][$group_name])
        ? $wcapf_options['taxonomy_settings'][$group_name]
        : [];

    $svg_icon = isset($settings['svg_icon']) && is_scalar($settings['svg_icon']) ? wp_kses((string) $settings['svg_icon'], dapfforwc_get_allowed_svg_tags()) : '';
    $view_all_text = isset($settings['view_all_text']) && is_scalar($settings['view_all_text']) ? sanitize_text_field((string) $settings['view_all_text']) : '';
    $mobile_title = isset($settings['mobile_title']) && is_scalar($settings['mobile_title']) ? sanitize_text_field((string) $settings['mobile_title']) : '';

    return [
        'svg_icon' => $svg_icon !== '' ? $svg_icon : dapfforwc_get_default_taxonomy_icon($group_name),
        'view_all_text' => $view_all_text !== '' ? $view_all_text : dapfforwc_get_default_view_all_text($group_name),
        'mobile_title' => $mobile_title !== '' ? $mobile_title : dapfforwc_get_default_mobile_title($group_name),
    ];
}

function dapfforwc_render_filter_group_title($group_name, $items_id)
{
    $settings = dapfforwc_get_filter_group_ui_settings($group_name);
    $title = dapfforwc_get_filter_group_title($group_name);
    $toggle_label = sprintf(
        /* translators: %s: filter group title. */
        __('Toggle %s filter', 'ajax-product-filter-for-woocommerce'),
        $title
    );

    $output = '<div class="title dapfforwc-filter-title">';
    $output .= '<span class="dapfforwc-filter-heading">';
    $output .= '<span class="dapfforwc-filter-icon" aria-hidden="true">' . wp_kses($settings['svg_icon'], dapfforwc_get_allowed_svg_tags()) . '</span>';
    $output .= '<span class="dapfforwc-filter-title-text" data-mobile-title="' . esc_attr($settings['mobile_title']) . '">' . esc_html($title) . '</span>';
    $output .= '</span>';
    $output .= '<button type="button" class="dapfforwc-filter-toggle" aria-expanded="true" aria-controls="' . esc_attr($items_id) . '" aria-label="' . esc_attr($toggle_label) . '">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m18 15-6-6-6 6"></path></svg>';
    $output .= '</button>';
    $output .= '</div>';

    return $output;
}

function dapfforwc_render_filter_group($group_name, $items, $selected_items, $show_group, $attribute = false)
{
    $group_name = sanitize_key($group_name);
    $items = is_array($items) || is_object($items) ? array_values((array) $items) : [];
    $items_id = 'dapfforwc-filter-items-' . $group_name;
    $settings = dapfforwc_get_filter_group_ui_settings($group_name);
    $visible_items = 5;
    $has_hidden_options = false;

    $output = '<div id="' . esc_attr($group_name) . '" class="filter-group dapfforwc-filter-card ' . esc_attr($group_name) . '" data-filter-group="' . esc_attr($group_name) . '" style="display: ' . (!empty($show_group) ? 'block' : 'none') . ';">';
    $output .= dapfforwc_render_filter_group_title($group_name, $items_id);
    $output .= '<div id="' . esc_attr($items_id) . '" class="items">';

    // Sort items
    usort($items, function ($a, $b) {
        return dapfforwc_customSort(
            is_object($a) ? $a->name : $a['name'], 
            is_object($b) ? $b->name : $b['name']
        );
    });

    // Generate filter options
    if (is_array($items) || is_object($items)) {
    foreach ($items as $index => $item) {
        $name = is_object($item) ? esc_html($item->name) : esc_html($item['name']);
        $slug = is_object($item) ? esc_attr($item->slug) : esc_attr($item['slug']);
        $checked = in_array($slug, $selected_items) ? ' checked' : '';
        $hide_option = $index >= $visible_items && empty($checked);
        $has_hidden_options = $has_hidden_options || $hide_option;

        $output .= dapfforwc_render_filter_option($name, $slug, $checked, $attribute ? 'attribute[' . $group_name . ']' : $group_name, $hide_option);
    }}

    $output .= '</div>';
    if ($has_hidden_options) {
        $output .= '<button type="button" class="dapfforwc-view-all" data-collapsed-text="' . esc_attr($settings['view_all_text']) . '" data-expanded-text="' . esc_attr__('Show less', 'ajax-product-filter-for-woocommerce') . '">';
        $output .= '<span>' . esc_html($settings['view_all_text']) . '</span>';
        $output .= '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>';
        $output .= '</button>';
    }
    $output .= '</div>';

    return $output;
}
