<?php
/*
Plugin Name: CyberwareClient
Description: Plugin pour gérer les clients cyberware de votre boutique.
Version: 1.0
*/

if (!defined('ABSPATH'))
    exit;

define('cyberwareclient_DIR', plugin_dir_path(__FILE__));
define('cyberwareclient_URL', plugin_dir_url(__FILE__));

require_once cyberwareclient_DIR . 'back.php';
require_once cyberwareclient_DIR . 'front.php';

function cyberwareclient_enqueue_assets()
{
    wp_enqueue_style('cyberware_css', cyberwareclient_URL . 'assets/css/cyberware.css', [], '1.0');
    wp_enqueue_script('cyberware_js', cyberwareclient_URL . 'assets/js/cyberware.js', [], '1.0', true);
}
add_action('wp_enqueue_scripts', 'cyberwareclient_enqueue_assets');

function cyberwareclient_on_activate()
{
    $slug = 'cyberwareclient';
    if (!get_page_by_path($slug)) {
        wp_insert_post([
            'post_title' => 'Cyberware Clients',
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => 'Page support CyberwareClient',
        ]);
    }
}
register_activation_hook(__FILE__, 'cyberwareclient_on_activate');
