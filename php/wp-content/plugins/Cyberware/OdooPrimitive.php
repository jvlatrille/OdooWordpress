<?php
if (!defined('ABSPATH'))
    exit;

if (!class_exists('ripcord')) {
    require_once __DIR__ . '/ripcord/ripcord.php';
}

function cyberwareclient_odoo_connect()
{
    $url = get_option('cyberwareclient_urlOdoo', '');
    $db = get_option('cyberwareclient_dbOdoo', '');

    $wp_user = wp_get_current_user();
    $username = $wp_user->user_email ?? '';
    $apikey = $wp_user->ID ? get_user_meta($wp_user->ID, 'cyberwareclient_odooapikey', true) : '';

    if ($url === '' || $db === '' || $username === '' || $apikey === '')
        return 0;

    $common = ripcord::client($url . "/xmlrpc/2/common");
    $common->version();
    return (int) $common->authenticate($db, $username, $apikey, []);
}

function cyberwareclient_odoo_object()
{
    $url = get_option('cyberwareclient_urlOdoo', '');
    return ripcord::client($url . "/xmlrpc/2/object");
}

function cyberwareclient_odoo_ctx()
{
    $wp_user = wp_get_current_user();
    $apikey = $wp_user->ID ? get_user_meta($wp_user->ID, 'cyberwareclient_odooapikey', true) : '';
    return [$apikey, get_option('cyberwareclient_dbOdoo', ''), (int) cyberwareclient_odoo_connect()];
}

function cyberwareclient_odoo_clients_count($recherche)
{
    [$apikey, $db, $uid] = cyberwareclient_odoo_ctx();
    if (!$uid)
        return 0;

    $obj = cyberwareclient_odoo_object();

    $domain = [];
    $recherche = trim((string) $recherche);
    if ($recherche !== '') {
        $domain = ['|', ['nom_client', 'ilike', $recherche], ['pseudo', 'ilike', $recherche]];
    }

    return (int) $obj->execute_kw($db, $uid, $apikey, 'cyberware.client', 'search_count', [$domain]);
}

function cyberwareclient_odoo_clients_page($page, $recherche)
{
    [$apikey, $db, $uid] = cyberwareclient_odoo_ctx();
    if (!$uid)
        return false;

    $obj = cyberwareclient_odoo_object();

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


function cyberwareclient_odoo_users_for_select()
{
    [$apikey, $db, $uid] = cyberwareclient_odoo_ctx();
    if (!$uid)
        return false;

    $obj = cyberwareclient_odoo_object();
    $kwargs = ['fields' => ['id', 'login', 'name'], 'limit' => 200, 'order' => 'id asc'];
    return $obj->execute_kw($db, $uid, $apikey, 'res.users', 'search_read', [[]], $kwargs);
}

function cyberwareclient_odoo_implants_all()
{
    [$apikey, $db, $uid] = cyberwareclient_odoo_ctx();
    if (!$uid)
        return false;

    $obj = cyberwareclient_odoo_object();
    $kwargs = ['fields' => ['id', 'nom_implant'], 'limit' => 500, 'order' => 'nom_implant asc'];
    return $obj->execute_kw($db, $uid, $apikey, 'cyberware.implant', 'search_read', [[]], $kwargs);
}

function cyberwareclient_odoo_create_client($nom_client, $pseudo, $implant_ids, $image_b64 = '')
{
    try {
        [$apikey, $db, $uid] = cyberwareclient_odoo_ctx();
        if (!$uid)
            throw new Exception("Connexion Odoo impossible.");

        if (trim($nom_client) === '')
            throw new Exception("nom_client obligatoire.");

        $obj = cyberwareclient_odoo_object();

        $vals = [
            'nom_client' => $nom_client,
            'pseudo' => $pseudo,
            'user_id' => (int) $uid,
            'implant_ids' => [[6, 0, array_values(array_filter($implant_ids))]],
            'actif' => true,
        ];

        if (!empty($image_b64)) {
            $vals['image_client'] = $image_b64;
        }

        return (int) $obj->execute_kw($db, $uid, $apikey, 'cyberware.client', 'create', [$vals]);
    } catch (Exception $e) {
        set_transient('cyberwareclient_erreur', $e->getMessage(), 30);
        return 0;
    }
}

function cyberwareclient_odoo_update_client($client_id, $nom_client, $pseudo, $implant_ids, $image_b64 = '')
{
    try {
        [$apikey, $db, $uid] = cyberwareclient_odoo_ctx();
        if (!$uid)
            throw new Exception("Connexion Odoo impossible.");
        if ($client_id <= 0)
            throw new Exception("Client invalide.");
        if (trim($nom_client) === '')
            throw new Exception("nom_client obligatoire.");

        $obj = cyberwareclient_odoo_object();

        $vals = [
            'nom_client' => $nom_client,
            'pseudo' => $pseudo,
            'implant_ids' => [[6, 0, array_values(array_filter($implant_ids))]],
        ];

        if (!empty($image_b64)) {
            $vals['image_client'] = $image_b64;
        }

        return (bool) $obj->execute_kw($db, $uid, $apikey, 'cyberware.client', 'write', [[(int) $client_id], $vals]);
    } catch (Exception $e) {
        set_transient('cyberwareclient_erreur', $e->getMessage(), 30);
        return false;
    }
}

function cyberwareclient_odoo_delete_client($client_id)
{
    try {
        [$apikey, $db, $uid] = cyberwareclient_odoo_ctx();
        if (!$uid)
            throw new Exception("Connexion Odoo impossible.");
        if ($client_id <= 0)
            throw new Exception("Client invalide.");

        $obj = cyberwareclient_odoo_object();
        return (bool) $obj->execute_kw($db, $uid, $apikey, 'cyberware.client', 'unlink', [[(int) $client_id]]);
    } catch (Exception $e) {
        set_transient('cyberwareclient_erreur', $e->getMessage(), 30);
        return false;
    }
}

function cyberwareclient_image_src($binaire)
{
    if (empty($binaire))
        return '';
    return "data:image/png;base64," . $binaire;
}