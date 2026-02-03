<?php
require_once __DIR__ . '/OdooPrimitive.php';

function listeImplantsDispo($content)
{
    if (!is_page('odooreservation')) {
        return $content;
    }

    $moncontenu = '
    <div class="OdooBridge">
        <div class="OdooContent">
    ';

    $implants = getAllImplants();

    if ($implants !== false) {
        $moncontenu .= "
            <h2>Liste des implants disponibles</h2>
            <div class='liste'>
        ";

        foreach ($implants as $implant) {

            $nom = isset($implant['nom_implant']) ? $implant['nom_implant'] : '';
            $type = isset($implant['type_implant']) ? $implant['type_implant'] : '';
            $rare = isset($implant['rarete']) ? $implant['rarete'] : '';
            $prix = isset($implant['prix_euro']) ? $implant['prix_euro'] : '';
            $ess = isset($implant['cout_essence']) ? $implant['cout_essence'] : '';
            $empl = isset($implant['emplacement']) ? $implant['emplacement'] : '';
            $actif = isset($implant['actif']) ? $implant['actif'] : '';

            $fabricant = '';
            if (isset($implant['manufacturer_id']) && is_array($implant['manufacturer_id']) && count($implant['manufacturer_id']) >= 2) {
                $fabricant = $implant['manufacturer_id'][1];
            }

            $image_html = '';
            if (!empty($implant['image_implant'])) {
                $image_html = "<img class='implant-img' src='data:image/png;base64," . $implant['image_implant'] . "' alt='implant' />";
            }

            $moncontenu .= "
                <div class='carte'>
                    $image_html
                    <p class='nom'><b>" . esc_html($nom) . "</b></p>
                    <p class='type'>Type : " . esc_html($type) . "</p>
                    <p class='rarete'>Rareté : " . esc_html($rare) . "</p>
                    <p class='prix'>Prix : " . esc_html($prix) . " €</p>
                    <p class='essence'>Coût essence : " . esc_html($ess) . "</p>
                    <p class='emplacement'>Emplacement : " . esc_html($empl) . "</p>
                    <p class='fabricant'>Fabricant : " . esc_html($fabricant) . "</p>
                    <p class='actif'>Actif : " . esc_html($actif) . "</p>
                </div>
            ";
        }

        $moncontenu .= "</div>";
    } else {
        $moncontenu .= "<p>Erreur de connexion à Odoo. Vérifie :</p>
        <ul>
            <li>Réglages > Options OdooBridge : urlOdoo + dbOdoo</li>
            <li>Ton profil utilisateur : champ Odoo API key</li>
            <li>Que ton mail Wordpress = mail de l’utilisateur Odoo (apiwordpress@admin.fr)</li>
        </ul>";
    }

    $moncontenu .= "
        </div>
    </div>
    ";

    return $moncontenu;
}

add_filter('the_content', 'listeImplantsDispo');
