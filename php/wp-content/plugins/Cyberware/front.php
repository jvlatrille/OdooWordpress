<?php
if (!defined('ABSPATH'))
    exit;

require_once __DIR__ . '/OdooPrimitive.php';

function cybercrud_render_clients_page($content)
{
    if (!is_page('cybercrud-clients'))
        return $content;

    $etat = sanitize_text_field($_GET['cybercrud'] ?? '');
    $page = max(1, (int) ($_GET['p'] ?? 1));
    $q = sanitize_text_field($_GET['q'] ?? '');

    $html = "<div class='cybercrud-ui'>";

    // messages
    if ($etat === 'ok') {
        $html .= "<div class='toast ok'>Action OK.</div>";
    } elseif ($etat === 'ko') {
        $erreur = get_transient('cybercrud_erreur');
        $html .= "<div class='toast ko'>Erreur : " . esc_html($erreur ?: 'action impossible') . "</div>";
        delete_transient('cybercrud_erreur');
    }

    if (!is_user_logged_in()) {
        $html .= "<div class='panel danger'>Connecte-toi pour accéder à cette page.</div></div>";
        return $html;
    }

    $total = cybercrud_odoo_clients_count($q);
    $clients = cybercrud_odoo_clients_page($page, $q);

    if ($clients === false) {
        $html .= "<div class='panel danger'>
            Connexion Odoo impossible. Vérifie :
            <ul>
              <li>Options CyberwareCRUD : urlOdoo + dbOdoo</li>
              <li>Profil WP : CyberwareCRUD Odoo API key</li>
              <li>Email WP = login Odoo</li>
            </ul>
        </div></div>";
        return $html;
    }

    $nb_pages = max(1, (int) ceil($total / 10));

    // données select + implants
    $users = cybercrud_odoo_users_for_select() ?: [];
    $implants = cybercrud_odoo_implants_all() ?: [];

    // header
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

    // form create
    $html .= "<section class='panel'>
      <h2>Ajouter un client</h2>

      <form class='form-grid' method='post'>
        " . wp_nonce_field('cybercrud_clients', 'cybercrud_nonce', true, false) . "
        <input type='hidden' name='cybercrud_action' value='create'>

        <div class='field'>
          <label>Nom</label>
          <input required name='cybercrud_nom_client' placeholder='Valerie'>
        </div>

        <div class='field'>
          <label>Pseudo</label>
          <input name='cybercrud_pseudo' placeholder='alias'>
        </div>

        <div class='field'>
          <label>User (lié)</label>
          <select name='cybercrud_user_id'>
            <option value=''>-- aucun --</option>";
    foreach ($users as $u) {
        $nom = $u['name'] ?? ($u['login'] ?? '');
        $html .= "<option value='" . (int) $u['id'] . "'>" . esc_html($nom) . "</option>";
    }
    $html .= "</select>
        </div>

        <div class='field full'>
          <label>Implants (N-N)</label>
          <div class='chips'>";
    foreach ($implants as $imp) {
        $iid = (int) $imp['id'];
        $label = $imp['nom_implant'] ?? ('Implant #' . $iid);
        $html .= "
                  <label class='chip'>
                    <input type='checkbox' name='cybercrud_implants[]' value='{$iid}'>
                    <span>" . esc_html($label) . "</span>
                  </label>
                ";
    }
    $html .= "</div>
        </div>

        <div class='actions full'>
          <button class='btn primary' type='submit'>Créer</button>
        </div>
      </form>
    </section>";

    // cards grid
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

        // photo
        $img_src = '';
        if (!empty($c['image_client'])) {
            $img_src = cybercrud_image_src($c['image_client']);
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

          <div class='line'><span>Compte Odoo</span><b>" . esc_html($user_label) . "</b></div>

          <div class='implants'>
            <div class='label'>Implants</div>
            <div class='chips small'>";
        if (count($selected_implants) === 0) {
            $html .= "<span class='empty'>aucun</span>";
        } else {
            // on transforme la liste d’ids en noms via $implants (petit mapping)
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
              <form class='form-grid' method='post'>
                " . wp_nonce_field('cybercrud_clients', 'cybercrud_nonce', true, false) . "
                <input type='hidden' name='cybercrud_action' value='update'>
                <input type='hidden' name='cybercrud_client_id' value='" . $id . "'>

                <div class='field'>
                  <label>Nom</label>
                  <input required name='cybercrud_nom_client' value='" . esc_attr($nom) . "'>
                </div>

                <div class='field'>
                  <label>Pseudo</label>
                  <input name='cybercrud_pseudo' value='" . esc_attr($pseudo) . "'>
                </div>

                <div class='field'>
                  <label>User</label>
                  <select name='cybercrud_user_id'>
                    <option value=''>-- aucun --</option>";
        foreach ($users as $u) {
            $uid = (int) $u['id'];
            $nomu = $u['name'] ?? ($u['login'] ?? '');
            $sel = ($uid === $user_id) ? "selected" : "";
            $html .= "<option value='{$uid}' {$sel}>" . esc_html($nomu) . "</option>";
        }
        $html .= "</select>
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
                            <input type='checkbox' name='cybercrud_implants[]' value='{$iid}' {$checked}>
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
                " . wp_nonce_field('cybercrud_clients', 'cybercrud_nonce', true, false) . "
                <input type='hidden' name='cybercrud_action' value='delete'>
                <input type='hidden' name='cybercrud_client_id' value='" . $id . "'>
                <button class='btn danger' type='submit'>Supprimer</button>
              </form>
            </div>
          </details>
        </article>
        ";
    }

    $html .= "</section>";

    // pagination
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

add_filter('the_content', 'cybercrud_render_clients_page');
