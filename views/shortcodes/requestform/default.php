<?php
	/*
	 * Yetix Request Form shortcode default template
	 * this can be overwritten if you put a default.php file into a ./template_yetix/shortcode/requestform/ directory of your theme
	 * use a copy of this file as your code/markup base.
	 */

	//context
	$uniq_id        = wp_generate_uuid4();
	//support
	$align          = ( isset( $align ) ) ? 'align'.esc_attr( $align ) : false ;
	$class_name     = ( isset( $class_name ) ) ? esc_attr( $class_name ) : false ;
	//attributes
	$return_timeout = ( is_numeric( $return_timeout ) ) ? ( int ) $return_timeout : 1000;
	$return_url     = ( isset( $return_url ) ) ? $return_url : null;
	$return_type    = (
		in_array( $return_type, [ 'display_msg', 'redirect' ] )
		&& ( ! is_null( $return_url ) && 'redirect' === $return_type )
	) ? $return_type : 'display_msg';

	wp_enqueue_style( 'yetix_request__block_requestform_style' );
	wp_enqueue_script( 'yetix_request__block_requestform_script' );
	\whodunit\utility\WordPress::update_localize_script( 'yetix_request__block_requestform_script', 'requestform', [
		$uniq_id => [
			'nonce'                => wp_create_nonce( 'wp_rest' ),
			'hide_form_after_send' => $hide_form_after_send,
			'return'               => [
				'type'    => $return_type,
				'url'     => $return_url,
				'timeout' => $return_timeout,
			],
		],
		'ticket_api_url'       => get_rest_url( null, 'yetix/v1/tickets' ),
		'attachment_api_url'   => get_rest_url( null, 'yetix/v1/attachments' ),
		'localize'             => [
			'field_labels'  => \whodunit\utility\Localize::get_localized_field_labels()
		]
	] );

	if( 1 === \whodunit\utility\Tracker::get_zendesk_administrator_client()->get_status() ) :
?>
	<div id="<?php echo esc_attr( $uniq_id ); ?>" class="<?php echo ( $class_name ) ? esc_attr( $class_name ).' ' : ''; ?>yetix wp-block-yetix-requestform yetix-requestform<?php echo ( $align ) ? ' '.esc_attr( $align ): ''; ?>">
		<div class="yetix-requestform ui form-message"></div>
		<div class="yetix-requestform loading"><span class="spinner"></span></div>
		<form class="yetix-requestform ui form">
<?php
			\whodunit\utility\Helpers::zendesk_sorted_fields( null, true );
			echo '<p>'.esc_html__( 'Required fields are marked *', 'yetix-request-form' ).'</p>';
?>
			<button class="yetix-requestform submit ui button" type="submit"><?php esc_html_e( 'Send', 'yetix-request-form' ); ?></button>

		</form>
	</div>
<?php
	elseif( current_user_can( 'yetix_request__manage_plugin_options' ) ):
?>
	<div class="<?php echo ( $class_name ) ? esc_attr( $class_name ).' ' : ''; ?>yetix wp-block-yetix-requestform yetix-requestform<?php echo ( $align ) ? ' '.esc_attr( $align ): ''; ?>">
		<div class="yetix-requestform ui nag red message error">
			<?php esc_html_e( 'Yetix could not establish a connection to Zendesk, please check your Yetix configuration or ask an administrator to do it.', 'yetix-request-form' ); ?>
		</div>
	</div>
<?php
	endif;