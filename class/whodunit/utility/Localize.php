<?php
namespace whodunit\utility;

use whodunit\yetix\Plugin;

class Localize {

	static function get_localized_field_labels(){
		$field_labels             = [];
		$plugin                   = Plugin::getInstance();
		$name_field_options       = $plugin->get_options( 'zendesk_name_field' );
		$email_field_options      = $plugin->get_options( 'zendesk_email_field' );
		$attachment_field_options = $plugin->get_options( 'zendesk_attachment_field' );
		$optional_field_options   = $plugin->get_options( 'zendesk_fields' );

		$field_labels = [
			'name' => ( isset( $name_field_options[ 'label' ] ) && ! empty( $name_field_options[ 'label' ] ) )
				? esc_html( $name_field_options[ 'label' ] ) : esc_html__( 'Name', 'yetix-request-form' ),
			'email' => ( isset( $email_field_options[ 'label' ] ) && ! empty( $email_field_options[ 'label' ] ) )
				? esc_html( $email_field_options[ 'label' ] ) : esc_html__( 'Email', 'yetix-request-form' ),
			'attachment_token' => ( isset( $attachment_field_options[ 'label' ] ) && ! empty( $attachment_field_options[ 'label' ] ) )
				? esc_html( $attachment_field_options[ 'label' ] ) : esc_html__( 'Attachment', 'yetix-request-form' ),
		];
		if( ! empty( $optional_field_options ) ){
			foreach( $optional_field_options as $field_id => $field_options ){
				$field_data  = get_object_vars( Tracker::get_ticket_fields( $field_id ) );
				$field_name  = ( is_null( $field_id ) || in_array( $field_data[ 'type' ], [ 'subject','description' ] ) )
					? $field_data[ 'type' ] : sanitize_title( $field_data[ 'title' ] ).'_'.$field_data[ 'id' ];
				$field_labels[ $field_name ] = ( isset( $field_options[ 'label' ] ) && ! empty( $field_options[ 'label' ] ) )
					?esc_html( $field_options[ 'label' ] ) : esc_html( $field_data[ 'title' ] );
			}
		}
		return $field_labels;
	}

}