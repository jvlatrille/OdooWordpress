<?php
/**
 * Plugin Name: OdooBridge
 * Plugin URI:  http://localhost:8000
 * Description: Pont WordPress <-> Odoo (Cyberware)
 * Version:     1.0.0
 * Author:      Jules
 * Text Domain: OdooBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ODOOBRIDGE_DIR', plugin_dir_path(__FILE__));
define('ODOOBRIDGE_URL', plugin_dir_url(__FILE__));

function odooBridgeInstall()
{
    $check_page_exist = get_page_by_path('odooreservation', OBJECT, 'page');

    if (empty($check_page_exist)) {
        $page_id = wp_insert_post(
            array(
                'post_author' => 1,
                'post_title' => 'Réservation',
                'post_name' => 'odooreservation',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '',
                'post_parent' => ''
            )
        );

        if (is_wp_error($page_id)) {
            error_log('OdooBridge: erreur creation page -> ' . $page_id->get_error_message());
        }
    }
}
register_activation_hook(__FILE__, 'odooBridgeInstall');

function odooBridgeUninstall()
{
    $page = get_page_by_path('odooreservation', OBJECT, 'page');
    if ($page && isset($page->ID)) {
        wp_delete_post($page->ID, true);
    }
}
register_deactivation_hook(__FILE__, 'odooBridgeUninstall');

function add_plugins_scripts()
{
    if (is_page('odooreservation') || is_admin()) {
        wp_enqueue_style(
            'styleodoo',
            plugin_dir_url(__FILE__) . 'assets/css/odoostyle.css',
            array(),
            '1.1',
            'all'
        );

        wp_enqueue_script(
            'scriptodoo',
            plugin_dir_url(__FILE__) . 'assets/js/odooscript.js',
            array(),
            '1.1',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'add_plugins_scripts');
add_action('admin_enqueue_scripts', 'add_plugins_scripts');


add_action('plugins_loaded', 'loadOdooBridge');

function loadOdooBridge()
{
    require_once __DIR__ . '/back.php';
    require_once __DIR__ . '/front.php';
}
