<?php
require_once __DIR__ . '/OdooPrimitive.php';

function listeImplantsDispo($content)
{
    if (!is_page('odooreservation')) {
        return $content;
    }

    $moncontenu = "<div class='OdooBridge'><div class='OdooContent'>";

    // message retour
    if (isset($_GET['odooBridge']) && $_GET['odooBridge'] === 'ok') {
        $moncontenu .= "<div class='odoo-msg ok'>Demande envoyée.</div>";
    } elseif (isset($_GET['odooBridge']) && $_GET['odooBridge'] === 'ko') {
        $erreur = get_transient('odoobridge_erreur');
        $moncontenu .= "<div class='odoo-msg ko'>Erreur : " . esc_html($erreur ? $erreur : "envoi impossible") . "</div>";
        delete_transient('odoobridge_erreur');
    }

    if (!is_user_logged_in()) {
        $moncontenu .= "<div class='odoo-msg ko'>Tu dois être connecté pour consulter cette page.</div></div></div>";
        return $moncontenu;
    }

    $implants = getAllImplants();
    if ($implants === false) {
        $moncontenu .= "<div class='odoo-msg ko'>
            Erreur de connexion à Odoo. Vérifie :
            <ul>
                <li>Réglages > Options OdooBridge : urlOdoo + dbOdoo</li>
                <li>Ton profil : champ Odoo API key</li>
                <li>Ton mail WordPress = mail utilisateur Odoo</li>
            </ul>
        </div></div></div>";
        return $moncontenu;
    }

    $moncontenu .= "<h2>Liste des implants disponibles</h2>";
    $moncontenu .= "<div class='liste'>";

    foreach ($implants as $implant) {
        $id_implant = isset($implant['id']) ? (int) $implant['id'] : 0;

        $nom = $implant['nom_implant'] ?? '';
        $type = $implant['type_implant'] ?? '';
        $rare = $implant['rarete'] ?? '';
        $prix = $implant['prix_euro'] ?? '';
        $ess = $implant['cout_essence'] ?? '';
        $empl = $implant['emplacement'] ?? '';

        $fabricant = '';
        if (isset($implant['manufacturer_id']) && is_array($implant['manufacturer_id']) && count($implant['manufacturer_id']) >= 2) {
            $fabricant = $implant['manufacturer_id'][1];
        }

        $image_html = "";
        if (!empty($implant['image_implant'])) {
            $image_html = "<img src='data:image/png;base64," . $implant['image_implant'] . "' alt='implant'>";
        } else {
            $image_html = "<img src='" . esc_url(plugin_dir_url(__FILE__) . "assets/images/placeholder.png") . "' alt='implant'>";
        }

        $id_date = "date_" . $id_implant;
        $id_duree = "duree_" . $id_implant;

        $moncontenu .= "
        <article class='card'>
            <div class='cardContent'>
                <div class='thumbWrap'>$image_html</div>

                <div class='details'>
                    <h3 class='titre'>" . esc_html($nom) . "</h3>

                    <div class='badges'>
                        <span class='badge'>Type : " . esc_html($type) . "</span>
                        <span class='badge'>Rareté : " . esc_html($rare) . "</span>
                    </div>

                    <div class='meta'>
                        <div class='ligne'><span class='k'>Prix</span><span class='v'>" . esc_html($prix) . " €</span></div>
                        <div class='ligne'><span class='k'>Essence</span><span class='v'>" . esc_html($ess) . "</span></div>
                        <div class='ligne'><span class='k'>Emplacement</span><span class='v'>" . esc_html($empl) . "</span></div>
                        <div class='ligne'><span class='k'>Fabricant</span><span class='v'>" . esc_html($fabricant) . "</span></div>
                    </div>

                    <form class='demande' method='post' action=''>
                        " . wp_nonce_field('demandeImplantation', 'odoobridge_nonce', true, false) . "
                        <input type='hidden' name='implant_id' value='" . esc_attr($id_implant) . "'>

                        <div class='champ'>
                            <label for='" . esc_attr($id_date) . "'>Date</label>
                            <input type='date' name='date_reservation' id='" . esc_attr($id_date) . "' required>
                        </div>

                        <div class='champ'>
                            <label for='" . esc_attr($id_duree) . "'>Durée (jours)</label>
                            <input type='number' name='duree_reservation' id='" . esc_attr($id_duree) . "' min='1' required>
                        </div>

                        <button class='bouton' type='submit' name='odoobridge_demande' value='1'>Envoyer</button>
                    </form>

                    <div class='aide'>La demande est enregistrée côté Odoo si tes identifiants sont ok.</div>
                </div>
            </div>
        </article>";
    }

    $moncontenu .= "</div></div></div>";
    return $moncontenu;
}

add_filter('the_content', 'listeImplantsDispo');
