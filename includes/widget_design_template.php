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
        $formOutPut .= render_filter_group('category', $updated_filters["categories"], $selected_categories, isset($wcapf_options['show_categories']) ? $wcapf_options['show_categories'] : false);
    }

    // Render attributes
    if (!empty($updated_filters["attributes"])) {
        $formOutPut .= '<div class="filter-group attributes" style="display: ' . (!empty($wcapf_options['show_attributes']) ? 'block' : 'none') . ';">';
        if (is_array($updated_filters["attributes"]) || is_object($updated_filters["attributes"])) {
        foreach ($updated_filters["attributes"] as $attribute_name => $terms) {
            $formOutPut .= render_filter_group($attribute_name, $terms, $default_filter, true, true);
        }}
        $formOutPut .= '</div>';
    }

    // Render tags
    if (!empty($updated_filters["tags"])) {
        $formOutPut .= render_filter_group('tag', $updated_filters["tags"], $default_filter, isset($wcapf_options['show_tags']) ? $wcapf_options['show_tags'] : false);
    }

    return $formOutPut;
}

function render_filter_group($group_name, $items, $selected_items, $show_group, $attribute = false)
{
    $output = '<div id="' . esc_attr($group_name) . '" class="filter-group ' . esc_attr($group_name) . '" style="display: ' . (!empty($show_group) ? 'block' : 'none') . ';">';
    $output .= '<div class="title">' . ucwords(str_replace('-', ' ', $group_name)) . '</div>';
    $output .= '<div class="items">';

    // Sort items
    usort($items, function ($a, $b) {
        return dapfforwc_customSort(
            is_object($a) ? $a->name : $a['name'], 
            is_object($b) ? $b->name : $b['name']
        );
    });

    // Generate filter options
    if (is_array($items) || is_object($items)) {
    foreach ($items as $item) {
        $name = is_object($item) ? esc_html($item->name) : esc_html($item['name']);
        $slug = is_object($item) ? esc_attr($item->slug) : esc_attr($item['slug']);
        $checked = in_array($slug, $selected_items) ? ' checked' : '';

        $output .= dapfforwc_render_filter_option($name, $slug, $checked, $attribute ? 'attribute[' . $group_name . ']' : $group_name);
    }}

    $output .= '</div></div>';
    return $output;
}
