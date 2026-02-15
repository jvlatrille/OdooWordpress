<?php
if (!defined('ABSPATH'))
    exit;

require_once __DIR__ . '/OdooPrimitive.php';

function cyberwareimplant_render_implants_page($content)
{
    if (!is_page('cyberwareimplants'))
        return $content;

    $etat = sanitize_text_field($_GET['cyberwareimplant'] ?? '');
    $page = max(1, (int) ($_GET['pg'] ?? 1));
    $q = sanitize_text_field($_GET['q'] ?? '');
    $type = sanitize_text_field($_GET['type'] ?? '');
    $rarete = sanitize_text_field($_GET['rarete'] ?? '');

    $html = "<div class='cybercrudimplatns-ui'>";

    if ($etat === 'ok') {
        $html .= "<div class='toast ok' id='cyber-toast'>Action OK.</div><script>setTimeout(()=>document.getElementById('cyber-toast')?.remove(),2500);</script>";
    } elseif ($etat === 'ko') {
        $erreur = get_transient('cyberwareimplant_erreur');
        $html .= "<div class='toast ko'>Erreur : " . esc_html($erreur ?: 'action impossible') . "</div>";
        delete_transient('cyberwareimplant_erreur');
    }

    if (!is_user_logged_in()) {
        $html .= "<div class='panel danger'>Connecte-toi pour accéder à cette page.</div></div>";
        return $html;
    }

    $total = cyberwareimplant_odoo_implants_count($q, $type, $rarete);
    $implants = cyberwareimplant_odoo_implants_all($q, $type, $rarete);


    if ($implants === false) {
        $html .= "<div class='panel danger'>Connexion Odoo impossible. Vérifie options + apikey + email.</div></div>";
        return $html;
    }

    $nb_pages = max(1, (int) ceil($total / 12));

    $types = [
        '' => 'Tous types',
        'optique' => 'Optique',
        'neural' => 'Neural',
        'armure' => 'Armure',
        'membre' => 'Membre cybernétique',
        'interne' => 'Organe interne',
    ];
    $rares = [
        '' => 'Toutes raretés',
        'commun' => 'Commun',
        'rare' => 'Rare',
        'epique' => 'Épique',
        'légendaire' => 'Légendaire',
    ];

    $html .= "
    <div class='topbar'>
      <div class='titre'>
        <h1>Cyberware Implants</h1>
        <div class='sous'>Total : <b>" . (int) $total . "</b></div>
      </div>

      <form class='search' method='get'>
        <form class='search' method='get' action='" . esc_url(get_permalink(get_page_by_path('cyberwareimplants')->ID)) . "'>
        <input type='text' name='q' value='" . esc_attr($q) . "' placeholder='recherche nom'>
        <select name='type' class='mini-select'>";
    foreach ($types as $k => $label) {
        $sel = ($k === $type) ? "selected" : "";
        $html .= "<option value='" . esc_attr($k) . "' {$sel}>" . esc_html($label) . "</option>";
    }
    $html .= "</select>
        <select name='rarete' class='mini-select'>";
    foreach ($rares as $k => $label) {
        $sel = ($k === $rarete) ? "selected" : "";
        $html .= "<option value='" . esc_attr($k) . "' {$sel}>" . esc_html($label) . "</option>";
    }
    $html .= "</select>
        <button type='submit'>Filtrer</button>
      </form>
    </div>
  ";

    /* CREATE ACCORDION */
    $html .= "
  <section class='panel create-panel'>
    <details class='create-accordion'>
      <summary class='create-summary'>
        <div class='create-left'>
          <span class='create-title'>Ajouter un implant</span>
          <span class='create-hint'>Clique pour ouvrir</span>
        </div>
        <span class='create-plus' aria-hidden='true'>+</span>
      </summary>

      <div class='create-body'>
        <form class='form-grid cybercrudimplatns-form' method='post' enctype='multipart/form-data'>
          " . wp_nonce_field('cyberwareimplant_implants', 'cyberwareimplant_nonce', true, false) . "
          <input type='hidden' name='cyberwareimplant_action' value='create'>

          <div class='field'>
            <label>Nom</label>
            <input required name='cyberwareimplant_nom_implant' placeholder='Kiroshi Optics Mk.3'>
          </div>

          <div class='field'>
            <label>Type</label>
            <select name='cyberwareimplant_type_implant' required>";
    foreach ($types as $k => $label) {
        if ($k === '')
            continue;
        $html .= "<option value='" . esc_attr($k) . "'>" . esc_html($label) . "</option>";
    }
    $html .= "</select>
          </div>

          <div class='field'>
            <label>Rareté</label>
            <select name='cyberwareimplant_rarete' required>";
    foreach ($rares as $k => $label) {
        if ($k === '')
            continue;
        $html .= "<option value='" . esc_attr($k) . "'>" . esc_html($label) . "</option>";
    }
    $html .= "</select>
          </div>

          <div class='field'>
            <label>Prix (€)</label>
            <input type='number' step='0.01' name='cyberwareimplant_prix_euro' value='0'>
          </div>

          <div class='field'>
            <label>Coût essence</label>
            <input type='number' name='cyberwareimplant_cout_essence' value='0'>
          </div>

          <div class='field'>
            <label>Emplacement</label>
            <input name='cyberwareimplant_emplacement' placeholder='tete / bras / ...'>
          </div>

          <div class='field'>
            <label>Photo</label>
            <input type='file' name='cyberwareimplant_image_implant' accept='image/*'>
            <div class='mini-help'>PNG/JPG/WebP • max 2MB</div>
          </div>

          <div class='field full'>
            <label>Description</label>
            <textarea class='textarea' name='cyberwareimplant_description' rows='3' placeholder='...'></textarea>
          </div>

          <div class='field'>
            <label class='checkline'>
              <input type='checkbox' name='cyberwareimplant_actif' checked> Actif
            </label>
          </div>

          <div class='actions full actions-row'>
            <button class='btn primary' type='submit'>Créer</button>
            <button class='btn' type='reset'>Reset</button>
          </div>
        </form>
      </div>
    </details>
  </section>
  ";

    /* CARDS */
    $html .= "<section class='grid'>";

    foreach ($implants as $imp) {
        $id = (int) ($imp['id'] ?? 0);
        $nom = $imp['nom_implant'] ?? '';
        $type_i = $imp['type_implant'] ?? '';
        $rare_i = $imp['rarete'] ?? '';
        $prix = $imp['prix_euro'] ?? 0;
        $ess = $imp['cout_essence'] ?? 0;
        $empl = $imp['emplacement'] ?? '';
        $desc = $imp['description'] ?? '';
        $actif = !empty($imp['actif']);

        $fab = '—';
        if (isset($imp['manufacturer_id']) && is_array($imp['manufacturer_id']) && count($imp['manufacturer_id']) >= 2) {
            $fab = $imp['manufacturer_id'][1];
        }

        $img_src = '';
        if (!empty($imp['image_implant']))
            $img_src = cyberwareimplant_image_src($imp['image_implant']);

        $initiale = mb_strtoupper(mb_substr($nom ?: 'I', 0, 1));

        $html .= "
    <article class='card'>
      <div class='card-head'>
        <div class='avatar'>";
        if ($img_src)
            $html .= "<img src='" . esc_attr($img_src) . "' alt='implant'>";
        else
            $html .= "<div class='fallback'>" . esc_html($initiale) . "</div>";
        $html .= "</div>
        <div class='who'>
          <div class='name'>" . esc_html($nom) . "</div>
          <div class='meta'>#{$id} • " . esc_html($types[$type_i] ?? $type_i) . " • " . esc_html($rares[$rare_i] ?? $rare_i) . "</div>
        </div>
      </div>

      <div class='stats'>
        <div class='stat'><span>Prix</span><b>" . esc_html($prix) . " €</b></div>
        <div class='stat'><span>Essence</span><b>" . esc_html($ess) . "</b></div>
        <div class='stat'><span>Empl.</span><b>" . esc_html($empl ?: '—') . "</b></div>
      </div>

      <div class='line'><span>Fabricant</span><b>" . esc_html($fab) . "</b></div>
      <div class='desc-mini'>" . esc_html(mb_strimwidth(strip_tags($desc), 0, 120, '…')) . "</div>

      <details class='drawer'>
        <summary class='btn ghost'>Modifier / Supprimer</summary>
        <div class='drawer-body'>
          <form class='form-grid cybercrudimplatns-form' method='post' enctype='multipart/form-data'>
            " . wp_nonce_field('cyberwareimplant_implants', 'cyberwareimplant_nonce', true, false) . "
            <input type='hidden' name='cyberwareimplant_action' value='update'>
            <input type='hidden' name='cyberwareimplant_implant_id' value='{$id}'>

            <div class='field'>
              <label>Nom</label>
              <input required name='cyberwareimplant_nom_implant' value='" . esc_attr($nom) . "'>
            </div>

            <div class='field'>
              <label>Type</label>
              <select name='cyberwareimplant_type_implant' required>";
        foreach ($types as $k => $label) {
            if ($k === '')
                continue;
            $sel = ($k === $type_i) ? "selected" : "";
            $html .= "<option value='" . esc_attr($k) . "' {$sel}>" . esc_html($label) . "</option>";
        }
        $html .= "</select>
            </div>

            <div class='field'>
              <label>Rareté</label>
              <select name='cyberwareimplant_rarete' required>";
        foreach ($rares as $k => $label) {
            if ($k === '')
                continue;
            $sel = ($k === $rare_i) ? "selected" : "";
            $html .= "<option value='" . esc_attr($k) . "' {$sel}>" . esc_html($label) . "</option>";
        }
        $html .= "</select>
            </div>

            <div class='field'><label>Prix (€)</label><input type='number' step='0.01' name='cyberwareimplant_prix_euro' value='" . esc_attr($prix) . "'></div>
            <div class='field'><label>Coût essence</label><input type='number' name='cyberwareimplant_cout_essence' value='" . esc_attr($ess) . "'></div>
            <div class='field'><label>Emplacement</label><input name='cyberwareimplant_emplacement' value='" . esc_attr($empl) . "'></div>

            <div class='field'>
              <label>Photo (remplacer)</label>
              <input type='file' name='cyberwareimplant_image_implant' accept='image/*'>
            </div>

            <div class='field full'>
              <label>Description</label>
              <textarea class='textarea' name='cyberwareimplant_description' rows='3'>" . esc_textarea($desc) . "</textarea>
            </div>

            <div class='field'>
              <label class='checkline'>
                <input type='checkbox' name='cyberwareimplant_actif' " . ($actif ? 'checked' : '') . "> Actif
              </label>
            </div>

            <div class='actions full'>
              <button class='btn primary' type='submit'>Enregistrer</button>
            </div>
          </form>

          <form method='post' class='delete' onsubmit=\"return confirm('Supprimer cet implant ?');\">
            " . wp_nonce_field('cyberwareimplant_implants', 'cyberwareimplant_nonce', true, false) . "
            <input type='hidden' name='cyberwareimplant_action' value='delete'>
            <input type='hidden' name='cyberwareimplant_implant_id' value='{$id}'>
            <button class='btn danger' type='submit'>Supprimer</button>
          </form>
        </div>
      </details>
    </article>
    ";
    }

    $html .= "</section>";
    $html .= "</div>";
    return $html;
}
add_filter('the_content', 'cyberwareimplant_render_implants_page');
