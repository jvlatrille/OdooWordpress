<?php
/*
Plugin Name: CyberwareCRUD
Description: CRUD cyberware.client via Odoo XML-RPC (pagination + recherche).
Version: 1.0
*/

if (!defined('ABSPATH'))
    exit;

define('CYBERCRUD_DIR', plugin_dir_path(__FILE__));
define('CYBERCRUD_URL', plugin_dir_url(__FILE__));

require_once CYBERCRUD_DIR . 'back.php';
require_once CYBERCRUD_DIR . 'front.php';

function cybercrud_enqueue_assets()
{
    wp_enqueue_style('cyberware_css', CYBERCRUD_URL . 'assets/css/cyberware.css', [], '1.0');
    wp_enqueue_script('cyberware_js', CYBERCRUD_URL . 'assets/js/cyberware.js', [], '1.0', true);
}
add_action('wp_enqueue_scripts', 'cybercrud_enqueue_assets');

/**
 * Page support auto (facultatif mais pratique, comme le tuto OdooBridge)
 */
function cybercrud_on_activate()
{
    $slug = 'cybercrud-clients';
    if (!get_page_by_path($slug)) {
        wp_insert_post([
            'post_title' => 'Cyberware Clients',
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => 'Page support CyberwareCRUD',
        ]);
    }
}
register_activation_hook(__FILE__, 'cybercrud_on_activate');
