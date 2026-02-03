<?php
/**
 * Plugin Name: bandeauInfo
 * Plugin URI:  https://www.bandeauInfo.fr
 * Description: Ajoutez un bandeau de texte sur toute les pages du site 
 * Version:     1.0.0
 * Author:      Yann Carpentier
 * Author URI:  https://www.iutbayonne.univ-pau.fr/
 * Text Domain: bandeauInfo
 */

function administration_add_admin_page()
{
    add_submenu_page(
        'options-general.php',
        'Options Bandeau en haut page',
        'Options Bandeau onglet page',
        'manage_options',
        'administration',
        'administration_page'
    );
}

function administration_page()
{

    if (isset($_POST['message_bandeau'])) {
        update_option('message_bandeau', sanitize_text_field($_POST['message_bandeau']));
        echo '<div class="updated"><p>Message enregistré.</p></div>';
    }

    $message = get_option('message_bandeau', '');

    echo '<div class="wrap">';
    echo '<h1>Options Bandeau</h1>';

    echo '<form method="post">';
    echo '<label for="message_bandeau">Message du bandeau :</label><br>';
    echo '<input type="text" id="message_bandeau" name="message_bandeau" value="' . esc_attr($message) . '" style="width: 400px;">';
    echo '<br><br>';
    echo '<input type="submit" class="button button-primary" value="Enregistrer">';
    echo '</form>';

    echo '</div>';
}


add_action('admin_menu', 'administration_add_admin_page');

function ajouter_bandeau($content)
{
    $message = get_option('message_bandeau');
    $moncontenu = "";
    if ((is_page() || is_single() || is_home() || is_front_page()) && get_option('message_bandeau') != "") {
        $moncontenu = '<div style=\'float:left;position:absolute;left:0;top:0;text-align:center;font-size:20px;width:100%;height:15px;padding:20px;color:white;background-color:black \'>' . $message . '</div>';
    }
    return $moncontenu . $content;
}

add_filter('the_title', 'ajouter_bandeau');
