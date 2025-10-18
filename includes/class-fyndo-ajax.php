<?php

if (! defined('ABSPATH')) {
    exit;
}

class FYND_Ajax
{
    public function init()
    {
        add_action('wp_ajax_fyndo_search', array($this, 'handle_search'));
        add_action('wp_ajax_nopriv_fyndo_search', array($this, 'handle_search'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    public function handle_search()
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'fyndo_search_nonce')) {
            wp_send_json_error(array('message' => esc_html__('درخواست نامعتبر است', 'fyndo-ajax-search')), 400);
        }

        $search_query = isset($_POST['q']) ? sanitize_text_field(wp_unslash($_POST['q'])) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 5;

        if ($per_page <= 0) {
            $per_page = 5;
        }
        if ($per_page > 50) {
            $per_page = 50;
        }
        if ($page <= 0) {
            $page = 1;
        }

        if (mb_strlen($search_query) < 3) {
            wp_send_json_error(array('message' => esc_html__('لطفاً حداقل ۳ کاراکتر وارد کنید', 'fyndo-ajax-search')), 422);
        }

        $cache_key = 'fyndo_search_' . md5(strtolower($search_query) . "_p{$page}_r{$per_page}");
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            wp_send_json_success($cached);
        }

        if (! post_type_exists('product')) {
            wp_send_json_error(array('message' => esc_html__('نوع پست "product" پیدا نشد. لطفاً ووکامرس را نصب و فعال کنید.', 'fyndo-ajax-search')), 400);
        }
        $post_types = array('product');
        $args = array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            's'              => $search_query,
            'posts_per_page' => $per_page,
            'offset'         => ($page - 1) * $per_page,
            'fields'         => 'ids',
        );

        $query = new WP_Query($args);

        $items = array();
        $q = mb_strtolower($search_query);

        if ($query->have_posts()) {
            foreach ($query->posts as $post_id) {
                $post_obj = get_post($post_id);
                if (! $post_obj) {
                    continue;
                }

                $title = (string) $post_obj->post_title;
                $title_l = mb_strtolower($title);
                $excerpt = (string) $post_obj->post_excerpt;
                $excerpt_l = mb_strtolower($excerpt);

                $score = 0;
                if ('' !== $q) {
                    if (0 === mb_strpos($title_l, $q)) {
                        $score += 100;
                    } elseif (false !== mb_strpos($title_l, $q)) {
                        $score += 70;
                    }
                    if ($excerpt && false !== mb_strpos($excerpt_l, $q)) {
                        $score += 40;
                    }
                }

                $date = $post_obj ? strtotime($post_obj->post_date_gmt) : 0;

                $thumbnail = get_the_post_thumbnail_url($post_id, 'thumbnail');
                $thumbnail_url = $thumbnail ? $thumbnail : '';

                $regular_price = '';
                $sale_price = '';
                $categories = array();

                if (get_post_type($post_id) === 'product' && function_exists('wc_get_product')) {
                    $product = wc_get_product($post_id);
                    if ($product) {
                        $regular_raw = $product->get_regular_price();
                        $sale_raw = $product->get_sale_price();
                        if (function_exists('wc_price')) {
                            $regular_price = $regular_raw !== '' ? wc_price($regular_raw) : '';
                            $sale_price = $sale_raw !== '' ? wc_price($sale_raw) : '';
                        } else {
                            $regular_price = $regular_raw !== '' ? (string) $regular_raw : '';
                            $sale_price = $sale_raw !== '' ? (string) $sale_raw : '';
                        }
                    }
                    $categories = array_map('sanitize_text_field', wp_get_post_terms($post_id, 'product_cat', array('fields' => 'names')));
                } else {
                    $categories = array_map('sanitize_text_field', wp_get_post_terms($post_id, 'category', array('fields' => 'names')));
                }

                $items[] = array(
                    'id' => intval($post_id),
                    'title' => wp_kses_post($title),
                    'permalink' => esc_url_raw(get_permalink($post_id)),
                    'thumbnail' => esc_url_raw($thumbnail_url),
                    'regular_price' => $regular_price,
                    'sale_price' => $sale_price,
                    'categories' => $categories,
                    'score' => $score,
                    'date' => $date,
                );
            }
        }

        usort($items, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $b['date'] <=> $a['date'];
            }
            return $b['score'] <=> $a['score'];
        });

        $items = array_slice($items, 0, $per_page);

        $response = array(
            'query' => $search_query,
            'page' => $page,
            'per_page' => $per_page,
            'found' => intval($query->found_posts),
            'results' => $items,
        );

        set_transient($cache_key, $response, 30);

        wp_send_json_success($response);
    }

    public function register_rest_routes()
    {
        register_rest_route('fyndo/v1', '/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_search'),
            'permission_callback' => '__return_true',
            'args' => array(
                'q' => array('required' => true),
                'page' => array('required' => false),
                'per_page' => array('required' => false),
            ),
        ));
    }

    public function rest_search($request)
    {
        $q = isset($request['q']) ? sanitize_text_field($request['q']) : '';
        $page = isset($request['page']) ? intval($request['page']) : 1;
        $per_page = isset($request['per_page']) ? intval($request['per_page']) : 5;

        $_POST['q'] = $q;
        $_POST['page'] = $page;
        $_POST['per_page'] = $per_page;
        return rest_ensure_response($this->get_search_results($q, $page, $per_page));
    }

    protected function get_search_results($search_query, $page = 1, $per_page = 5)
    {
        $cache_key = 'fyndo_search_' . md5(strtolower($search_query) . "_p{$page}_r{$per_page}");
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        if (! post_type_exists('product')) {
            return array(
                'query' => $search_query,
                'page' => $page,
                'per_page' => $per_page,
                'found' => 0,
                'results' => array(),
            );
        }
        $post_types = array('product');
        $args = array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            's'              => $search_query,
            'posts_per_page' => $per_page,
            'offset'         => ($page - 1) * $per_page,
            'fields'         => 'ids',
        );

        $query = new WP_Query($args);

        $items = array();
        $q = mb_strtolower($search_query);

        if ($query->have_posts()) {
            foreach ($query->posts as $post_id) {
                $post_obj = get_post($post_id);
                if (! $post_obj) {
                    continue;
                }

                $title = (string) $post_obj->post_title;
                $title_l = mb_strtolower($title);
                $excerpt = (string) $post_obj->post_excerpt;
                $excerpt_l = mb_strtolower($excerpt);

                $score = 0;
                if ('' !== $q) {
                    if (0 === mb_strpos($title_l, $q)) {
                        $score += 100;
                    } elseif (false !== mb_strpos($title_l, $q)) {
                        $score += 70;
                    }
                    if ($excerpt && false !== mb_strpos($excerpt_l, $q)) {
                        $score += 40;
                    }
                }

                $date = $post_obj ? strtotime($post_obj->post_date_gmt) : 0;

                $thumbnail = get_the_post_thumbnail_url($post_id, 'thumbnail');
                $thumbnail_url = $thumbnail ? $thumbnail : '';

                $regular_price = '';
                $sale_price = '';
                $categories = array();

                if (get_post_type($post_id) === 'product' && function_exists('wc_get_product')) {
                    $product = wc_get_product($post_id);
                    if ($product) {
                        $regular_raw = $product->get_regular_price();
                        $sale_raw = $product->get_sale_price();
                        if (function_exists('wc_price')) {
                            $regular_price = $regular_raw !== '' ? wc_price($regular_raw) : '';
                            $sale_price = $sale_raw !== '' ? wc_price($sale_raw) : '';
                        } else {
                            $regular_price = $regular_raw !== '' ? (string) $regular_raw : '';
                            $sale_price = $sale_raw !== '' ? (string) $sale_raw : '';
                        }
                    }
                    $categories = array_map('sanitize_text_field', wp_get_post_terms($post_id, 'product_cat', array('fields' => 'names')));
                } else {
                    $categories = array_map('sanitize_text_field', wp_get_post_terms($post_id, 'category', array('fields' => 'names')));
                }

                $items[] = array(
                    'id' => intval($post_id),
                    'title' => wp_kses_post($title),
                    'permalink' => esc_url_raw(get_permalink($post_id)),
                    'thumbnail' => esc_url_raw($thumbnail_url),
                    'regular_price' => $regular_price,
                    'sale_price' => $sale_price,
                    'categories' => $categories,
                    'score' => $score,
                    'date' => $date,
                );
            }
        }

        usort($items, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $b['date'] <=> $a['date'];
            }
            return $b['score'] <=> $a['score'];
        });

        $items = array_slice($items, 0, $per_page);

        $response = array(
            'query' => $search_query,
            'page' => $page,
            'per_page' => $per_page,
            'found' => intval($query->found_posts),
            'results' => $items,
        );

        set_transient($cache_key, $response, 30);

        return $response;
    }
}
