<?php
if (!defined('ABSPATH'))
    exit;

require_once __DIR__ . '/OdooPrimitive.php';

function cyberwareclient_render_clients_page($content)
{
    if (!is_page('cybercrud-clients'))
        return $content;

    $etat = sanitize_text_field($_GET['cybercrud'] ?? '');
    $page = max(1, (int) ($_GET['p'] ?? 1));
    $q = sanitize_text_field($_GET['q'] ?? '');

    $html = "<div class='cybercrud-ui'>";

    if ($etat === 'ok') {
        $html .= "<div class='toast ok' id='cyber-toast'>Action OK.</div><script>setTimeout(() => document.getElementById('cyber-toast')?.remove(), 3000);</script>";
    } elseif ($etat === 'ko') {
        $erreur = get_transient('cyberwareclient_erreur');
        $html .= "<div class='toast ko'>Erreur : " . esc_html($erreur ?: 'action impossible') . "</div>";
        delete_transient('cyberwareclient_erreur');
    }

    if (!is_user_logged_in()) {
        $html .= "<div class='panel danger'>Connecte-toi pour accéder à cette page.</div></div>";
        return $html;
    }

    $total = cyberwareclient_odoo_clients_count($q);
    $clients = cyberwareclient_odoo_clients_page($page, $q);

    if ($clients === false) {
        $html .= "<div class='panel danger'>
            Connexion Odoo impossible. Vérifie :
            <ul>
              <li>Options CyberwareClient : urlOdoo + dbOdoo</li>
              <li>Profil WP : CyberwareClient Odoo API key</li>
              <li>Email WP = login Odoo</li>
            </ul>
        </div></div>";
        return $html;
    }

    $nb_pages = max(1, (int) ceil($total / 10));

    $users = cyberwareclient_odoo_users_for_select() ?: [];
    $implants = cyberwareclient_odoo_implants_all() ?: [];
    $html .= "
      <div class='topbar'>
        <div class='titre'>
          <h1>Cyberware Clients</h1>
          <div class='sous'>Total : <b>" . (int) $total . "</b></div>
        </div>

        <form class='search' method='get'>
          <input type='hidden' name='pagename' value='cybercrud-clients'>
          <input type='text' name='q' value='" . esc_attr($q) . "' placeholder='recherche nom / pseudo'>
          <button type='submit'>Rechercher</button>
        </form>
      </div>
    ";

    $html .= "
    <section class='panel create-panel'>
    <details class='create-accordion'>
        <summary class='create-summary'>
        <div class='create-left'>
            <span class='create-title'>Ajouter un client</span>
            <span class='create-hint'>Clique pour ouvrir</span>
        </div>
        <span class='create-plus' aria-hidden='true'>+</span>
        </summary>

        <div class='create-body'>
        <form class='form-grid cybercrud-form' method='post' enctype='multipart/form-data'>
            " . wp_nonce_field('cyberwareclient_clients', 'cyberwareclient_nonce', true, false) . "
            <input type='hidden' name='cyberwareclient_action' value='create'>

            <div class='field'>
            <label>Nom</label>
            <input required name='cyberwareclient_nom_client' placeholder='Valerie' autocomplete='name'>
            </div>

            <div class='field'>
            <label>Pseudo</label>
            <input name='cyberwareclient_pseudo' placeholder='alias' autocomplete='nickname'>
            </div>

            <div class='field'>
            <label>Photo</label>
            <input type='file' name='cyberwareclient_image_client' accept='image/*'>
            <div class='mini-help'>PNG/JPG/WebP • max 2MB</div>
            </div>

            <div class='field full'>
            <label>Implants</label>
            <div class='chips chips-scroll'>";
    foreach ($implants as $imp) {
        $iid = (int) $imp['id'];
        $label = $imp['nom_implant'] ?? ('Implant #' . $iid);
        $html .= "
                <label class='chip'>
                <input type='checkbox' name='cyberwareclient_implants[]' value='{$iid}'>
                <span>" . esc_html($label) . "</span>
                </label>
            ";
    }
    $html .= "
            </div>
            </div>

            <div class='actions full actions-row'>
            <button class='btn primary' type='submit'>Créer</button>
            <button class='btn' type='reset'>Reset</button>
            </div>
        </form>
        </div>
    </details>
    </section>";

    $html .= "<section class='grid'>";

    foreach ($clients as $c) {
        $id = (int) ($c['id'] ?? 0);
        $nom = $c['nom_client'] ?? '';
        $pseudo = $c['pseudo'] ?? '';

        $user_id = 0;
        $user_label = '—';
        if (isset($c['user_id']) && is_array($c['user_id']) && count($c['user_id']) >= 2) {
            $user_id = (int) $c['user_id'][0];
            $user_label = $c['user_id'][1];
        }

        $age = isset($c['age']) ? (int) $c['age'] : 0;
        $ess_max = isset($c['niveau_essence_max']) ? (int) $c['niveau_essence_max'] : 0;
        $ess_util = isset($c['essence_utilisee']) ? (int) $c['essence_utilisee'] : 0;
        $ess_rest = isset($c['essence_restante']) ? (int) $c['essence_restante'] : ($ess_max - $ess_util);

        $selected_implants = isset($c['implant_ids']) && is_array($c['implant_ids']) ? array_map('intval', $c['implant_ids']) : [];

        $img_src = '';
        if (!empty($c['image_client'])) {
            $img_src = cyberwareclient_image_src($c['image_client']);
        }

        $initiale = mb_strtoupper(mb_substr($nom ?: 'C', 0, 1));

        $html .= "
        <article class='card'>
          <div class='card-head'>
            <div class='avatar'>";
        if ($img_src) {
            $html .= "<img src='" . esc_attr($img_src) . "' alt='client'>";
        } else {
            $html .= "<div class='fallback'>" . esc_html($initiale) . "</div>";
        }
        $html .= "</div>

            <div class='who'>
              <div class='name'>" . esc_html($nom) . "</div>
              <div class='meta'>#" . $id . " • " . esc_html($pseudo ?: '—') . "</div>
            </div>
          </div>

          <div class='stats'>
            <div class='stat'><span>Âge</span><b>" . ($age ?: '—') . "</b></div>
            <div class='stat'><span>Essence</span><b>" . (int) $ess_util . " / " . (int) $ess_max . "</b></div>
            <div class='stat'><span>Restante</span><b>" . (int) $ess_rest . "</b></div>
          </div>
          <div class='implants'>
            <div class='label'>Implants</div>
            <div class='chips small'>";
        if (count($selected_implants) === 0) {
            $html .= "<span class='empty'>aucun</span>";
        } else {
            $map_implants = [];
            foreach ($implants as $imp) {
                $map_implants[(int) $imp['id']] = $imp['nom_implant'] ?? ('Implant #' . (int) $imp['id']);
            }
            foreach ($selected_implants as $iid) {
                $label = $map_implants[$iid] ?? ('Implant #' . $iid);
                $html .= "<span class='pill'>" . esc_html($label) . "</span>";
            }
        }
        $html .= "</div>
          </div>

          <details class='drawer'>
            <summary class='btn ghost'>Modifier / Supprimer</summary>

            <div class='drawer-body'>
              <form class='form-grid' method='post' enctype='multipart/form-data'>
                " . wp_nonce_field('cyberwareclient_clients', 'cyberwareclient_nonce', true, false) . "
                <input type='hidden' name='cyberwareclient_action' value='update'>
                <input type='hidden' name='cyberwareclient_client_id' value='" . $id . "'>

                <div class='field'>
                  <label>Nom</label>
                  <input required name='cyberwareclient_nom_client' value='" . esc_attr($nom) . "'>
                </div>

                <div class='field'>
                  <label>Pseudo</label>
                  <input name='cyberwareclient_pseudo' value='" . esc_attr($pseudo) . "'>
                </div>
                <div class='field'>
                    <label>Photo (remplacer)</label>
                    <input type='file' name='cyberwareclient_image_client' accept='image/*'>
                </div>
                <div class='field full'>
                  <label>Implants</label>
                  <div class='chips'>";
        foreach ($implants as $imp) {
            $iid = (int) $imp['id'];
            $label = $imp['nom_implant'] ?? ('Implant #' . $iid);
            $checked = in_array($iid, $selected_implants, true) ? "checked" : "";
            $html .= "
                          <label class='chip'>
                            <input type='checkbox' name='cyberwareclient_implants[]' value='{$iid}' {$checked}>
                            <span>" . esc_html($label) . "</span>
                          </label>
                        ";
        }
        $html .= "</div>
                </div>

                <div class='actions full'>
                  <button class='btn primary' type='submit'>Enregistrer</button>
                </div>
              </form>

              <form method='post' class='delete' onsubmit=\"return confirm('Supprimer ce client ?');\">
                " . wp_nonce_field('cyberwareclient_clients', 'cyberwareclient_nonce', true, false) . "
                <input type='hidden' name='cyberwareclient_action' value='delete'>
                <input type='hidden' name='cyberwareclient_client_id' value='" . $id . "'>
                <button class='btn danger' type='submit'>Supprimer</button>
              </form>
            </div>
          </details>
        </article>
        ";
    }

    $html .= "</section>";

    if ($nb_pages > 1) {
        $html .= "<div class='pagination'>";
        for ($i = 1; $i <= $nb_pages; $i++) {
            $url = add_query_arg(['p' => $i, 'q' => $q], get_permalink());
            $cls = ($i === $page) ? "actif" : "";
            $html .= "<a class='{$cls}' href='" . esc_url($url) . "'>{$i}</a>";
        }
        $html .= "</div>";
    }

    $html .= "</div>";
    return $html;
}

add_filter('the_content', 'cyberwareclient_render_clients_page');
