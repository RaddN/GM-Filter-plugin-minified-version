<?php

if (!defined('ABSPATH')) {
    exit;
}

function dapfforwc_get_filter_permalink_word()
{
    $options = get_option('wcapf_options');
    $filter_word = is_array($options) && isset($options['filters_word_in_permalinks'])
        ? sanitize_title((string) $options['filters_word_in_permalinks'])
        : 'filters';

    return '' !== $filter_word ? $filter_word : 'filters';
}

function dapfforwc_get_current_filter_request_path()
{
    global $wp;

    if (isset($wp) && is_string($wp->request) && '' !== $wp->request) {
        return trim($wp->request, '/');
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    $path = wp_parse_url($request_uri, PHP_URL_PATH);
    if (!is_string($path)) {
        return '';
    }

    $home_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
    if (is_string($home_path) && '/' !== $home_path && 0 === strpos($path, $home_path)) {
        $path = substr($path, strlen($home_path));
    }

    return trim($path, '/');
}

function dapfforwc_parse_filter_permalink_request($request = null)
{
    $filter_word = dapfforwc_get_filter_permalink_word();
    $request = null === $request ? dapfforwc_get_current_filter_request_path() : (string) $request;
    $request_parts = array_values(array_filter(explode('/', trim($request, '/'))));
    $filter_index = array_search($filter_word, $request_parts, true);

    if (false === $filter_index) {
        return null;
    }

    $filter_values = array_slice($request_parts, $filter_index + 1);
    if (empty($filter_values)) {
        return null;
    }

    $page_parts = array_slice($request_parts, 0, $filter_index);
    $page_index = array_search('page', $page_parts, true);
    $paged = 1;

    if (false !== $page_index && isset($page_parts[$page_index + 1]) && is_numeric($page_parts[$page_index + 1])) {
        $paged = max(1, absint($page_parts[$page_index + 1]));
        array_splice($page_parts, $page_index, 2);
    }

    $filter_values = array_map(
        function ($value) {
            return trim(sanitize_text_field(rawurldecode((string) $value)), '/');
        },
        $filter_values
    );

    return array(
        'page_slug' => implode('/', array_map('sanitize_title', $page_parts)),
        'filters'   => implode('/', array_values(array_filter($filter_values))),
        'paged'     => $paged,
    );
}

function dapfforwc_register_filter_query_var($vars)
{
    $vars[] = 'filters';

    return array_values(array_unique($vars));
}

function dapfforwc_map_filter_permalink_request($query_vars)
{
    $filter_request = dapfforwc_parse_filter_permalink_request();
    if (null === $filter_request || empty($filter_request['filters'])) {
        return $query_vars;
    }

    unset(
        $query_vars['attachment'],
        $query_vars['category_name'],
        $query_vars['error'],
        $query_vars['name'],
        $query_vars['page'],
        $query_vars['paged'],
        $query_vars['pagename'],
        $query_vars['page_id']
    );

    if ('' !== $filter_request['page_slug']) {
        $query_vars['pagename'] = $filter_request['page_slug'];
    } elseif ('page' === get_option('show_on_front')) {
        $front_page_id = absint(get_option('page_on_front'));
        if ($front_page_id > 0) {
            $query_vars['page_id'] = $front_page_id;
        }
    }

    $query_vars['filters'] = $filter_request['filters'];

    return $query_vars;
}

function dapfforwc_disable_filter_permalink_canonical($redirect_url)
{
    return null === dapfforwc_parse_filter_permalink_request() ? $redirect_url : false;
}

add_filter('query_vars', 'dapfforwc_register_filter_query_var');
add_filter('request', 'dapfforwc_map_filter_permalink_request');
add_filter('redirect_canonical', 'dapfforwc_disable_filter_permalink_canonical');
