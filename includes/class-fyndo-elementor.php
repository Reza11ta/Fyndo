<?php

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Plugin;

if (! defined('ABSPATH')) {
    exit;
}

class FYND_Elementor
{
    public static function init()
    {
        if (defined('ELEMENTOR_VERSION') && class_exists('Elementor\\Plugin')) {
            add_action('elementor/widgets/register', array(__CLASS__, 'register'));
        }
    }

    public static function register($widgets_manager)
    {
        if (version_compare(ELEMENTOR_VERSION, '3.5.0', '>=')) {
            $widgets_manager->register(new FYND_Elementor_Widget());
        } else {
            $widgets_manager->register_widget_type(new FYND_Elementor_Widget());
        }
    }
}

/**
 * Elementor Widget Class
 */
class FYND_Elementor_Widget extends Widget_Base
{
    public function get_name()
    {
        return 'fyndo_ajax_search';
    }

    public function get_title()
    {
        return 'Fyndo Ajax Search';
    }

    public function get_icon()
    {
        return 'eicon-search';
    }

    public function get_categories()
    {
        return array('general');
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_search_settings',
            [
                'label' => esc_html__('تنظیمات جستجو', 'fyndo-ajax-search'),
            ]
        );

        $this->add_control(
            'placeholder_text',
            [
                'label' => esc_html__('متن نگهدارنده', 'fyndo-ajax-search'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('جستجو...', 'fyndo-ajax-search'),
                'placeholder' => esc_html__('مثال: جستجو...', 'fyndo-ajax-search'),
            ]
        );

        $this->add_control(
            'results_per_page',
            [
                'label' => esc_html__('تعداد نتایج در هر صفحه', 'fyndo-ajax-search'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 50,
                'step' => 1,
                'default' => 5,
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $placeholder_text = ! empty($settings['placeholder_text']) ? $settings['placeholder_text'] : esc_html__('Search...', 'fyndo-ajax-search');
        $results_per_page = ! empty($settings['results_per_page']) ? intval($settings['results_per_page']) : 5;

        echo do_shortcode(sprintf(
            '[fyndo_ajax_search placeholder_text="%s" results_per_page="%d"]',
            esc_attr($placeholder_text),
            esc_attr($results_per_page)
        ));
    }
}
