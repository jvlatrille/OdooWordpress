<?php

if (!class_exists('ripcord')) {
    require_once __DIR__ . '/ripcord/ripcord.php';
}

global $odoo_url, $odoo_db, $odoo_username, $odoo_apikey;

$odoo_url = get_option('urlOdoo');
$odoo_db = get_option('dbOdoo');

$utilisateur_courant = wp_get_current_user();
$odoo_username = (!empty($utilisateur_courant->ID)) ? $utilisateur_courant->user_email : "";
$odoo_apikey = (!empty($utilisateur_courant->ID)) ? get_user_meta($utilisateur_courant->ID, 'odooapikey', true) : "";

function odooConnect()
{
    global $odoo_url, $odoo_db, $odoo_username, $odoo_apikey;

    if ($odoo_url == "" || $odoo_db == "" || $odoo_username == "" || $odoo_apikey == "") {
        return "";
    }

    $common = ripcord::client($odoo_url . "/xmlrpc/2/common");
    $common->version();
    return $common->authenticate($odoo_db, $odoo_username, $odoo_apikey, array());
}

function getAllImplants()
{
    global $odoo_url, $odoo_db, $odoo_apikey;

    $uid = odooConnect();
    if (empty($uid))
        return false;

    $models = ripcord::client($odoo_url . "/xmlrpc/2/object");

    $kwargs = [
        'order' => 'nom_implant asc',
        'domain' => [],
        'fields' => [
            'nom_implant',
            'type_implant',
            'rarete',
            'prix_euro',
            'cout_essence',
            'emplacement',
            'manufacturer_id',
            'image_implant',
            'actif'
        ]
    ];

    return $models->execute_kw(
        $odoo_db,
        $uid,
        $odoo_apikey,
        'cyberware.implant',
        'search_read',
        [],
        $kwargs
    );
}