<?php

use whodunit\yetix\Plugin;

	$options = \whodunit\yetix\Plugin::getInstance()->get_options();
	$client  = \whodunit\utility\Tracker::get_zendesk_administrator_client();
	$notices = \whodunit\utility\Notices::get_instance();
	$notices->echo_notices();

	wp_enqueue_style( 'yetix_request__style-admin' );
	wp_enqueue_script( 'yetix_request__admin_setting_script' );
	wp_localize_script( 'yetix_request__admin_setting_script', 'yetix', [
		'save_settings_api_url'      => get_rest_url( null, 'yetix/v1/settings' ),
		'load_ticket_fields_api_url' => get_rest_url( null, 'yetix/v1/ticket_fields' ),
        'check_connection_api_url'   => get_rest_url( null, 'yetix/v1/tracker/check_token_connection' ),
		'max_upload_size'            => wp_max_upload_size(),
		'nonce'                      => wp_create_nonce( 'wp_rest' ),
	] );
//display general settings

?>
<div class="yetix">

	<h1><?php esc_html_e( 'Yetix settings', 'yetix-request-form' ); ?></h1>

    <div id="yetix_request__general_settings">

        <form id="yetix_request__general_settings_form" class="yetix__development__settings_form ui form">

            <h2><?php esc_html_e( 'General settings', 'yetix-request-form' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Once Zendesk is successfully connected, use Yetix Request Form block or shortcode to add the form to any post.', 'yetix-request-form' ); ?>
            </p>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><span class="ui label"><?php esc_html_e( 'Zendesk subdomain: ', 'yetix-request-form' ); ?></span></th>
                        <td>
                            <div class="yetix_request__settings_zendesk_domain-wrapper ui right labeled input field">
                                <div class="ui label">https://</div>
                                <input
                                    id    = "yetix_request__settings_zendesk_domain"
                                    type  = "text" placeholder="<?php esc_attr_e( 'my subdomain', 'yetix-request-form' ) ?>"
                                    name  = "options[zendesk_domain]"
                                    value = "<?php echo esc_attr( $options[ 'zendesk_domain' ] ); ?>"
                                >
                                <div class="ui label">.zendesk.com</div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><span class="ui label"><?php esc_html_e( 'Zendesk user: ', 'yetix-request-form' ); ?></span></th>
                        <td>
                            <div class="field">
                                <input
                                    id    = "yetix_request__settings_zendesk_user"
                                    type  = "email"
                                    name  = "options[zendesk_user]"
                                    value = "<?php echo esc_attr( $options[ 'zendesk_user' ] ); ?>"
                                >
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><span class="ui label"><?php esc_html_e( 'Zendesk API token: ', 'yetix-request-form' ); ?></span></th>
                        <td>
                            <div class="field">
                                <input
                                    id    = "yetix_request__settings_zendesk_token"
                                    type  = "password"
                                    name  = "options[zendesk_token]"
                                    value = "<?php echo esc_attr( $options[ 'zendesk_token' ] ); ?>"
                                >
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input
                    id    = "yetix_request__submit_general_settings"
                    class = "button button-primary"
                    type  = "submit"
                    name  = "submit"
                    value = "<?php esc_html_e( 'Save settings', 'yetix-request-form' ); ?>"
                >
            </p>

        </form>

    </div>

    <div id="yetix_request__status"></div>
    <div id="yetix_request__ticket_fields_settings"></div>
	
</div>