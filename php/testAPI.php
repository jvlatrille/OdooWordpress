<?php
header('Content-Type: text/html; charset=utf-8');
require_once('ripcord/ripcord.php');

/* =========================
   CONFIG (TES INFOS)
   ========================= */
$url = "http://web:8069";
$db = "odoo18";
$username = "apiwordpress@admin.fr";
$cleapi = "3a9f262bbd636a22ea69139628834ef5d67a8ac9";

/* Modèle du module Cyberware (adaptation du TP rentcars.vehicle) */
$modele = "cyberware.implant";

/* =========================
   PETITES FONCTIONS AFFICHAGE
   ========================= */
function titre($txt)
{
    echo "<h2 style='margin-top:24px;'>$txt</h2>";
}
function bloc($label, $data)
{
    echo "<h4 style='margin:10px 0 6px;'>$label</h4>";
    echo "<pre style='background:#111;color:#eee;padding:12px;border-radius:8px;overflow:auto;'>";
    print_r($data);
    echo "</pre>";
}

/* =========================
   VERSION
   ========================= */
titre("version() (API common)");
$common = ripcord::client($url . "/xmlrpc/2/common");
$version = $common->version();
bloc("Retour version()", $version);

/* =========================
   AUTHENTICATE
   ========================= */
titre("authenticate() (API common)");
$uid = $common->authenticate($db, $username, $cleapi, []);

if (empty($uid)) {
    echo "<p style='color:#ff6b6b;'>Impossible de me connecter</p>";
    exit;
}

echo "<p style='color:#7CFC00;'>Je suis connecté avec l'id : $uid</p>";

$object = ripcord::client($url . "/xmlrpc/2/object");

$domaine = [
    '|',
    ['rarete', '=', 'epique'],
    ['rarete', '=', 'rare'],
];

/* =========================
   SEARCH
   ========================= */
titre("execute_kw -> search");

$arguments_positionnels = [$domaine];
$arguments_nommes = [
    'offset' => 0,
    'limit' => null,
    'order' => 'prix_euro desc',
];

$ids_search = $object->execute_kw(
    $db,
    $uid,
    $cleapi,
    $modele,
    'search',
    $arguments_positionnels,
    $arguments_nommes
);
bloc("search() -> liste d'IDs", $ids_search);

$arguments_positionnels = [];
$arguments_nommes = [
    'domain' => $domaine,
    'offset' => 0,
    'limit' => null,
    'order' => 'prix_euro desc',
];

$ids_search_kw = $object->execute_kw(
    $db,
    $uid,
    $cleapi,
    $modele,
    'search',
    $arguments_positionnels,
    $arguments_nommes
);
bloc("search() (domain dans keyword_argument)", $ids_search_kw);

/* =========================
   SEARCH_COUNT
   ========================= */
titre("execute_kw -> search_count");

$arguments_positionnels = [];
$arguments_nommes = [
    'domain' => $domaine,
    'limit' => null
];

$nb = $object->execute_kw(
    $db,
    $uid,
    $cleapi,
    $modele,
    'search_count',
    $arguments_positionnels,
    $arguments_nommes
);
bloc("search_count() -> nombre", $nb);

/* =========================
   SEARCH_READ
   ========================= */
titre("execute_kw -> search_read");

$champs = [
    'nom_implant',
    'type_implant',
    'rarete',
    'prix_euro',
    'cout_essence',
    'emplacement',
    'manufacturer_id'
];

$arguments_positionnels = [];
$arguments_nommes = [
    'domain' => $domaine,
    'fields' => $champs,
    'order' => 'prix_euro desc',
    'limit' => 5,
    'load' => 'None'
];

$implants_load_none = $object->execute_kw(
    $db,
    $uid,
    $cleapi,
    $modele,
    'search_read',
    $arguments_positionnels,
    $arguments_nommes
);
bloc("search_read() avec load=None", $implants_load_none);

$arguments_nommes = [
    'domain' => $domaine,
    'fields' => $champs,
    'order' => 'prix_euro desc',
    'limit' => 5
];

$implants_sans_load = $object->execute_kw(
    $db,
    $uid,
    $cleapi,
    $modele,
    'search_read',
    $arguments_positionnels,
    $arguments_nommes
);
bloc("search_read() sans load", $implants_sans_load);

/* =========================
   CREATE
   ========================= */
titre("execute_kw -> create (2 implants)");

$tag = date('Ymd_His');

$implant1 = [
    'nom_implant' => "Implant API Alpha $tag",
    'type_implant' => 'neural',
    'rarete' => 'epique',
    'prix_euro' => 9999,
    'cout_essence' => 2,
    'emplacement' => 'systeme',
];

$implant2 = [
    'nom_implant' => "Implant API Beta $tag",
    'type_implant' => 'optique',
    'rarete' => 'commun',
    'prix_euro' => 2500,
    'cout_essence' => 1,
    'emplacement' => 'tete',
];

$vals_list = [$implant1, $implant2];
$arguments_positionnels = [$vals_list];

$ids_crees = $object->execute_kw(
    $db,
    $uid,
    $cleapi,
    $modele,
    'create',
    $arguments_positionnels
);

bloc("create() -> IDs créés", $ids_crees);

if (is_int($ids_crees)) {
    $ids_crees = [$ids_crees];
}
if (!is_array($ids_crees) || count($ids_crees) === 0) {
    echo "<p style='color:#ff6b6b;'>Création KO, j'arrête les tests write/unlink.</p>";
    exit;
}

/* =========================
   WRITE
   ========================= */
titre("execute_kw -> write (update)");

$ids_un = [$ids_crees[0]];
$data_un = [
    'prix_euro' => 11111,
    'rarete' => 'rare',
    'cout_essence' => 3
];

$arguments_positionnels = [$ids_un, $data_un];

$res_write_1 = $object->execute_kw(
    $db,
    $uid,
    $cleapi,
    $modele,
    'write',
    $arguments_positionnels
);
bloc("write() sur 1 implant", $res_write_1);

$data_multi = ['actif' => false];
$arguments_positionnels = [$ids_crees, $data_multi];

$res_write_multi = $object->execute_kw(
    $db,
    $uid,
    $cleapi,
    $modele,
    'write',
    $arguments_positionnels
);
bloc("write() sur plusieurs implants", $res_write_multi);

$arguments_positionnels = [[['id', 'in', $ids_crees]]];
$arguments_nommes = ['fields' => $champs, 'limit' => 10];

$verif = $object->execute_kw(
    $db,
    $uid,
    $cleapi,
    $modele,
    'search_read',
    $arguments_positionnels,
    $arguments_nommes
);
bloc("Vérif après write (search_read sur IDs créés)", $verif);

/* =========================
   UNLINK
   ========================= */
titre("execute_kw -> unlink (delete)");

$arguments_positionnels = [$ids_crees];

$res_unlink = $object->execute_kw(
    $db,
    $uid,
    $cleapi,
    $modele,
    'unlink',
    $arguments_positionnels
);

bloc("unlink() -> suppression des IDs créés", $res_unlink);