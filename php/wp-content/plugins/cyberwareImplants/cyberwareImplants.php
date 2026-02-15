<?php
/*
Plugin Name: CyberwareImplant
Description: CRUD WordPress <-> Odoo pour cyberware.implant (avec photos)
Version: 1.0
*/

if (!defined('ABSPATH'))
    exit;

require_once __DIR__ . '/back.php';
require_once __DIR__ . '/front.php';

register_activation_hook(__FILE__, 'cyberwareimplant_creer_page_support');

function cyberwareimplant_creer_page_support()
{
    $slug = 'cyberwareimplants';
    $titre = 'Cyberware Implants';

    $page = get_page_by_path($slug, OBJECT, 'page');
    if ($page) {
        return;
    }
    wp_insert_post([
        'post_title' => $titre,
        'post_name' => $slug,
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_content' => 'Page support CyberwareImplant',
    ]);
}

function cyberwareimplant_enqueue_assets()
{
    if (!is_page('cyberwareimplants'))
        return;

    wp_enqueue_style(
        'cyberwareimplant-style',
        plugin_dir_url(__FILE__) . 'assets/style.css',
        [],
        '1.0'
    );

    wp_enqueue_script(
        'cyberwareimplant-script',
        plugin_dir_url(__FILE__) . 'assets/script.js',
        [],
        '1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'cyberwareimplant_enqueue_assets');
