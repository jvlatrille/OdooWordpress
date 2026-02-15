<?php
if (!defined('ABSPATH'))
    exit;

require_once __DIR__ . '/OdooPrimitive.php';

function cyberwareclient_add_admin_page()
{
    add_submenu_page(
        'options-general.php',
        'Options CyberwareClient',
        'Options CyberwareClient',
        'manage_options',
        'cyberwareclient_options',
        'cyberwareclient_admin_page'
    );
}
add_action('admin_menu', 'cyberwareclient_add_admin_page');

function cyberwareclient_admin_page()
{
    if (isset($_POST['cyberwareclient_submit'])) {
        if (isset($_POST['cyberwareclient_urlOdoo']))
            update_option('cyberwareclient_urlOdoo', sanitize_text_field($_POST['cyberwareclient_urlOdoo']));
        if (isset($_POST['cyberwareclient_dbOdoo']))
            update_option('cyberwareclient_dbOdoo', sanitize_text_field($_POST['cyberwareclient_dbOdoo']));
        echo '<div class="updated"><p>Options enregistrées.</p></div>';
    }

    $db = esc_attr(get_option('cyberwareclient_dbOdoo', ''));
    $url = esc_attr(get_option('cyberwareclient_urlOdoo', ''));

    ?>
    <div class="wrap">
        <h1>Options Cyberware Client</h1>
        <form method="post">
            <label>Nom base Odoo :</label><br>
            <input type="text" name="cyberwareclient_dbOdoo" value="<?php echo $db; ?>" style="width: 360px;"><br><br>

            <label>URL Odoo :</label><br>
            <input type="text" name="cyberwareclient_urlOdoo" value="<?php echo $url; ?>" style="width: 360px;"><br><br>

            <input type="submit" class="button button-primary" name="cyberwareclient_submit" value="Enregistrer">
        </form>
    </div>
    <?php
}

function cyberwareclient_profile_apikey_field($user)
{
    ?>
    <h3>Cyberware Client</h3>
    <table class="form-table">
        <tr>
            <th><label for="cyberwareclient_odooapikey">Odoo API key</label></th>
            <td>
                <input type="text" name="cyberwareclient_odooapikey" id="cyberwareclient_odooapikey"
                    value="<?php echo esc_attr(get_user_meta($user->ID, 'cyberwareclient_odooapikey', true)); ?>"
                    class="regular-text" />
            </td>
        </tr>
    </table>
    <?php
}

function cyberwareclient_profile_apikey_save($user_id)
{
    if (!current_user_can('edit_user', $user_id))
        return false;
    $cle = isset($_POST['cyberwareclient_odooapikey']) ? sanitize_text_field($_POST['cyberwareclient_odooapikey']) : '';
    update_user_meta($user_id, 'cyberwareclient_odooapikey', $cle);
}

add_action('show_user_profile', 'cyberwareclient_profile_apikey_field');
add_action('personal_options_update', 'cyberwareclient_profile_apikey_save');
add_action('edit_user_profile', 'cyberwareclient_profile_apikey_field');
add_action('edit_user_profile_update', 'cyberwareclient_profile_apikey_save');

function cyberwareclient_handle_posts()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
        return;

    if (!is_page('cyberwareclient'))
        return;

    if (!isset($_POST['cyberwareclient_nonce']) || !wp_verify_nonce($_POST['cyberwareclient_nonce'], 'cyberwareclient_clients')) {
        set_transient('cyberwareclient_erreur', "Nonce invalide.", 30);
        cyberwareclient_redirect('ko');
    }

    if (!is_user_logged_in()) {
        set_transient('cyberwareclient_erreur', "Tu dois être connecté.", 30);
        cyberwareclient_redirect('ko');
    }

    $action = sanitize_text_field($_POST['cyberwareclient_action'] ?? '');

    if ($action === 'create') {
        $nom_client = sanitize_text_field($_POST['cyberwareclient_nom_client'] ?? '');
        $pseudo = sanitize_text_field($_POST['cyberwareclient_pseudo'] ?? '');
        $user_id_form = absint($_POST['cyberwareclient_user_id'] ?? 0);
        $implants = isset($_POST['cyberwareclient_implants']) && is_array($_POST['cyberwareclient_implants'])
            ? array_map('absint', $_POST['cyberwareclient_implants'])
            : [];

        $image_b64 = cyberwareclient_fichier_vers_base64('cyberwareclient_image_client');
        $id = cyberwareclient_odoo_create_client($nom_client, $pseudo, $implants, $image_b64, $user_id_form);
        cyberwareclient_redirect($id ? 'ok' : 'ko');
    }

    if ($action === 'update') {
        $client_id = absint($_POST['cyberwareclient_client_id'] ?? 0);
        $nom_client = sanitize_text_field($_POST['cyberwareclient_nom_client'] ?? '');
        $pseudo = sanitize_text_field($_POST['cyberwareclient_pseudo'] ?? '');
        $user_id_form = absint($_POST['cyberwareclient_user_id'] ?? 0);
        $implants = isset($_POST['cyberwareclient_implants']) && is_array($_POST['cyberwareclient_implants'])
            ? array_map('absint', $_POST['cyberwareclient_implants'])
            : [];

        $image_b64 = cyberwareclient_fichier_vers_base64('cyberwareclient_image_client');
        $ok = cyberwareclient_odoo_update_client($client_id, $nom_client, $pseudo, $implants, $image_b64, $user_id_form);
        cyberwareclient_redirect($ok ? 'ok' : 'ko');
    }

    if ($action === 'delete') {
        $client_id = absint($_POST['cyberwareclient_client_id'] ?? 0);
        $ok = cyberwareclient_odoo_delete_client($client_id);
        cyberwareclient_redirect($ok ? 'ok' : 'ko');
    }

    cyberwareclient_redirect('ko');
}
add_action('template_redirect', 'cyberwareclient_handle_posts');

function cyberwareclient_redirect($etat)
{
    $url = get_permalink(get_page_by_path('cyberwareclient')->ID);
    $url = add_query_arg(['cybercrudclient' => $etat], $url);
    wp_safe_redirect($url);
    exit;
}

function cyberwareclient_fichier_vers_base64($champ)
{
    if (!isset($_FILES[$champ]) || !is_array($_FILES[$champ])) {
        return '';
    }

    $f = $_FILES[$champ];

    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return '';
    }

    $tmp = $f['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return '';
    }

    $max = 2 * 1024 * 1024; // 2MB
    if (($f['size'] ?? 0) > $max) {
        set_transient('cyberwareclient_erreur', "Image trop lourde (max 2MB).", 30);
        cyberwareclient_redirect('ko');
    }

    $mime = mime_content_type($tmp);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
        set_transient('cyberwareclient_erreur', "Format image non supporté.", 30);
        cyberwareclient_redirect('ko');
    }

    $contenu = file_get_contents($tmp);
    if ($contenu === false) {
        return '';
    }

    return base64_encode($contenu);
}
