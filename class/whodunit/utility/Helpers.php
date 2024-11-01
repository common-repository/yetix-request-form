<?php
namespace whodunit\utility;

use whodunit\yetix\Plugin;

class Helpers{

	static function zendesk_core_fields( $disabled = false, $echo = false ){
		$core_fields              = '';
		$name_field_options       = Plugin::getInstance()->get_options( 'zendesk_name_field' );
		$email_field_options      = Plugin::getInstance()->get_options( 'zendesk_email_field' );
		$attachment_field_options = Plugin::getInstance()->get_options( 'zendesk_attachment_field' );

		if( isset( $name_field_options[ 'display' ] ) && 'true' === $name_field_options[ 'display' ] ) {
			$field_labels[ 'mane' ] = ( isset( $name_field_options[ 'label' ] ) && ! empty( $name_field_options[ 'label' ] ) )
				? esc_html( $name_field_options[ 'label' ] ) : esc_html__( 'Name', 'yetix-request-form' );
			$core_fields .= Helpers::zendesk_field( null, $name_field_options,
				[ 'type' => 'name', 'title' => $field_labels[ 'mane' ] ], $echo, $disabled
			);
		}
		if( isset( $email_field_options[ 'display' ] ) && 'true' === $email_field_options[ 'display' ] ) {
			$field_labels[ 'email' ] = ( isset( $email_field_options[ 'label' ] ) && ! empty( $email_field_options[ 'label' ] ) )
				? esc_html( $email_field_options[ 'label' ] ) : esc_html__( 'Email', 'yetix-request-form' );
			$core_fields .= Helpers::zendesk_field( null, $email_field_options,
				[ 'type' => 'email', 'title' => $field_labels[ 'email' ] ], $echo, $disabled
			);
		}
		if( isset( $attachment_field_options[ 'display' ] ) && 'true' === $attachment_field_options[ 'display' ] ) {
			$field_labels[ 'attachment' ] = ( isset( $attachment_field_options[ 'label' ] ) && ! empty( $attachment_field_options[ 'label' ] ) )
				? esc_html( $attachment_field_options[ 'label' ] ) : esc_html__( 'Attachment', 'yetix-request-form' );
			$core_fields .= Helpers::zendesk_attachment_field( $attachment_field_options, $echo, $disabled );
		}
		if( false === $echo ){ return $core_fields; }
		return "";
	}

	static function zendesk_option_fields( $disabled = false, $echo = false ){
		$option_fields          = '';
		$optional_field_options = Plugin::getInstance()->get_options( 'zendesk_fields' );

		if( ! empty( $optional_field_options ) ){
			foreach( $optional_field_options as $field_id => $field_options ){
				if( isset( $field_options[ 'display' ] ) && 'true' === $field_options[ 'display' ] ){
					$field_data = get_object_vars( Tracker::get_ticket_fields( $field_id ) );
					$field_name = ( is_null( $field_id ) || in_array( $field_data[ 'type' ], [ 'subject','description' ] ) )
						? $field_data[ 'type' ] : sanitize_title( $field_data[ 'title' ] ).'_'.$field_data[ 'id' ];
					$field_labels[ $field_name ] = ( isset( $field_options[ 'label' ] ) && ! empty( $field_options[ 'label' ] ) )
						?esc_html( $field_options[ 'label' ] ) : esc_html( $field_data[ 'title' ] );
					$option_fields .= Helpers::zendesk_field( $field_id, $field_options, $field_data, $echo,  $disabled );
				}
			}
		}
		if( false === $echo ){ return $option_fields; }
		return "";
	}

	static function zendesk_attachment_field( $field_options, $echo = false, $disabled = false ){
		if( false === $echo ){ ob_start(); }
		$field_title          = ( isset( $field_options[ 'label' ] ) && ! empty( $field_options[ 'label' ] ) )
			?esc_html( $field_options[ 'label' ] ) : esc_html__( 'Attachment', 'yetix-request-form' );
		$field_required_label = ( isset( $field_options[ 'required' ] ) && 'true' === ( $field_options[ 'required' ] ) ) ? ' *' : '';
		$field_disabled_attr  = ( $disabled ) ? ' disabled' : '';
		$field_required_attr  = ( isset( $field_options[ 'required' ] ) && 'true' === $field_options[ 'required' ] ) ? ' aria-required="true"' : '';
		echo '<div class="yetix-requestform field-wrapper field yetix-field" data-name="attachment_token">'
			.'<label class="yetix-requestform field-label" for="requestform_attachment_file">'.esc_html( $field_title.$field_required_label ).'</label>'
			.'<div class="ui action input">'
				.'<input class="yetix-requestform-readonly" type="text" placeholder="'.esc_html__( 'Add one or more files', 'yetix-request-form' ).'"'.( ( $disabled ) ? ' disabled' : 'readonly' ).'>'
				.'<input type="hidden" id="requestform_attachment_token"'.esc_attr( $field_disabled_attr ).'>'
				.'<input type="file" class="yetix-requestform-inputfile" id="requestform_attachment_file"'.esc_attr( $field_disabled_attr ).esc_attr( $field_required_attr ).' multiple>'
		        .'<button type="button" class="ui button yetix-requestform-attachment-upload" id="requestform_attachment_upload"'.esc_attr( $field_disabled_attr ).'>'.esc_html__( 'Upload', 'yetix-request-form' ).'</button>'
			.'</div>'
			.'<div class="ui active progress yetix-progress">'
				.'<div class="bar"><div class="progress"></div></div>'
			.'</div>'
			.'<div class="ui celled list yetix-requestform field file-list"></div>'
			.'<div class="yetix-requestform ui field field-message"></div>'
		.'</div>';
		if( false === $echo ){ return ob_get_clean(); }
		return "";
	}

	static function zendesk_sorted_fields( $disabled = false, $echo = false ){
		$fields                   = [];
		$a_big_number             = 99999;

		$client = Tracker::get_zendesk_administrator_client();
		try{
			$response = $client->ticketFields()->findAll();
		}catch( \Exception $e ){}
		$zendesk_fields = [];
		foreach( $response->ticket_fields as $field ){
			$zendesk_fields[ $field->id ] = $field;
			$field->is_system = Tracker::is_sytem_field( $field->type );
		}


		$optional_field_options   = Plugin::getInstance()->get_options( 'zendesk_fields' );
		$name_field_options       = Plugin::getInstance()->get_options( 'zendesk_name_field' );
		$email_field_options      = Plugin::getInstance()->get_options( 'zendesk_email_field' );
		$attachment_field_options = Plugin::getInstance()->get_options( 'zendesk_attachment_field' );

		if( isset( $name_field_options[ 'display' ] ) && 'true' === $name_field_options[ 'display' ] ) {
			//if order is not define we put a big number
			$order = ( is_numeric( @$name_field_options[ 'order' ] ) ) ? ( int ) $name_field_options[ 'order' ] : $a_big_number++;
			$field_labels[ 'mane' ] = ( isset( $name_field_options[ 'label' ] ) && ! empty( $name_field_options[ 'label' ] ) )
				? esc_html( $name_field_options[ 'label' ] ) : esc_html__( 'Name', 'yetix-request-form' );
			$fields[ $order ] = Helpers::zendesk_field( null, $name_field_options,
				[ 'type' => 'name', 'title' => $field_labels[ 'mane' ] ], false, $disabled
			);
		}
		if( isset( $email_field_options[ 'display' ] ) && 'true' === $email_field_options[ 'display' ] ) {
			$order = ( is_numeric( @$email_field_options[ 'order' ] ) ) ? ( int ) $email_field_options[ 'order' ] : $a_big_number++;
			$field_labels[ 'email' ] = ( isset( $email_field_options[ 'label' ] ) && ! empty( $email_field_options[ 'label' ] ) )
				? esc_html( $email_field_options[ 'label' ] ) : esc_html__( 'Email', 'yetix-request-form' );
			$fields[ $order ] = Helpers::zendesk_field( null, $email_field_options,
				[ 'type' => 'email', 'title' => $field_labels[ 'email' ] ], false, $disabled
			);
		}
		if( isset( $attachment_field_options[ 'display' ] ) && 'true' === $attachment_field_options[ 'display' ] ) {
			$order = ( is_numeric( @$attachment_field_options[ 'order' ] ) ) ? ( int ) $attachment_field_options[ 'order' ] : $a_big_number++;
			$field_labels[ 'attachment' ] = ( isset( $attachment_field_options[ 'label' ] ) && ! empty( $attachment_field_options[ 'label' ] ) )
				? esc_html( $attachment_field_options[ 'label' ] ) : esc_html__( 'Attachment', 'yetix-request-form' );
			$fields[ $order ] = Helpers::zendesk_attachment_field( $attachment_field_options, false, $disabled );
		}
		if( ! empty( $optional_field_options ) ){
			foreach( $optional_field_options as $field_id => $field_options ){
				//TODO:tmp solution, fields need to be fetch from zendesk and cached frequently, disabled or deleted field need to be
				//properly update has soon has possible.
				if( ! isset( $zendesk_fields[ $field_id ] ) ){ continue; }
				if( ! $zendesk_fields[ $field_id ]->active ){ continue; }

				if( isset( $field_options[ 'display' ] ) && 'true' === $field_options[ 'display' ] ){
					$field_data = get_object_vars( Tracker::get_ticket_fields( $field_id ) );
					$order = ( is_numeric( @$field_options[ 'order' ] ) ) ? ( int ) $field_options[ 'order' ] : $a_big_number++;
					$field_name = ( is_null( $field_id ) || in_array( $field_data[ 'type' ], [ 'subject','description' ] ) )
						? $field_data[ 'type' ] : sanitize_title( $field_data[ 'title' ] ).'_'.$field_data[ 'id' ];
					$field_labels[ $field_name ] = ( isset( $field_options[ 'label' ] ) && ! empty( $field_options[ 'label' ] ) )
						?esc_html( $field_options[ 'label' ] ) : esc_html( $field_data[ 'title' ] );
					$fields[ $order ] = Helpers::zendesk_field( $field_id, $field_options, $field_data, false,  $disabled );
				}
			}
		}
		ksort( $fields );
		if( false === $echo ){ ob_start(); }
			echo wp_kses(
				implode( "\r", $fields ),
				[
					'div'      => [ 'class' => true, 'id'  => true, 'aria-required' => true, 'data-*' => true ],
					'label'    => [ 'class' => true, 'for' => true ],
					'button'   => [ 'type' => true, 'class' => true, 'id'  => true ],
					'input'    => [ 'class' => true, 'type' => true, 'id' => true, 'name' => true, 'value' => true, 'aria-required' => true, 'multiple' => true ],
					'select'   => [ 'class' => true, 'type' => true, 'id' => true, 'name' => true, 'aria-required' => true , 'multiple' => true ],
					'option'   => [ 'class' => true, 'value' => true, 'selected' => true ],
					'textarea' => [ 'class' => true, 'type' => true,'id' => true, 'name' => true ],
				]
			);
		if( false === $echo ){ return ob_get_clean(); }
		return "";
	}


	static function zendesk_custom_filter( $custom_field_id ){
		$field = new \whodunit\yetix\model\ModelTicketField( $custom_field_id );
		$input_name = 'ticket-customfield';
		$input_id   = 'requestslist_ticket-customfield';
		if( 0 === $field->get_id() ){ return ''; }
		$helper = '<div class="yetix-field field field-wrapper" data-name="'.$input_name.'">'
			.'<label for="'.$input_id.'">'
			.'<span class="screen-reader-text">'
			.$field->get_title_in_portal()
			.'</span>'
			.'</label>';
		switch ( $field->get_type() ){
			case 'tagger' :
				$options = array_map( function ( $o )use( $field ){
					return '<option value="'.$field->get_id().':'.$o->value.'">'.$o->name.'</option>';
				}, array_merge( [ ( object )[ 'name' => $field->get_title(), 'value' => 'all' ] ], $field->get_options() ) );
				$helper .= '<select class="ui selection dropdown yetix-dropdown" id="'.$input_id.'">'
					.implode( PHP_EOL, $options )
					.'</select>';
				break;
			//basic
			default :
				$helper .= '<div class="ui action input">'
					.'<input type="text" id="'.$input_id.'">'
					.'</div>';
				break;
		}
		$helper .= '</div>';
		return $helper;
	}

	static function zendesk_field( $field_id, $field_options, $field_data = null, $echo = false, $disabled = false ){
		if( ! is_array( $field_data ) || !is_null( $field_id ) ){
			$field_data = get_object_vars( Tracker::get_ticket_fields( $field_id ) );
		}
		//TODO::redundant with RestRouteTicket.php l52
		$field_name           = ( is_null( $field_id ) || in_array( $field_data[ 'type' ], [ 'name', 'email', 'subject', 'description', 'tickettype', 'priority', 'status' ] ) )
			? $field_data[ 'type' ] : sanitize_title( $field_data[ 'title' ] ).'_'.$field_data[ 'id' ];
		$field_title          = ( isset( $field_options[ 'label' ] ) && ! empty( $field_options[ 'label' ] ) )
			?esc_html( $field_options[ 'label' ] ) : esc_html( $field_data[ 'title' ] );
		$field_required_label = ( isset( $field_options[ 'required' ] ) && 'true' === ( $field_options[ 'required' ] ) ) ? ' *' : '';
		$field_required_attr  = ( isset( $field_options[ 'required' ] ) && 'true' === $field_options[ 'required' ] ) ? ' aria-required="true"' : '';
		$field_disabled_attr  = ( $disabled ) ? ' disabled' : '';
		if( false === $echo ){ ob_start(); }
		switch( $field_data[ 'type' ] ){
			//text
			case 'name' :
			case 'email' :
			case 'subject' :
			case 'text' :
			case 'regexp' :
			case 'decimal' :
			case 'integer' :
				$default = ( isset( $field_options[ 'default' ] ) && ! empty( $field_options[ 'default' ] ) ) ? 'value="'.esc_html( $field_options[ 'default' ] ).'"' : '';
				echo '<div class="yetix-requestform field field-wrapper yetix-field" data-name="'.esc_attr( $field_name ).'">'
					.'<label for="requestform_'.esc_attr( $field_name ).'">'.esc_html( $field_title.$field_required_label ).'</label>'
					.'<input type="text" id="requestform_'.esc_attr( $field_name ).'"'.esc_attr( $default.$field_disabled_attr ).esc_attr( $field_required_attr ).'>'
					.'<div class="yetix-requestform field-message ui"></div>'
					.'</div>';
				break;
			//select
			case 'status' :
			case 'tickettype' :
			case 'multiselect' :
			case 'priority' :
			case 'tagger' :
				$option_key  = ( isset( $field_data[ 'custom_field_options' ] ) ) ? 'custom_field_options' : 'system_field_options';
				$multiselect = ( 'multiselect' === $field_data[ 'type' ] ) ? ' multiple' : '';
				$options     = array_map( function( $entry ){
					$selected = ( isset( $entry->default ) && $entry->default ) ? ' selected="selected"' : '';
					return '<option value="'.$entry->value.'"'.$selected.'>'.$entry->name.'</option>';
				}, $field_data[ $option_key ] );
				echo '<div class="yetix-requestform field field-wrapper" data-name="'.esc_attr( $field_name ).'">'
					.'<label for="requestform_'.esc_attr( $field_name ).'">'.esc_html( $field_title.$field_required_label ).'</label>'
					.'<select class="ui selection dropdown yetix-dropdown" id="requestform_'.esc_attr( $field_name ).'"'.esc_attr( $field_disabled_attr ).esc_attr( $field_required_attr ).esc_attr( $multiselect ).'>'
					.wp_kses( implode( '', $options ), [ 'option' => [ 'value' => [],'selected' => [] ] ] )
					.'</select>'
					.'<div class="yetix-requestform field-message ui"></div>'
					.'</div>';
				break;
			//textarea
			case 'description' :
				$default  = ( isset( $field_options[ 'default' ] ) && ! empty( $field_options[ 'default' ] ) ) ? esc_html( $field_options[ 'default' ] ) : '';
				echo '<div class="yetix-requestform field-wrapper field yetix-field" data-name="'.esc_attr( $field_name ).'">'
					.'<label for="requestform_description">'.esc_html( $field_title.$field_required_label ).'</label>'
					//.'<textarea id="requestform_'.esc_attr( $field_name ).'"'.esc_attr( $field_disabled_attr ).esc_attr( $field_required_attr ).'>'.esc_html( $default ).'</textarea>'
					.'<div class="" id="requestform_description" '.esc_attr( $field_disabled_attr ).esc_attr( $field_required_attr ).'>'.esc_html( $default ).'</div>'
					.'<div class="yetix-requestform field-message ui"></div>'
					.'</div>';
				break;
			case 'textarea' :
				$default  = ( isset( $field_options[ 'default' ] ) && ! empty( $field_options[ 'default' ] ) ) ? esc_html( $field_options[ 'default' ] ) : '';
				echo '<div class="yetix-requestform field-wrapper field yetix-field" data-name="'.esc_attr( $field_name ).'">'
					.'<label for="requestform_'.esc_attr( $field_name ).'">'.esc_html( $field_title.$field_required_label ).'</label>'
					.'<textarea id="requestform_'.esc_attr( $field_name ).'"'.esc_attr( $field_disabled_attr ).esc_attr( $field_required_attr ).'>'.esc_html( $default ).'</textarea>'
					.'<div class="yetix-requestform field-message ui"></div>'
					.'</div>';
				break;
			case 'date' :
				echo '<div class="yetix-requestform field-wrapper ui field calendar yetix-calendar" data-name="'.esc_attr( $field_name ).'">'
					.'<label for="requestform_'.esc_attr( $field_name ).'">'.esc_html( $field_title.$field_required_label ).'</label>'
					.'<input type="text" placeholder="'.esc_attr__( 'Date', 'yetix-request-form' ).'" id="requestform_'.esc_attr( $field_name ).'"'.esc_attr( $field_disabled_attr ).esc_attr( $field_required_attr ).'>'
					.'<div class="yetix-requestform field-message ui"></div>'
					.'</div>';
				break;
			case 'checkbox' :
				echo '<div class="yetix-requestform field-wrapper ui field yetix-checkbox" data-name="'.esc_attr( $field_name ).'">'
					.'<input type="checkbox" value="true" id="requestform_'.esc_attr( $field_name ).'"'.esc_attr( $field_required_attr ).'>'
					.'<label for="requestform_'.esc_attr( $field_name ).'">'.esc_html( $field_title.$field_required_label ).'</label>'
					.'<div class="yetix-requestform field-message ui"></div>'
					.'</div>';
				break;
		}

		if( false === $echo ){ return ob_get_clean(); }
		return "";
	}

	static function zendesk_get_clients_options( $echo = false ){
		if( false === $echo ){ ob_start(); }
		$user_options  = Plugin::getInstance()->get_options( 'user' );
		$oauth_clients = Tracker::get_oauth_clients();
		echo '<option value="">'.esc_html__( 'Select a client', 'yetix-request-form' ).'</option>';
		foreach( $oauth_clients as $oauth_client ){
			$selected = ( $oauth_client->id === ( int ) @$user_options[ 'client_id' ] ) ? 'selected="selected"' : '';
			echo '<option value="'.esc_attr( $oauth_client->id ).'" '.esc_attr( $selected ).'>'.esc_html( $oauth_client->name ).'</option>';
		}
		if( false === $echo ){ return ob_get_clean(); }
		return "";
	}

	static function attachment( $attachment, $template = 'default', $echo = true ){
		if( ! is_a( $attachment , 'whodunit\yetix\model\ModelAttachment' ) ){ return; }
		self::load_template( 'attachment', $template, null, [ 'attachment' => $attachment ], $echo );
	}

	static function ticket_field( $field, $template = 'default', $disable = false, $echo = true ){
		if( ! is_a( $field , 'whodunit\yetix\model\ModelTicketField' ) ){ return; }
		self::load_template( 'ticket_field', $template, $field->get_type(), [ 'field' => $field, 'disabled' => $disable ], $echo );
	}

	static protected function load_template( $template_type, $file_name, $file_prefix = null, $attributes = [], $echo = false ){
		$files = [];
		if( is_string( $file_prefix ) ){
			$files[] = get_stylesheet_directory().'/template_yetix/helpers/'.$template_type.'/'.$file_prefix.'-'.$file_name.'.php';
			$files[] = YETIX_REQUEST__PLUGIN_DIR.'/views/helpers/'.$template_type.'/'.$file_prefix.'-'.$file_name.'.php';
		}
		$files[] = get_stylesheet_directory().'/template_yetix/helpers/'.$template_type.'/'.$file_name.'.php';
		$files[] = YETIX_REQUEST__PLUGIN_DIR.'/views/helpers/'.$template_type.'/'.$file_name.'.php';
		if( false === $echo ){ ob_start(); }
		foreach( $files as $file ) {
			if( file_exists( $file ) ){
				if( is_array( $attributes ) ){
					extract( $attributes );
					unset( $attributes );
				}
				include( $file );
				break;
			}
		}
		if (false === $echo) { return ob_get_clean(); }
		return "";
	}

}