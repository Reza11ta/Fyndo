<?php

/**
 * Plugin Name: Fyndo (Ajax Product Search)
 * Plugin URI:  https://example.com
 * Description: سریع , امن , استفاده از سیستم سرچ در لحظه ajax
 * Version:     1.0.0
 * Author:      Reza Tavakoli
 * Author URI:  https://reza11ta.github.io/portfolio
 * Text Domain: fyndo-ajax-search
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

if (! defined('ABSPATH')) {
    exit;
}

define('FYND_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FYND_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FYND_VERSION', '1.0.0');

if (! defined('FYND_UPDATE_MANIFEST')) {
    define('FYND_UPDATE_MANIFEST', 'https://example.com/fyndo/manifest.json');
}

function fyndo_check_for_update($transient)
{
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_file = plugin_basename(__FILE__);
    $manifest = get_transient('fyndo_update_manifest');

    if (false === $manifest) {
        $resp = wp_remote_get(FYND_UPDATE_MANIFEST, array('timeout' => 10, 'sslverify' => true));
        if (is_wp_error($resp) || 200 !== wp_remote_retrieve_response_code($resp)) {
            return $transient;
        }
        $body = wp_remote_retrieve_body($resp);
        $manifest = json_decode($body);
        if (! $manifest || ! isset($manifest->version) || ! isset($manifest->download_url)) {
            return $transient;
        }
        set_transient('fyndo_update_manifest', $manifest, 6 * HOUR_IN_SECONDS);
    }

    if (version_compare($manifest->version, FYND_VERSION, '>')) {
        $update = new stdClass();
        $update->slug = 'my-ajax-search';
        $update->plugin = $plugin_file;
        $update->new_version = $manifest->version;
        $update->url = isset($manifest->homepage) ? $manifest->homepage : '';
        $update->package = $manifest->download_url;
        $transient->response[$plugin_file] = $update;
    }

    return $transient;
}
add_filter('pre_set_site_transient_update_plugins', 'fyndo_check_for_update');


function fyndo_plugins_api($res, $action, $args)
{
    if ('plugin_information' !== $action) {
        return $res;
    }


    $accepted_slugs = array('my-ajax-search', 'fyndo-ajax-search');
    if (empty($args->slug) || ! in_array($args->slug, $accepted_slugs, true)) {
        return $res;
    }

    $manifest = get_transient('fyndo_update_manifest');
    if (false === $manifest) {
        $resp = wp_remote_get(FYND_UPDATE_MANIFEST, array('timeout' => 10, 'sslverify' => true));
        if (is_wp_error($resp) || 200 !== wp_remote_retrieve_response_code($resp)) {
            return $res;
        }
        $body = wp_remote_retrieve_body($resp);
        $manifest = json_decode($body);
        if (! $manifest) {
            return $res;
        }
        set_transient('fyndo_update_manifest', $manifest, 6 * HOUR_IN_SECONDS);
    }

    $info = new stdClass();
    $info->name = isset($manifest->name) ? $manifest->name : 'Fyndo (Ajax Product Search)';
    $info->slug = 'my-ajax-search';
    $info->version = isset($manifest->version) ? $manifest->version : FYND_VERSION;
    $info->author = isset($manifest->author) ? $manifest->author : 'Reza Tavakoli';
    $info->homepage = isset($manifest->homepage) ? $manifest->homepage : '';
    $info->sections = array(
        'description' => isset($manifest->changelog) ? $manifest->changelog : '',
    );
    $info->download_link = isset($manifest->download_url) ? $manifest->download_url : '';

    return $info;
}
add_filter('plugins_api', 'fyndo_plugins_api', 10, 3);


function fyndo_check_dependencies()
{
    $has_wc = class_exists('WooCommerce');
    $has_elementor = defined('ELEMENTOR_VERSION') || class_exists('Elementor\\Plugin') || class_exists('Elementor\\Widget_Base');

    if (! $has_wc && ! $has_elementor) {
        if (is_admin() && current_user_can('activate_plugins')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
            deactivate_plugins(plugin_basename(__FILE__));
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Fyndo requires WooCommerce or Elementor. The plugin has been deactivated.', 'fyndo-ajax-search') . '</p></div>';
            });
        }
        return false;
    }

    return true;
}
add_action('plugins_loaded', 'fyndo_check_dependencies', 5);

require_once FYND_PLUGIN_DIR . 'includes/class-fyndo-ajax.php';
require_once FYND_PLUGIN_DIR . 'includes/class-fyndo-admin.php';

function fyndo_init_plugin()
{
    // Ajax core
    $fyndo_ajax = new FYND_Ajax();
    $fyndo_ajax->init();
    FYND_Admin::init();

    if (defined('ELEMENTOR_VERSION') && class_exists('\Elementor\Widget_Base')) {
        require_once FYND_PLUGIN_DIR . 'includes/class-fyndo-elementor.php';
        FYND_Elementor::init();
    } else {
        add_action('elementor/loaded', function () {
            if (class_exists('\Elementor\Widget_Base')) {
                require_once FYND_PLUGIN_DIR . 'includes/class-fyndo-elementor.php';
                FYND_Elementor::init();
            }
        });
    }
}
$has_wc = class_exists('WooCommerce');
$has_elementor = defined('ELEMENTOR_VERSION') || class_exists('Elementor\\Plugin') || class_exists('Elementor\\Widget_Base');

if ($has_wc || $has_elementor) {
    add_action('plugins_loaded', 'fyndo_init_plugin');
} else {
    if (is_admin() && current_user_can('activate_plugins')) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        update_option('fyndo_auto_deactivated', 1);
        deactivate_plugins(plugin_basename(__FILE__));
        add_action('admin_notices', function () {
            $install_wc = esc_url(admin_url('plugin-install.php?s=woocommerce&tab=search&type=term'));
            $install_elementor = esc_url(admin_url('plugin-install.php?s=elementor&tab=search&type=term'));
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Fyndo requires WooCommerce or Elementor. The plugin has been deactivated to prevent errors.', 'fyndo-ajax-search') . '</p>';
            echo '<p>' . sprintf(esc_html__('Install/activate %s or %s and then re-activate Fyndo.', 'fyndo-ajax-search'), '<a href="' . $install_wc . '">WooCommerce</a>', '<a href="' . $install_elementor . '">Elementor</a>') . '</p>';
            echo '</div>';
        });
    }
    add_action('activated_plugin', function ($plugin) {
        $flag = get_option('fyndo_auto_deactivated');
        if (! $flag) {
            return;
        }
        $has_wc_now = class_exists('WooCommerce');
        $has_elementor_now = defined('ELEMENTOR_VERSION') || class_exists('Elementor\\Plugin') || class_exists('Elementor\\Widget_Base');
        if ($has_wc_now || $has_elementor_now) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
            $try = activate_plugin(plugin_basename(__FILE__));
            if (! is_wp_error($try)) {
                delete_option('fyndo_auto_deactivated');
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Fyndo has been automatically re-activated after required dependency installation.', 'fyndo-ajax-search') . '</p></div>';
                });
            }
        }
    }, 10, 1);
    add_action('upgrader_process_complete', function ($upgrader, $options) {
        if (get_option('fyndo_auto_deactivated')) {
            $has_wc_now = class_exists('WooCommerce');
            $has_elementor_now = defined('ELEMENTOR_VERSION') || class_exists('Elementor\\Plugin') || class_exists('Elementor\\Widget_Base');
            if ($has_wc_now || $has_elementor_now) {
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
                $try = activate_plugin(plugin_basename(__FILE__));
                if (! is_wp_error($try)) {
                    delete_option('fyndo_auto_deactivated');
                    add_action('admin_notices', function () {
                        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Fyndo has been automatically re-activated after required dependency installation.', 'fyndo-ajax-search') . '</p></div>';
                    });
                }
            }
        }
    }, 10, 2);
}


function fyndo_render_search_shortcode($atts = array())
{
    $atts = shortcode_atts(array(
        'results_per_page' => 5,
        'placeholder_text' => esc_html__('جستجو...', 'fyndo-ajax-search'),
    ), $atts, 'fyndo_ajax_search');

    $results_per_page = intval($atts['results_per_page']);
    $placeholder      = sanitize_text_field($atts['placeholder_text']);

    if ($results_per_page <= 0 || $results_per_page > 50) {
        $results_per_page = 5;
    }

    wp_enqueue_style(
        'fyndo-frontend',
        FYND_PLUGIN_URL . 'assets/css/my-ajax-search.css',
        array(),
        FYND_VERSION
    );

    wp_enqueue_script(
        'fyndo-frontend',
        FYND_PLUGIN_URL . 'assets/js/my-ajax-search.js',
        array('jquery'),
        FYND_VERSION,
        true
    );

    wp_localize_script('fyndo-frontend', 'fyndo_ajax', array(
        'ajax_url'         => admin_url('admin-ajax.php'),
        'nonce'            => wp_create_nonce('fyndo_search_nonce'),
        'results_per_page' => $results_per_page,
        'i18n_no_results'  => esc_html__('محصولی یافت نشد', 'fyndo-ajax-search'),
        'i18n_error'       => esc_html__('خطا رخ داد', 'fyndo-ajax-search'),
    ));

    $search_page = esc_url(home_url('/'));
    $instance_id = 'fyndo_' . wp_unique_id();

    ob_start(); ?>
    <form class="fyndo-search-form mas-search-form" method="get" action="<?php echo $search_page; ?>" data-fyndo-instance="<?php echo esc_attr($instance_id); ?>" data-results-per-page="<?php echo esc_attr($results_per_page); ?>">
        <label for="fyndo-search-input" class="screen-reader-text">
            <?php echo esc_html__('جستجو', 'fyndo-ajax-search'); ?>
        </label>
        <input id="<?php echo esc_attr($instance_id . '_input'); ?>" name="s" type="search"
            placeholder="<?php echo esc_attr($placeholder); ?>"
            aria-label="<?php echo esc_attr__('Search', 'fyndo-ajax-search'); ?>">
        <noscript>
            <button type="submit"><?php echo esc_html__('Search', 'fyndo-ajax-search'); ?></button>
        </noscript>
        <div id="<?php echo esc_attr($instance_id . '_live'); ?>" class="fyndo-search-live mas-search-live" role="status" aria-live="polite"></div>
        <ul id="<?php echo esc_attr($instance_id . '_results'); ?>" class="fyndo-search-results mas-search-results" role="list"></ul>
    </form>
<?php
    return ob_get_clean();
}
add_shortcode('fyndo_ajax_search', 'fyndo_render_search_shortcode');


function fyndo_activate_plugin()
{
    $has_wc = class_exists('WooCommerce');
    $has_elementor = defined('ELEMENTOR_VERSION') || class_exists('Elementor\\Plugin') || class_exists('Elementor\\Widget_Base');

    if (! $has_wc && ! $has_elementor) {
        if (is_admin() && current_user_can('activate_plugins')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('افزونهٔ فایندو نیاز به نصب و فعال‌سازی ووکامرس یا المنتور دارد. افزونه غیرفعال شد.', 'fyndo-ajax-search'), esc_html__('وابستگی لازم', 'fyndo-ajax-search'), array('back_link' => true));
        }
    }
}
register_activation_hook(__FILE__, 'fyndo_activate_plugin');

function fyndo_deactivate_plugin() {}
register_deactivation_hook(__FILE__, 'fyndo_deactivate_plugin');
