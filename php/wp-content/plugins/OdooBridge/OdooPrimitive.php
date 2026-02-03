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

function getClientIdCourant($uid)
{
    global $odoo_url, $odoo_db, $odoo_apikey;

    $models = ripcord::client($odoo_url . "/xmlrpc/2/object");

    // on suppose que ton modèle cyberware.client a un champ user_id (logique avec ton related email_user)
    $ids = $models->execute_kw(
        $odoo_db,
        $uid,
        $odoo_apikey,
        'cyberware.client',
        'search',
        [[['user_id', '=', $uid]]],
        ['limit' => 1]
    );

    if (is_array($ids) && count($ids) > 0) {
        return intval($ids[0]);
    }
    return 0;
}

function creerDemandeImplantation($implant_id, $date_reservation, $duree_reservation)
{
    global $odoo_url, $odoo_db, $odoo_apikey;

    try {
        $uid = odooConnect();
        if (empty($uid)) {
            throw new Exception("Connexion Odoo impossible (options / clé API / email).");
        }

        $implant_id = intval($implant_id);
        $duree_reservation = intval($duree_reservation);
        $date_reservation = sanitize_text_field($date_reservation);

        if ($implant_id <= 0 || $duree_reservation <= 0 || $date_reservation === '') {
            throw new Exception("Champs invalides.");
        }

        $client_id = getClientIdCourant($uid);
        if ($client_id <= 0) {
            throw new Exception("Aucun client cyberware lié à cet utilisateur Odoo.");
        }

        $models = ripcord::client($odoo_url . "/xmlrpc/2/object");

        // ATTENTION : si tes champs s’appellent différemment dans cyberware.implantation,
        // c’est ICI qu’on ajustera (client_id / implant_id / date / durée)
        $valeurs = [
            'client_id' => $client_id,
            'implant_id' => $implant_id,
            'date_implantation' => $date_reservation,
            'duree' => $duree_reservation,
        ];

        $id_cree = $models->execute_kw(
            $odoo_db,
            $uid,
            $odoo_apikey,
            'cyberware.implantation',
            'create',
            [$valeurs]
        );

        return $id_cree;
    } catch (Exception $e) {
        set_transient('odoobridge_erreur', $e->getMessage(), 30);
        return false;
    }
}
