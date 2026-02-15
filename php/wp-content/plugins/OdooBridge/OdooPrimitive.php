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
            'id',
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

    $ids = $models->execute_kw(
        $odoo_db,
        (int) $uid,
        $odoo_apikey,
        'cyberware.client',
        'search',
        [[['user_id', '=', (int) $uid]]],
        ['limit' => 1]
    );

    if (is_array($ids) && count($ids) > 0) {
        return (int) $ids[0];
    }

    $wp_user = wp_get_current_user();
    if (empty($wp_user->ID)) {
        return 0;
    }

    $nom = $wp_user->display_name ?: $wp_user->user_login ?: 'Client';
    $pseudo = $wp_user->user_login ?: '';

    $valeurs = [
        'nom_client' => $nom,
        'pseudo' => $pseudo,
        'user_id' => (int) $uid,
        'niveau_essence_max' => 100,
        'actif' => true,
    ];

    $id_cree = $models->execute_kw(
        $odoo_db,
        (int) $uid,
        $odoo_apikey,
        'cyberware.client',
        'create',
        [$valeurs]
    );

    return (int) $id_cree;
}

function normaliser_datetime_odoo($val)
{
    $val = trim((string) $val);

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
        return $val . ' 00:00:00';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $val)) {
        return str_replace('T', ' ', $val) . ':00';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $val)) {
        return $val;
    }

    return '';
}

function creerDemandeImplantation($implant_id, $date_implantation)
{
    global $odoo_url, $odoo_db, $odoo_apikey;

    try {
        $uid = odooConnect();
        if (empty($uid)) {
            throw new Exception("Connexion Odoo impossible (options / clé API / email).");
        }

        $implant_id = (int) $implant_id;
        $date_implantation = normaliser_datetime_odoo(sanitize_text_field($date_implantation));

        if ($implant_id <= 0 || $date_implantation === '') {
            throw new Exception("Champs invalides (implant/date).");
        }

        $client_id = getClientIdCourant($uid);
        if ($client_id <= 0) {
            throw new Exception("Aucun client cyberware lié à cet utilisateur Odoo.");
        }

        $models = ripcord::client($odoo_url . "/xmlrpc/2/object");

        $valeurs = [
            'client_id' => (int) $client_id,
            'implant_id' => (int) $implant_id,
            'date_implantation' => $date_implantation,

            'charcudoc_id' => (int) $uid,
        ];

        $id_cree = $models->execute_kw(
            $odoo_db,
            (int) $uid,
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