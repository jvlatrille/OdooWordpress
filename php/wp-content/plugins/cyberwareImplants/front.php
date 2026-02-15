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
  $implants = cyberwareimplant_odoo_implants_page($page, $q, $type, $rarete);




  if ($implants === false) {
    $html .= "<div class='panel danger'>Connexion Odoo impossible. Vérifie options + apikey + email.</div></div>";
    return $html;
  }

  $nb_pages = max(1, (int) ceil($total / 10));

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

  $html .= "<style>
    .cyber-table-wrap { overflow-x: auto; margin-top: 20px; }
    .cyber-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .cyber-table th, .cyber-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: middle; color: #475569; font-size: 14px; }
    .cyber-table th { background-color: #f8fafc; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
    .cyber-table tr:hover { background-color: #f1f5f9; }
    .cyber-table img.thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; background: #e2e8f0; }
    .cyber-table .fallback { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #e2e8f0; border-radius: 4px; font-weight: 600; color: #64748b; }
    .pagination { display: flex; gap: 5px; margin-top: 20px; justify-content: center; flex-wrap: wrap; }
    .page-btn { padding: 8px 12px; border: 1px solid #e2e8f0; background: #fff; color: #475569; text-decoration: none; border-radius: 4px; font-size: 14px; transition: all 0.2s; }
    .page-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .page-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; }
    /* Mobile responsive */
    @media (max-width: 768px) {
      .cyber-table thead { display: none; }
      .cyber-table tr { display: block; margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; }
      .cyber-table td { display: flex; justify-content: space-between; align-items: center; border-bottom: none; padding: 8px 0; }
      .cyber-table td::before { content: attr(data-label); font-weight: 600; color: #64748b; margin-right: 10px; }
      .cyber-table td.actions-cell { display: block; margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0; }
    }
  </style>";

  /* TABLE */
  $html .= "<div class='cyber-table-wrap'><table class='cyber-table'>
    <thead>
      <tr>
        <th>ID</th>
        <th>Photo</th>
        <th>Nom</th>
        <th>Type</th>
        <th>Rareté</th>
        <th>Prix</th>
        <th>Essence</th>
        <th>Empl.</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>";

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

    $html .= "<tr>
      <td data-label='ID'>#{$id}</td>
      <td data-label='Photo'>";
    if ($img_src)
      $html .= "<img src='" . esc_attr($img_src) . "' class='thumb' alt='implant'>";
    else
      $html .= "<div class='fallback'>" . esc_html($initiale) . "</div>";
    $html .= "</td>
      <td data-label='Nom'><b>" . esc_html($nom) . "</b><br><small>" . esc_html($fab) . "</small></td>
      <td data-label='Type'>" . esc_html($types[$type_i] ?? $type_i) . "</td>
      <td data-label='Rareté'>" . esc_html($rares[$rare_i] ?? $rare_i) . "</td>
      <td data-label='Prix'>" . esc_html($prix) . " €</td>
      <td data-label='Essence'>" . esc_html($ess) . "</td>
      <td data-label='Empl.'>" . esc_html($empl ?: '—') . "</td>
      <td class='actions-cell' data-label='Actions'>
        <details class='drawer' style='position:relative;'>
          <summary class='btn ghost' style='padding:5px 10px; font-size:12px;'>Modifier</summary>
          <div class='drawer-body' style='position:absolute; right:0; top:100%; z-index:10; background:#fff; border:1px solid #e2e8f0; padding:15px; border-radius:8px; width:300px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);'>
            <form class='cybercrudimplatns-form' method='post' enctype='multipart/form-data'>
              " . wp_nonce_field('cyberwareimplant_implants', 'cyberwareimplant_nonce', true, false) . "
              <input type='hidden' name='cyberwareimplant_action' value='update'>
              <input type='hidden' name='cyberwareimplant_implant_id' value='{$id}'>

              <div class='field'>
                <label>Nom</label>
                <input required name='cyberwareimplant_nom_implant' value='" . esc_attr($nom) . "' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;'>
              </div>

              <div class='field'>
                <label>Type</label>
                <select name='cyberwareimplant_type_implant' required style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;'>";
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
                <select name='cyberwareimplant_rarete' required style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;'>";
    foreach ($rares as $k => $label) {
      if ($k === '')
        continue;
      $sel = ($k === $rare_i) ? "selected" : "";
      $html .= "<option value='" . esc_attr($k) . "' {$sel}>" . esc_html($label) . "</option>";
    }
    $html .= "</select>
              </div>

              <div class='field'><label>Prix (€)</label><input type='number' step='0.01' name='cyberwareimplant_prix_euro' value='" . esc_attr($prix) . "' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;'></div>
              <div class='field'><label>Coût essence</label><input type='number' name='cyberwareimplant_cout_essence' value='" . esc_attr($ess) . "' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;'></div>
              <div class='field'><label>Emplacement</label><input name='cyberwareimplant_emplacement' value='" . esc_attr($empl) . "' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;'></div>

              <div class='field'>
                <label>Photo (remplacer)</label>
                <input type='file' name='cyberwareimplant_image_implant' accept='image/*' style='width:100%;'>
              </div>

              <div class='field full'>
                <label>Description</label>
                <textarea class='textarea' name='cyberwareimplant_description' rows='3' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;'>" . esc_textarea($desc) . "</textarea>
              </div>

              <div class='field'>
                <label class='checkline'>
                  <input type='checkbox' name='cyberwareimplant_actif' " . ($actif ? 'checked' : '') . "> Actif
                </label>
              </div>

              <div class='actions full' style='margin-top:10px; display:flex; gap:10px;'>
                <button class='btn primary' type='submit' style='background:#2563eb; color:fff; border:none; padding:8px 12px; border-radius:4px; cursor:pointer;'>Enregistrer</button>
              </div>
            </form>

            <form method='post' class='delete' onsubmit=\"return confirm('Supprimer cet implant ?');\" style='margin-top:10px; border-top:1px solid #eee; padding-top:10px;'>
              " . wp_nonce_field('cyberwareimplant_implants', 'cyberwareimplant_nonce', true, false) . "
              <input type='hidden' name='cyberwareimplant_action' value='delete'>
              <input type='hidden' name='cyberwareimplant_implant_id' value='{$id}'>
              <button class='btn danger' type='submit' style='background:#ef4444; color:fff; border:none; padding:8px 12px; border-radius:4px; cursor:pointer; width:100%;'>Supprimer</button>
            </form>
          </div>
        </details>
      </td>
    </tr>";
  }

  $html .= "</tbody></table></div>";

  /* PAGINATION */
  if ($nb_pages > 1) {
    $html .= "<div class='pagination'>";
    // Build query params base
    $params = [
      'q' => $q,
      'type' => $type,
      'rarete' => $rarete,
    ];
    // Remove empty params to clean URL
    $params = array_filter($params, fn($v) => $v !== '');

    // Page Links
    for ($i = 1; $i <= $nb_pages; $i++) {
      $p = array_merge($params, ['pg' => $i]);
      $url = add_query_arg($p, get_permalink());
      $active = ($i === $page) ? 'active' : '';
      $html .= "<a href='" . esc_url($url) . "' class='page-btn {$active}'>{$i}</a>";
    }
    $html .= "</div>";
  }

  $html .= "</section>";
  $html .= "</div>";
  return $html;
}
add_filter('the_content', 'cyberwareimplant_render_implants_page');
