<?php
if (!defined('ABSPATH'))
    exit;

require_once __DIR__ . '/OdooPrimitive.php';

/* ===== options ===== */
function cyberwareimplant_add_admin_page()
{
    add_submenu_page(
        'options-general.php',
        'Options CyberwareImplant',
        'Options CyberwareImplant',
        'manage_options',
        'cyberwareimplant_options',
        'cyberwareimplant_admin_page'
    );
}
add_action('admin_menu', 'cyberwareimplant_add_admin_page');

function cyberwareimplant_admin_page()
{
    if (isset($_POST['cyberwareimplant_submit'])) {
        if (isset($_POST['cyberwareimplant_urlOdoo']))
            update_option('cyberwareimplant_urlOdoo', sanitize_text_field($_POST['cyberwareimplant_urlOdoo']));
        if (isset($_POST['cyberwareimplant_dbOdoo']))
            update_option('cyberwareimplant_dbOdoo', sanitize_text_field($_POST['cyberwareimplant_dbOdoo']));
        echo '<div class="updated"><p>Options enregistrées.</p></div>';
    }

    $db = esc_attr(get_option('cyberwareimplant_dbOdoo', ''));
    $url = esc_attr(get_option('cyberwareimplant_urlOdoo', ''));

    echo '<div class="wrap"><h1>Options CyberwareImplant</h1>
    <form method="post">
      <label>Nom base Odoo :</label><br>
      <input type="text" name="cyberwareimplant_dbOdoo" value="' . $db . '" style="width:360px"><br><br>
      <label>URL Odoo :</label><br>
      <input type="text" name="cyberwareimplant_urlOdoo" value="' . $url . '" style="width:360px"><br><br>
      <input type="submit" class="button button-primary" name="cyberwareimplant_submit" value="Enregistrer">
    </form></div>';
}

/* ===== apikey profil ===== */
function cyberwareimplant_profile_apikey_field($user)
{ ?>
    <h3>CyberwareImplant</h3>
    <table class="form-table">
        <tr>
            <th><label for="cyberwareimplant_odooapikey">Odoo API key</label></th>
            <td><input type="text" name="cyberwareimplant_odooapikey" id="cyberwareimplant_odooapikey"
                    value="<?php echo esc_attr(get_user_meta($user->ID, 'cyberwareimplant_odooapikey', true)); ?>"
                    class="regular-text" /></td>
        </tr>
    </table>
<?php }

function cyberwareimplant_profile_apikey_save($user_id)
{
    if (!current_user_can('edit_user', $user_id))
        return false;
    $cle = isset($_POST['cyberwareimplant_odooapikey']) ? sanitize_text_field($_POST['cyberwareimplant_odooapikey']) : '';
    update_user_meta($user_id, 'cyberwareimplant_odooapikey', $cle);
}

add_action('show_user_profile', 'cyberwareimplant_profile_apikey_field');
add_action('personal_options_update', 'cyberwareimplant_profile_apikey_save');
add_action('edit_user_profile', 'cyberwareimplant_profile_apikey_field');
add_action('edit_user_profile_update', 'cyberwareimplant_profile_apikey_save');

function cyberwareimplant_fichier_vers_base64($champ)
{
    if (!isset($_FILES[$champ]) || !is_array($_FILES[$champ]))
        return '';
    $f = $_FILES[$champ];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)
        return '';

    $tmp = $f['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp))
        return '';

    $max = 2 * 1024 * 1024;
    if (($f['size'] ?? 0) > $max) {
        set_transient('cyberwareimplant_erreur', "Image trop lourde (max 2MB).", 30);
        cyberwareimplant_redirect('ko');
    }

    $mime = mime_content_type($tmp);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
        set_transient('cyberwareimplant_erreur', "Format image non supporté.", 30);
        cyberwareimplant_redirect('ko');
    }

    $contenu = file_get_contents($tmp);
    return $contenu ? base64_encode($contenu) : '';
}

/* ===== handle posts ===== */
function cyberwareimplant_handle_posts()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
        return;
    if (!is_page('cyberwareimplants'))
        return;

    if (!isset($_POST['cyberwareimplant_nonce']) || !wp_verify_nonce($_POST['cyberwareimplant_nonce'], 'cyberwareimplant_implants')) {
        set_transient('cyberwareimplant_erreur', "Nonce invalide.", 30);
        cyberwareimplant_redirect('ko');
    }

    if (!is_user_logged_in()) {
        set_transient('cyberwareimplant_erreur', "Tu dois être connecté.", 30);
        cyberwareimplant_redirect('ko');
    }

    $action = sanitize_text_field($_POST['cyberwareimplant_action'] ?? '');

    if ($action === 'create' || $action === 'update') {
        $vals = [
            'nom_implant' => sanitize_text_field($_POST['cyberwareimplant_nom_implant'] ?? ''),
            'description' => sanitize_textarea_field($_POST['cyberwareimplant_description'] ?? ''),
            'type_implant' => sanitize_text_field($_POST['cyberwareimplant_type_implant'] ?? ''),
            'rarete' => sanitize_text_field($_POST['cyberwareimplant_rarete'] ?? ''),
            'prix_euro' => (float) ($_POST['cyberwareimplant_prix_euro'] ?? 0),
            'cout_essence' => (int) ($_POST['cyberwareimplant_cout_essence'] ?? 0),
            'emplacement' => sanitize_text_field($_POST['cyberwareimplant_emplacement'] ?? ''),
            'actif' => isset($_POST['cyberwareimplant_actif']) ? true : false,
        ];

        $img = cyberwareimplant_fichier_vers_base64('cyberwareimplant_image_implant');
        if ($img !== '')
            $vals['image_implant'] = $img;

        if ($action === 'create') {
            $id = cyberwareimplant_odoo_create_implant($vals);
            cyberwareimplant_redirect($id ? 'ok' : 'ko');
        } else {
            $implant_id = absint($_POST['cyberwareimplant_implant_id'] ?? 0);
            $ok = cyberwareimplant_odoo_update_implant($implant_id, $vals);
            cyberwareimplant_redirect($ok ? 'ok' : 'ko');
        }
    }

    if ($action === 'delete') {
        $implant_id = absint($_POST['cyberwareimplant_implant_id'] ?? 0);
        $ok = cyberwareimplant_odoo_delete_implant($implant_id);
        cyberwareimplant_redirect($ok ? 'ok' : 'ko');
    }

    cyberwareimplant_redirect('ko');
}
add_action('template_redirect', 'cyberwareimplant_handle_posts');

function cyberwareimplant_redirect($etat)
{
    $page = get_page_by_path('cyberwareimplants');
    $url = $page ? get_permalink($page->ID) : home_url('/');
    wp_safe_redirect(add_query_arg(['cyberwareimplant' => $etat], $url));
    exit;
}
