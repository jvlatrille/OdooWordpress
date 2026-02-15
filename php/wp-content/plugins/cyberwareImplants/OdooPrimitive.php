<?php
if (!defined('ABSPATH'))
    exit;

if (!class_exists('ripcord')) {
    require_once __DIR__ . '/ripcord/ripcord.php';
}

function cyberwareimplant_odoo_connect()
{
    $url = get_option('cyberwareimplant_urlOdoo', '');
    $db = get_option('cyberwareimplant_dbOdoo', '');

    $wp_user = wp_get_current_user();
    $username = $wp_user->user_email ?? '';
    $apikey = $wp_user->ID ? get_user_meta($wp_user->ID, 'cyberwareimplant_odooapikey', true) : '';

    if ($url === '' || $db === '' || $username === '' || $apikey === '')
        return 0;

    $common = ripcord::client($url . "/xmlrpc/2/common");
    $common->version();
    return (int) $common->authenticate($db, $username, $apikey, []);
}

function cyberwareimplant_odoo_object()
{
    $url = get_option('cyberwareimplant_urlOdoo', '');
    return ripcord::client($url . "/xmlrpc/2/object");
}

function cyberwareimplant_odoo_ctx()
{
    $wp_user = wp_get_current_user();
    $apikey = $wp_user->ID ? get_user_meta($wp_user->ID, 'cyberwareimplant_odooapikey', true) : '';
    return [$apikey, get_option('cyberwareimplant_dbOdoo', ''), (int) cyberwareimplant_odoo_connect()];
}

function cyberwareimplant_image_src($binaire)
{
    if (empty($binaire))
        return '';
    return "data:image/png;base64," . $binaire;
}

function cyberwareimplant_odoo_implants_count($q, $type, $rarete)
{
    [$apikey, $db, $uid] = cyberwareimplant_odoo_ctx();
    if (!$uid)
        return 0;

    $obj = cyberwareimplant_odoo_object();
    $domain = [];

    $q = trim((string) $q);
    if ($q !== '')
        $domain[] = ['nom_implant', 'ilike', $q];
    if ($type !== '')
        $domain[] = ['type_implant', '=', $type];
    if ($rarete !== '')
        $domain[] = ['rarete', '=', $rarete];

    return (int) $obj->execute_kw($db, $uid, $apikey, 'cyberware.implant', 'search_count', [$domain]);
}

function cyberwareimplant_odoo_implants_page($page, $q, $type, $rarete)
{
    [$apikey, $db, $uid] = cyberwareimplant_odoo_ctx();
    if (!$uid)
        return false;

    $obj = cyberwareimplant_odoo_object();

    $limit = 12;
    $page = max(1, (int) $page);
    $offset = ($page - 1) * $limit;

    $domain = [];
    $q = trim((string) $q);
    if ($q !== '')
        $domain[] = ['nom_implant', 'ilike', $q];
    if ($type !== '')
        $domain[] = ['type_implant', '=', $type];
    if ($rarete !== '')
        $domain[] = ['rarete', '=', $rarete];

    $kwargs = [
        'domain' => $domain,
        'fields' => [
            'id',
            'nom_implant',
            'description',
            'type_implant',
            'rarete',
            'prix_euro',
            'cout_essence',
            'emplacement',
            'image_implant',
            'manufacturer_id',
            'actif'
        ],
        'order' => 'id desc',
        'limit' => $limit,
        'offset' => $offset,
    ];

    return $obj->execute_kw($db, $uid, $apikey, 'cyberware.implant', 'search_read', [], $kwargs);
}

function cyberwareimplant_odoo_create_implant($vals)
{
    try {
        [$apikey, $db, $uid] = cyberwareimplant_odoo_ctx();
        if (!$uid)
            throw new Exception("Connexion Odoo impossible.");

        if (trim($vals['nom_implant'] ?? '') === '')
            throw new Exception("Nom obligatoire.");
        if (empty($vals['type_implant']))
            throw new Exception("Type obligatoire.");
        if (empty($vals['rarete']))
            throw new Exception("Rareté obligatoire.");

        $obj = cyberwareimplant_odoo_object();
        return (int) $obj->execute_kw($db, $uid, $apikey, 'cyberware.implant', 'create', [$vals]);

    } catch (Exception $e) {
        set_transient('cyberwareimplant_erreur', $e->getMessage(), 30);
        return 0;
    }
}

function cyberwareimplant_odoo_update_implant($implant_id, $vals)
{
    try {
        [$apikey, $db, $uid] = cyberwareimplant_odoo_ctx();
        if (!$uid)
            throw new Exception("Connexion Odoo impossible.");
        if ($implant_id <= 0)
            throw new Exception("Implant invalide.");

        $obj = cyberwareimplant_odoo_object();
        return (bool) $obj->execute_kw($db, $uid, $apikey, 'cyberware.implant', 'write', [[(int) $implant_id], $vals]);

    } catch (Exception $e) {
        set_transient('cyberwareimplant_erreur', $e->getMessage(), 30);
        return false;
    }
}

function cyberwareimplant_odoo_delete_implant($implant_id)
{
    try {
        [$apikey, $db, $uid] = cyberwareimplant_odoo_ctx();
        if (!$uid)
            throw new Exception("Connexion Odoo impossible.");
        if ($implant_id <= 0)
            throw new Exception("Implant invalide.");

        $obj = cyberwareimplant_odoo_object();
        return (bool) $obj->execute_kw($db, $uid, $apikey, 'cyberware.implant', 'unlink', [[(int) $implant_id]]);
    } catch (Exception $e) {
        set_transient('cyberwareimplant_erreur', $e->getMessage(), 30);
        return false;
    }
}

function cyberwareimplant_odoo_implants_all($q, $type, $rarete)
{
    [$apikey, $db, $uid] = cyberwareimplant_odoo_ctx();
    if (!$uid)
        return false;

    $obj = cyberwareimplant_odoo_object();

    $domain = [];

    $q = trim((string) $q);
    if ($q !== '') {
        $domain[] = ['nom_implant', 'ilike', $q];
    }
    if ($type !== '') {
        $domain[] = ['type_implant', '=', $type];
    }
    if ($rarete !== '') {
        $domain[] = ['rarete', '=', $rarete];
    }

    $kwargs = [
        'domain' => $domain,
        'fields' => [
            'id',
            'nom_implant',
            'type_implant',
            'rarete',
            'prix_euro',
            'cout_essence',
            'emplacement',
            'description',
            'actif',
            'image_implant',
            'manufacturer_id'
        ],
        'order' => 'id desc',
        'limit' => 0,
    ];

    return $obj->execute_kw($db, $uid, $apikey, 'cyberware.implant', 'search_read', [], $kwargs);
}
