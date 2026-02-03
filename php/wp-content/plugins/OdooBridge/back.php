<?php

function odoobridge_administration_add_admin_page()
{
    add_submenu_page(
        'options-general.php',
        'Options OdooBridge',
        'Options OdooBridge',
        'manage_options',
        'odoobridge_administration',
        'odoobridge_administration_page'
    );
}

function odoobridge_administration_page()
{
    if (isset($_POST['submit'])) {
        if (isset($_POST['urlOdoo'])) {
            update_option('urlOdoo', sanitize_text_field($_POST['urlOdoo']));
        }
        if (isset($_POST['dbOdoo'])) {
            update_option('dbOdoo', sanitize_text_field($_POST['dbOdoo']));
        }
    }

    $db_actuel = get_option('dbOdoo');
    $url_actuel = get_option('urlOdoo');
    ?>
    <div class="wrap OdooBridge OdooBridgeBack">
        <h1>Mes options</h1>
        <form method="post" action="">
            <label for="dbOdoo">Nom base Odoo :</label>
            <input class="input" id="dbOdoo" name="dbOdoo" value="<?php echo esc_attr($db_actuel); ?>">

            <br><label for="urlOdoo">URL Odoo :</label>
            <input id="urlOdoo" name="urlOdoo" value="<?php echo esc_attr($url_actuel); ?>">

            <br><input type="submit" name="submit" class="button button-primary" value="Enregistrer" />
        </form>
    </div>
    <?php
}

add_action('admin_menu', 'odoobridge_administration_add_admin_page');

function odoobridge_add_custom_user_profile_apikey($user)
{
    printf(
        '
<h3>%1$s</h3>
<table class="form-table">
<tr>
<th><label for="odooapikey">%2$s</label></th>
<td>
  <input type="text" name="odooapikey" id="odooapikey" value="%3$s" class="regular-text" />
  <br /><span class="description">%4$s</span>
</td>
</tr>
</table>
',
        __('Extra Profile Information', 'locale'),
        __('Odoo API key', 'locale'),
        esc_attr(get_user_meta($user->ID, 'odooapikey', true)),
        __('Start typing API key', 'locale')
    );
}

function odoobridge_save_custom_user_profile_apikey($user_id)
{
    if (!current_user_can('edit_user', $user_id))
        return false;

    $odooapikey = isset($_POST['odooapikey']) ? $_POST['odooapikey'] : '';
    update_user_meta($user_id, 'odooapikey', $odooapikey);
}

add_action('show_user_profile', 'odoobridge_add_custom_user_profile_apikey');
add_action('personal_options_update', 'odoobridge_save_custom_user_profile_apikey');

add_action('edit_user_profile', 'odoobridge_add_custom_user_profile_apikey');
add_action('edit_user_profile_update', 'odoobridge_save_custom_user_profile_apikey');

add_action('init', 'odoobridge_traiter_formulaire_demande');

function odoobridge_traiter_formulaire_demande()
{
    if (!isset($_POST['odoobridge_demande'])) {
        return;
    }

    if (!is_user_logged_in()) {
        set_transient('odoobridge_erreur', "Tu dois être connecté.", 30);
        odoobridge_redirect_resultat('ko');
    }

    if (!isset($_POST['odoobridge_nonce']) || !wp_verify_nonce($_POST['odoobridge_nonce'], 'demandeImplantation')) {
        set_transient('odoobridge_erreur', "Nonce invalide.", 30);
        odoobridge_redirect_resultat('ko');
    }

    require_once __DIR__ . '/OdooPrimitive.php';

    $implant_id = isset($_POST['implant_id']) ? intval($_POST['implant_id']) : 0;
    $date = isset($_POST['date_reservation']) ? sanitize_text_field($_POST['date_reservation']) : '';
    $duree = isset($_POST['duree_reservation']) ? intval($_POST['duree_reservation']) : 0;

    $resultat = creerDemandeImplantation($implant_id, $date, $duree);

    odoobridge_redirect_resultat($resultat !== false ? 'ok' : 'ko');
}

function odoobridge_redirect_resultat($etat)
{
    $page = get_page_by_path('odooreservation', OBJECT, 'page');
    $url = $page ? get_permalink($page->ID) : home_url('/');

    wp_safe_redirect(add_query_arg(['odooBridge' => $etat], $url));
    exit;
}
