<?php
if (!defined('ABSPATH'))
    exit;

if (!class_exists('ripcord')) {
    require_once __DIR__ . '/ripcord/ripcord.php';
}

function cybercrud_odoo_connect()
{
    $url = get_option('cybercrud_urlOdoo', '');
    $db = get_option('cybercrud_dbOdoo', '');

    $wp_user = wp_get_current_user();
    $username = $wp_user->user_email ?? '';
    $apikey = $wp_user->ID ? get_user_meta($wp_user->ID, 'cybercrud_odooapikey', true) : '';

    if ($url === '' || $db === '' || $username === '' || $apikey === '')
        return 0;

    $common = ripcord::client($url . "/xmlrpc/2/common");
    $common->version();
    return (int) $common->authenticate($db, $username, $apikey, []);
}

function cybercrud_odoo_object()
{
    $url = get_option('cybercrud_urlOdoo', '');
    return ripcord::client($url . "/xmlrpc/2/object");
}

function cybercrud_odoo_ctx()
{
    $wp_user = wp_get_current_user();
    $apikey = $wp_user->ID ? get_user_meta($wp_user->ID, 'cybercrud_odooapikey', true) : '';
    return [$apikey, get_option('cybercrud_dbOdoo', ''), (int) cybercrud_odoo_connect()];
}

/* ====== LISTE + PAGINATION + SEARCH ====== */

function cybercrud_odoo_clients_count($recherche)
{
    [$apikey, $db, $uid] = cybercrud_odoo_ctx();
    if (!$uid)
        return 0;

    $obj = cybercrud_odoo_object();

    $domain = [];
    $recherche = trim((string) $recherche);
    if ($recherche !== '') {
        $domain = ['|', ['nom_client', 'ilike', $recherche], ['pseudo', 'ilike', $recherche]];
    }

    return (int) $obj->execute_kw($db, $uid, $apikey, 'cyberware.client', 'search_count', [$domain]);
}

function cybercrud_odoo_clients_page($page, $recherche)
{
    [$apikey, $db, $uid] = cybercrud_odoo_ctx();
    if (!$uid)
        return false;

    $obj = cybercrud_odoo_object();

    $page = max(1, (int) $page);
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $domain = [];
    $recherche = trim((string) $recherche);
    if ($recherche !== '') {
        $domain = ['|', ['nom_client', 'ilike', $recherche], ['pseudo', 'ilike', $recherche]];
    }

    $kwargs = [
        'domain' => $domain,
        'fields' => [
            'id',
            'nom_client',
            'pseudo',
            'user_id',
            'implant_ids',
            'image_client',
            'date_naissance',
            'age',
            'niveau_essence_max',
            'essence_utilisee',
            'essence_restante',
            'actif'
        ],

        'order' => 'id desc',
        'limit' => $limit,
        'offset' => $offset,
    ];

    return $obj->execute_kw($db, $uid, $apikey, 'cyberware.client', 'search_read', [], $kwargs);
}

/* ====== Données pour <select> et N-N ====== */

function cybercrud_odoo_users_for_select()
{
    [$apikey, $db, $uid] = cybercrud_odoo_ctx();
    if (!$uid)
        return false;

    $obj = cybercrud_odoo_object();
    // on prend juste quelques users pour l’exo, sinon ça peut être énorme
    $kwargs = ['fields' => ['id', 'login', 'name'], 'limit' => 200, 'order' => 'id asc'];
    return $obj->execute_kw($db, $uid, $apikey, 'res.users', 'search_read', [[]], $kwargs);
}

function cybercrud_odoo_implants_all()
{
    [$apikey, $db, $uid] = cybercrud_odoo_ctx();
    if (!$uid)
        return false;

    $obj = cybercrud_odoo_object();
    $kwargs = ['fields' => ['id', 'nom_implant'], 'limit' => 500, 'order' => 'nom_implant asc'];
    return $obj->execute_kw($db, $uid, $apikey, 'cyberware.implant', 'search_read', [[]], $kwargs);
}

/* ====== CRUD ====== */

function cybercrud_odoo_create_client($nom_client, $pseudo, $user_id, $implant_ids)
{
    try {
        [$apikey, $db, $uid] = cybercrud_odoo_ctx();
        if (!$uid)
            throw new Exception("Connexion Odoo impossible.");

        if (trim($nom_client) === '')
            throw new Exception("nom_client obligatoire.");

        $obj = cybercrud_odoo_object();

        $vals = [
            'nom_client' => $nom_client,
            'pseudo' => $pseudo,
            'user_id' => $user_id ?: false,
            // M2M: (6, 0, ids) = set complet
            'implant_ids' => [[6, 0, array_values(array_filter($implant_ids))]],
            'actif' => true,
        ];

        return (int) $obj->execute_kw($db, $uid, $apikey, 'cyberware.client', 'create', [$vals]);
    } catch (Exception $e) {
        set_transient('cybercrud_erreur', $e->getMessage(), 30);
        return 0;
    }
}

function cybercrud_odoo_update_client($client_id, $nom_client, $pseudo, $user_id, $implant_ids)
{
    try {
        [$apikey, $db, $uid] = cybercrud_odoo_ctx();
        if (!$uid)
            throw new Exception("Connexion Odoo impossible.");
        if ($client_id <= 0)
            throw new Exception("Client invalide.");
        if (trim($nom_client) === '')
            throw new Exception("nom_client obligatoire.");

        $obj = cybercrud_odoo_object();

        $vals = [
            'nom_client' => $nom_client,
            'pseudo' => $pseudo,
            'user_id' => $user_id ?: false,
            'implant_ids' => [[6, 0, array_values(array_filter($implant_ids))]],
        ];

        return (bool) $obj->execute_kw($db, $uid, $apikey, 'cyberware.client', 'write', [[(int) $client_id], $vals]);
    } catch (Exception $e) {
        set_transient('cybercrud_erreur', $e->getMessage(), 30);
        return false;
    }
}

function cybercrud_odoo_delete_client($client_id)
{
    try {
        [$apikey, $db, $uid] = cybercrud_odoo_ctx();
        if (!$uid)
            throw new Exception("Connexion Odoo impossible.");
        if ($client_id <= 0)
            throw new Exception("Client invalide.");

        $obj = cybercrud_odoo_object();
        return (bool) $obj->execute_kw($db, $uid, $apikey, 'cyberware.client', 'unlink', [[(int) $client_id]]);
    } catch (Exception $e) {
        set_transient('cybercrud_erreur', $e->getMessage(), 30);
        return false;
    }
}

function cybercrud_image_src($binaire)
{
    if (empty($binaire))
        return '';
    // Odoo renvoie déjà du base64 dans les search_read
    return "data:image/png;base64," . $binaire;
}