<?php
if (!defined('ABSPATH'))
    exit;

require_once __DIR__ . '/OdooPrimitive.php';

/* =========================
   3.1) Page options (urlOdoo + dbOdoo)
========================= */

function cybercrud_add_admin_page()
{
    add_submenu_page(
        'options-general.php',
        'Options CyberwareCRUD',
        'Options CyberwareCRUD',
        'manage_options',
        'cybercrud_options',
        'cybercrud_admin_page'
    );
}
add_action('admin_menu', 'cybercrud_add_admin_page');

function cybercrud_admin_page()
{
    if (isset($_POST['cybercrud_submit'])) {
        if (isset($_POST['cybercrud_urlOdoo']))
            update_option('cybercrud_urlOdoo', sanitize_text_field($_POST['cybercrud_urlOdoo']));
        if (isset($_POST['cybercrud_dbOdoo']))
            update_option('cybercrud_dbOdoo', sanitize_text_field($_POST['cybercrud_dbOdoo']));
        echo '<div class="updated"><p>Options enregistrées.</p></div>';
    }

    $db = esc_attr(get_option('cybercrud_dbOdoo', ''));
    $url = esc_attr(get_option('cybercrud_urlOdoo', ''));

    ?>
    <div class="wrap">
        <h1>Options CyberwareCRUD</h1>
        <form method="post">
            <label>Nom base Odoo :</label><br>
            <input type="text" name="cybercrud_dbOdoo" value="<?php echo $db; ?>" style="width: 360px;"><br><br>

            <label>URL Odoo :</label><br>
            <input type="text" name="cybercrud_urlOdoo" value="<?php echo $url; ?>" style="width: 360px;"><br><br>

            <input type="submit" class="button button-primary" name="cybercrud_submit" value="Enregistrer">
        </form>
    </div>
    <?php
}

/* =========================
   3.2) Champ "API key" sur profil user WP
========================= */

function cybercrud_profile_apikey_field($user)
{
    ?>
    <h3>CyberwareCRUD</h3>
    <table class="form-table">
        <tr>
            <th><label for="cybercrud_odooapikey">Odoo API key</label></th>
            <td>
                <input type="text" name="cybercrud_odooapikey" id="cybercrud_odooapikey"
                    value="<?php echo esc_attr(get_user_meta($user->ID, 'cybercrud_odooapikey', true)); ?>"
                    class="regular-text" />
            </td>
        </tr>
    </table>
    <?php
}

function cybercrud_profile_apikey_save($user_id)
{
    if (!current_user_can('edit_user', $user_id))
        return false;
    $cle = isset($_POST['cybercrud_odooapikey']) ? sanitize_text_field($_POST['cybercrud_odooapikey']) : '';
    update_user_meta($user_id, 'cybercrud_odooapikey', $cle);
}

add_action('show_user_profile', 'cybercrud_profile_apikey_field');
add_action('personal_options_update', 'cybercrud_profile_apikey_save');
add_action('edit_user_profile', 'cybercrud_profile_apikey_field');
add_action('edit_user_profile_update', 'cybercrud_profile_apikey_save');

/* =========================
   3.3) Interception des formulaires CRUD
   - create client
   - update client
   - delete client
========================= */

function cybercrud_handle_posts()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
        return;

    // on limite à la page support
    if (!is_page('cybercrud-clients'))
        return;

    // sécurité nonce (un seul pour tout)
    if (!isset($_POST['cybercrud_nonce']) || !wp_verify_nonce($_POST['cybercrud_nonce'], 'cybercrud_clients')) {
        set_transient('cybercrud_erreur', "Nonce invalide.", 30);
        cybercrud_redirect('ko');
    }

    if (!is_user_logged_in()) {
        set_transient('cybercrud_erreur', "Tu dois être connecté.", 30);
        cybercrud_redirect('ko');
    }

    // action
    $action = sanitize_text_field($_POST['cybercrud_action'] ?? '');

    if ($action === 'create') {
        $nom_client = sanitize_text_field($_POST['cybercrud_nom_client'] ?? '');
        $pseudo = sanitize_text_field($_POST['cybercrud_pseudo'] ?? '');
        $user_id = absint($_POST['cybercrud_user_id'] ?? 0);
        $implants = isset($_POST['cybercrud_implants']) && is_array($_POST['cybercrud_implants'])
            ? array_map('absint', $_POST['cybercrud_implants'])
            : [];

        $id = cybercrud_odoo_create_client($nom_client, $pseudo, $user_id, $implants);
        cybercrud_redirect($id ? 'ok' : 'ko');
    }

    if ($action === 'update') {
        $client_id = absint($_POST['cybercrud_client_id'] ?? 0);
        $nom_client = sanitize_text_field($_POST['cybercrud_nom_client'] ?? '');
        $pseudo = sanitize_text_field($_POST['cybercrud_pseudo'] ?? '');
        $user_id = absint($_POST['cybercrud_user_id'] ?? 0);
        $implants = isset($_POST['cybercrud_implants']) && is_array($_POST['cybercrud_implants'])
            ? array_map('absint', $_POST['cybercrud_implants'])
            : [];

        $ok = cybercrud_odoo_update_client($client_id, $nom_client, $pseudo, $user_id, $implants);
        cybercrud_redirect($ok ? 'ok' : 'ko');
    }

    if ($action === 'delete') {
        $client_id = absint($_POST['cybercrud_client_id'] ?? 0);
        $ok = cybercrud_odoo_delete_client($client_id);
        cybercrud_redirect($ok ? 'ok' : 'ko');
    }

    // si action inconnue
    cybercrud_redirect('ko');
}
add_action('template_redirect', 'cybercrud_handle_posts');

function cybercrud_redirect($etat)
{
    $url = get_permalink(get_page_by_path('cybercrud-clients')->ID);
    $url = add_query_arg(['cybercrud' => $etat], $url);
    wp_safe_redirect($url);
    exit;
}
