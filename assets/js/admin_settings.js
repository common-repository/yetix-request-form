( function( $ ){
	const { __, _x, _n, _nx, sprintf } = wp.i18n

	//load fields
	function load_fields( node ){
		$( node ).addClass( 'loading' )
		$.ajax( {
			url         : yetix.load_ticket_fields_api_url,
			beforeSend  : function( xhr ){ xhr.setRequestHeader( 'X-WP-Nonce', yetix.nonce ) },
			method      : 'GET',
			success     : function ( response ){
				helper_fields( node, response.code, response.message, response.fields, response.options )
				$( node ).removeClass( 'loading' )
			},
			error       : function ( response ) {
				helper_fields( node, response.responseJSON.code, response.responseJSON.message )
				$( node ).removeClass( 'loading' )
			}
		} )
	}

	//check connection status
	function load_connection_status( node ){
		$( node ).addClass( 'loading' )
		$.ajax( {
			url         : yetix.check_connection_api_url,
			beforeSend  : function( xhr ){ xhr.setRequestHeader( 'X-WP-Nonce', yetix.nonce ) },
			method      : 'GET',
			success     : function ( response ){
				helper_status( node, response.code, response.message )
				reload_fields()
				$( node ).removeClass( 'loading' )
			},
			error       : function ( response ) {
				helper_status( node, response.responseJSON.code, response.responseJSON.message )
				reload_fields()
				$( node ).removeClass( 'loading' )
			}
		} )
	}

	//save option
	function save_settings( form ){
		var form_data = process_form_data( form )
		enable_loading( form )
		disable_form( form )

		$.ajax( {
			url         : yetix.save_settings_api_url,
			beforeSend  : function( xhr ){ xhr.setRequestHeader( 'X-WP-Nonce', yetix.nonce ) },
			method      : 'POST',
			data        : form_data,
			processData : false,
			contentType : false,
			cache       : false,
			success     : function ( response ){
				disable_loading( form )
				enable_form( form )
				display_notice( response.code, response.message )
				reload_status();
			},
			error       : function ( response ) {
				disable_loading( form )
				enable_form( form )
				display_notice( response.responseJSON.code, response.responseJSON.message, true )
				reload_status();
			}
		} )
	}

	function reload_status(){
		load_connection_status( $( '#yetix_request__status' ) )
	}
	function reload_fields(  ){
		load_fields( $( '#yetix_request__ticket_fields_settings' ) )
	}

	function helper_status( node, code, message ){
		var table              = $( '<table class="form-table" role="presentation"><tbody></tbody></table>' )
		var table_tr           = $( '<tr class="ui message '+code+'"></tr>' )
		var table_col_title    = $( '<th scope="row">'+__( 'Current connection status', 'yetix-request-form' )+'</th>' )
		var table_col_message  = $( '<td>'+message+'</td>' )
		table_tr.append( [ table_col_title, table_col_message ] )
		table.find( 'tbody' ).append( table_tr )
		$( node ).html( table )
	}

	function helper_fields( node, code, message, fields = null, options = null){
		var form = $( '' ) //TODO::set a default message when nothing can be loaded
		if( 'zendesk_ticket_fields_load' === code ) {
			var localize = { 'type_labels' : {
					'subject'           : __( 'Text', 'yetix-request-form' ),
					'tickettype'        : __( 'Ticket type', 'yetix-request-form' ),
					'tagger'            : __( 'Dropdown list', 'yetix-request-form' ),
					'description'       : __( 'Description', 'yetix-request-form' ),
					'textarea'          : __( 'Multiline', 'yetix-request-form' ),
					'status'            : __( 'Status', 'yetix-request-form' ),
					'priority'          : __( 'Priority', 'yetix-request-form' ),
					'text'              : __( 'Text', 'yetix-request-form' ),
					'date'              : __( 'Date', 'yetix-request-form' ),
					'group'             : __( 'Group', 'yetix-request-form' ),
					'assignee'          : __( 'Assignee', 'yetix-request-form' ),
					'multiselect'       : __( 'Multiselect Dropdown list', 'yetix-request-form' ),
					'decimal'           : __( 'Decimal', 'yetix-request-form' ),
					'partialcreditcard' : __( 'Credit card', 'yetix-request-form' ),
					'checkbox'          : __( 'Checkbox', 'yetix-request-form' ),
					'integer'           : __( 'Numeric', 'yetix-request-form' ),
					'regexp'            : __( 'Regular expression', 'yetix-request-form' ),
				}
			}
			var title         = $( '<h2>'+__( 'Zendesk ticket fields', 'yetix-request-form')+'</h2>' )
			var form          = $( '<form id="yetix_request__ticket_fields_settings_form" class="yetix__development__settings_form ui form"></form>' )
			var description   = $( '<p class="description">'+__( 'Set default fields settings.', 'yetix-request-form')+'</p>' )
			var fields_inputs = $( '<table class="yetix_request__settings_field_board ui very basic table"></table>' )
			var table_head    = $( '<thead><tr>'
				+'<th class="th-name">'+__( 'Name', 'yetix-request-form' )+'</th>'
				+'<th class="th-type aligned">'+__( 'Type', 'yetix-request-form' )+'</th>'
				+'<th class="th-format aligned">'+__( 'Format', 'yetix-request-form' )+'</th>'
				+'<th class="th-display center aligned">'+__( 'Display', 'yetix-request-form' )+'</th>'
				+'<th class="th-required center aligned">'+__( 'Required', 'yetix-request-form' )+'</th>'
				+'</tr></thead>'
			)
			var table_body    = $( '<tbody class="ui accordion"></tbody>' )
			var submit        = $( '<p class="submit"><input type = "submit" name = "submit" id="yetix_request__submit_ticket_fields_settings" class="button button-primary" value="'+__( 'Save settings', 'yetix-request-form' )+'"></input></p>' )

			var read_only     = ' onclick="return false;"'
			var selected      = ' checked="checked"'
			var locked        = '<i class="lock icon"></i> '
			var field_grip    = '<i class="handle grip vertical icon"></i>'
			var disabled      = ' disabled="disabled"';
			var sort_fields   = []

			//name field
			var the_field        = $( '<tr class="the-field"></tr>' )
			var field_data       = $( '<td colspan="5"></td>' )

			var field_row1       = $( '<div class="title"></div>' )
			var field_title      = $( '<div class="td-field-name">'+field_grip+'<div class="field-name-clicker"><i class="icon dropdown"></i><strong>'+__( 'Name', 'yetix-request-form' )+'</strong>'+locked+'</div></div>' )
			var field_type       = $( '<div class="td-field-type aligned"><div class="field-name-type"><div class="field-type ui horizontal label">'+__( 'System', 'yetix-request-form' )+'</div><div></div>' )
			var field_format     = $( '<div class="td-field-format aligned"><div class="field-format ui horizontal label">'+__( 'Text', 'yetix-request-form' )+'</div></div>' )
			var field_display    = $( '<div class="td-field-display center aligned"><div class="ui toggle fitted checkbox"><input type="checkbox" name="options[zendesk_name_field][display]" value="true"'+selected+read_only+disabled+'><label></label></div></div>')
			var field_required   = $( '<div class="td-field-required center aligned"><div class="ui toggle fitted checkbox"><input type="checkbox" name="options[zendesk_name_field][required]" value="true"'+selected+read_only+disabled+'><label></label></div></div>' )

			var field_row2       = $( '<div class="content"></div>' )
			var field_overwrite  = $( '<div class="field"><label>'+__( 'Overwrite label', 'yetix-request-form' )+'</label><input type="text" name="options[zendesk_name_field][label]" value="'+options.name_field.label+'"></div>' )
			var field_default    = $( '<div class="field"><label>'+__( 'Default value', 'yetix-request-form' )+'</label><input type="text" name="options[zendesk_name_field][default]" value="'+options.name_field.default+'"></div>' )
			var field_order      = $( '<input type="hidden" class="order" name="options[zendesk_name_field][order]" value="'+options.name_field.order+'">' )

			field_row1.append( [ field_title, field_type, field_format, field_display, field_required ] )
			field_row2.append( [ field_overwrite, field_default, field_order ] )
			field_data.append( [ field_row1, field_row2 ] )
			the_field.append( [ field_data ] )
			sort_fields.push( the_field )


			//email field
			var the_field        = $( '<tr class="the-field"></tr>' )
			var field_data       = $( '<td colspan="5"></td>' )

			var field_row1       = $( '<div class="title"></div>' )
			var field_title      = $( '<div class="td-field-name">'+field_grip+'<div class="field-name-clicker"><i class="icon dropdown"></i><strong>'+__( 'Email', 'yetix-request-form' )+'</strong>'+locked+'</div></div>' )
			var field_type       = $( '<div class="td-field-type aligned"><div class="field-name-origin"><div class="field-origin ui horizontal label">'+__( 'System', 'yetix-request-form' )+'</div><div></div>' )
			var field_format     = $( '<div class="td-field-format aligned"><div class="field-format ui horizontal label">'+__( 'Email', 'yetix-request-form' )+'</div></div>' )
			var field_display    = $( '<div class="td-field-display center aligned"><div class="ui toggle fitted checkbox"><input type="checkbox" name="options[zendesk_name_field][display]" value="true"'+selected+read_only+disabled+'><label></label></div></div>')
			var field_required   = $( '<div class="td-field-required center aligned"><div class="ui toggle fitted checkbox"><input type="checkbox" name="options[zendesk_email_field][required]" value="true"'+selected+read_only+disabled+'><label></label></div></div>' )
			var field_row2       = $( '<div class="content"></div>' )
			var field_overwrite  = $( '<div class="field"><label>'+__( 'Overwrite label', 'yetix-request-form' )+'</label><input type="text" name="options[zendesk_email_field][label]" value="'+options.email_field.label+'"></div>' )
			var field_default    = $( '<div class="field"><label>'+__( 'Default value', 'yetix-request-form' )+'</label><input type="text" name="options[zendesk_email_field][default]" value="'+options.email_field.default+'"></div>' )
			var field_order      = $( '<input type="hidden" class="order" name="options[zendesk_email_field][order]" value="'+options.email_field.order+'">' )

			field_row1.append( [ field_title, field_type, field_format, field_display, field_required ] )
			field_row2.append( [ field_overwrite, field_default, field_order ] )

			field_data.append( [ field_row1, field_row2 ] )
			the_field.append( [ field_data ] )
			sort_fields.push( the_field )

			//attachment field
			var display_selected        = ( 'true' === options.attachment_field.display ) ? selected : '';
			var required_selected       = ( 'true' === options.attachment_field.required ) ? selected : '';

			var the_field        = $( '<tr class="the-field"></tr>' )
			var field_data       = $( '<td colspan="5"></td>' )

			var field_row1        = $( '<div class="title"></div>' )
			var field_title       = $( '<div class="td-field-name">'+field_grip+'<div class="field-name-clicker"><i class="icon dropdown"></i><strong>'+__( 'Attachment', 'yetix-request-form' )+'</strong></div></div>' )
			var field_type        = $( '<div class="td-field-type aligned"><div class="field-name-origin"><div class="field-origin ui horizontal label">'+__( 'System', 'yetix-request-form' )+'</div><div></div>' )
			var field_format      = $( '<div class="td-field-format aligned"><div class="field-format ui horizontal label">'+__( 'Attachment', 'yetix-request-form' )+'</div></div>' )
			var field_display     = $( '<div class="td-field-display center aligned"><div class="ui toggle fitted checkbox"><input type="checkbox" name="options[zendesk_attachment_field][display]" value="true"'+display_selected+'><label></label></div></div>')
			var field_required    = $( '<div class="td-field-required center aligned"><div class="ui toggle fitted checkbox"><input type="checkbox" name="options[zendesk_attachment_field][required]" value="true"'+required_selected+'><label></label></div></div>' )

			var field_row2        = $( '<div class="content"></div>' )
			var field_overwrite   = $( '<div class="field"><label>'+__( 'Overwrite label', 'yetix-request-form' )+'</label><input type="text" name="options[zendesk_attachment_field][label]" value="'+options.attachment_field.label+'">' )
			var field_max_size    = $( '<div class="field"><div class="ui right labeled input">'+
				'<label class="ui label">'+__( 'Maximum file size', 'yetix-request-form' )+'</label>'+
				'<input type="text" name="options[zendesk_attachment_field][max_size]" value="'+options.attachment_field.max_size+'">'+
				//translators: argument 1 is a maximum bytes size accepted by wp and the server config
				'<div class="ui label">'+sprintf( __( 'Bytes ( %s max )', 'yetix-request-form' ), yetix.max_upload_size )+'</div></div>'
			)
			var field_allowed_ext = $( '<div class="field"><label>'+__( 'Allowed file extensions list', 'yetix-request-form' )+'</label><textarea name="options[zendesk_attachment_field][allowed_ext]">'+options.attachment_field.allowed_ext+'</textarea>' )
			var field_order      = $( '<input type="hidden" class="order" name="options[zendesk_attachment_field][order]" value="'+options.attachment_field.order+'">' )

			field_row1.append( [ field_title, field_type, field_format, field_display, field_required ] )
			field_row2.append( [ field_overwrite, field_max_size, field_allowed_ext, field_order ] )

			field_data.append( [ field_row1, field_row2 ] )
			the_field.append( [ field_data ] )
			sort_fields.push( the_field )

			fields.forEach( function( field ){
				//group, assignee, partialcreditcard, not supported
				if( ! field.active || [ 'group', 'assignee', 'partialcreditcard' ].includes( field.type ) ){ return }
				if( null == options.custom_fields[ field.id ] )
					options.custom_fields[ field.id ] = { 'display' : false, 'label' : '', 'default' : '' }
				if( null == options.custom_fields[ field.id ].display )
					options.custom_fields[ field.id ].display = ''
				if( null == options.custom_fields[ field.id ].label )
					options.custom_fields[ field.id ].label = ''
				if( null == options.custom_fields[ field.id ].default )
					options.custom_fields[ field.id ].default = ''

				var system_label      = ( field.is_system ) ? __( 'System', 'yetix-request-form' ) : __( 'Custom', 'yetix-request-form' )
				var is_vital          = ( [ 'subject', 'description' ].includes( field.type ) ) ? true : false
				var is_disabled       = ( is_vital ) ? ' disabled="disabled"' : ''
				var is_locked         = ( is_vital ) ? locked : ''
				var display_selected  = ( 'true' === options.custom_fields[ field.id ].display || is_vital ) ? selected : ''
				var required_selected = ( 'true' === options.custom_fields[ field.id ].required || is_vital ) ? selected : ''
				var is_read_only      = ( is_vital ) ? read_only : ''

				var the_field        = $( '<tr class="the-field"></tr>' )
				var field_data       = $( '<td colspan="5"></td>' )

				var field_row1       = $( '<div class="title"></div>' )
				var field_title      = $( '<div class="td-field-name">'+field_grip+'<div class="field-name-clicker"><i class="icon dropdown"></i><strong>'+field.title+'</strong>'+is_locked+'</div></div>' )
				var field_type       = $( '<div class="td-field-type aligned"><div class="field-name-origin"><div class="field-origin ui horizontal label">'+system_label+'</div><div></div>' )
				var field_format     = $( '<div class="td-field-format aligned"><div class="field-format ui horizontal label">'+localize.type_labels[ field.type ]+'</div></div>' )
				var field_display    = $( '<div class="td-field-display center aligned"><div class="ui toggle fitted checkbox"><input type="checkbox" name="options[zendesk_fields]['+field.id+'][display]" value="true"'+display_selected+is_disabled+is_read_only+'><label></label></div></div>')
				var field_required   = $( '<div class="td-field-required center aligned"><div class="ui toggle fitted checkbox"><input type="checkbox" name="options[zendesk_fields]['+field.id+'][required]" value="true"'+required_selected+is_disabled+is_read_only+'><label></label></div></div>' )

				var field_row2       = $( '<div class="content"></div>' )
				var field_overwrite  = $( '<div class="field"><label>'+__( 'Overwrite label', 'yetix-request-form' )+'</label><input type="text" name="options[zendesk_fields]['+field.id+'][label]" value="'+options.custom_fields[ field.id ].label+'"></div>' )
				var field_default    = ( ! [ 'multiselect', 'checkbox', 'partialcreditcard', 'group', 'assignee', 'tagger', 'status', 'tickettype', 'priority', 'date' ].includes( field.type ) )
					? $( '<div class="field"><label>'+__( 'Default value', 'yetix-request-form' )+'</label><input type="text" name="options[zendesk_fields]['+field.id+'][default]" value="'+options.custom_fields[ field.id ].default+'"></div>' ) : $( '' )
				var field_order      = $( '<input type="hidden" class="order" name="options[zendesk_fields]['+field.id+'][order]" value="'+options.custom_fields[ field.id ].order+'">' )

				field_row1.append( [ field_title, field_type, field_format, field_display, field_required ] )
				field_row2.append( [ field_overwrite, field_default, field_order ] )

				field_data.append( [ field_row1, field_row2 ] )
				the_field.append( [ field_data ] )
				sort_fields.push( the_field )

			} )

			table_body.sortable( {
				containment : form,
				item        : '> tr',
				handle      : '.handle',
				cursor      : 'move',
				stop        : function( e, ui ){
					ui.item.parent().find( 'input.order' ).each( function( i ){ $( this ).val( i.toString() ) } )
				},
				start: function( e, ui ) {
					$( ui.helper ).height( '' ); $( ui.placeholder ).find('td').height( $( ui.helper ).height() );
				}
			} )


			sort_fields.sort( function( a,b ){
				var val_a = parseInt( $( a ).find( 'input.order' ).val() )
				var val_b = parseInt( $( b ).find( 'input.order' ).val() )
				if( isNaN( val_a ) || isNaN( val_b ) ){
					return ( ( isNaN( val_a ) && isNaN( val_b ) ) ? 0 : ( ( isNaN( val_a ) ) ? 1 : -1 ) )
				}
				return ( ( val_a < val_b ) ? -1 : ( ( val_a > val_b ) ? 1 : 0 ) )
			} )
			
			table_body.append( sort_fields )

			fields_inputs.append( [ table_head, table_body ] )

			//set trigger
			form.on( 'submit', function( e ){
				e.preventDefault()
				save_settings( this )
			} )

			//megazord power
			form.append( [ title, description, fields_inputs, submit ] )

			//init fomantic
			form.find( '.accordion' ).accordion( { selector: { trigger: '.field-name-clicker' } } )

			// Prevent drag and drop problem, quick remove accordeon content before any drag
			$( form ).find( 'i.handle' ).on( 'mousedown', function( e ){
				$(e.currentTarget).closest('.the-field').addClass('ready-for-drag');
				var the_field_index = $( form ).find('tr' ).index( $(e.currentTarget).closest('.the-field') ) - 1;
				$( form ).find('.accordion').accordion('close', the_field_index );
				setTimeout(function( the_placeholder ) {
					$(e.currentTarget).closest('.the-field').removeClass('ready-for-drag');
				}, 400);
			})


			// handle checkbox & disbale if not used

			// parse & disable
			$.each( form.find('.td-field-display .checkbox'), function( index, element ) {
				if( false === $( element ).checkbox('is checked') ){
					$(element).closest( '.the-field' ).find('.td-field-required .checkbox').checkbox( 'disable' );
				}
			});

			// bind
			form.find('.td-field-display .checkbox').checkbox({
				onChecked   : function() { $(this).closest( '.the-field' ).find('.td-field-required .checkbox').checkbox( 'enable' );  },
				onUnchecked : function() { $(this).closest( '.the-field' ).find('.td-field-required .checkbox').checkbox( 'disable' ); }
			})



		}

		//insert
		$( node ).html( form )
	}

	function process_form_data( form ){
		var form_data = new FormData( form );
		//add false value ti checkboxes
		$( form ).find( 'input[type=checkbox]' ).each( function(){
			form_data.append( $( this ).attr( 'name' ), ( $( this ).is( ':checked' ) ) ? 'true' : 'false' )
		});
		/*
		form_data.forEach(function( value, key){
			object[ key ] = value;
		});
		var json = JSON.stringify(object);
		*/
		return form_data
	}

	function display_notice( code, message, is_dismissible = false ){
		var notice  = $( '<div class="notice"></div>' );
		var message = $( '<p><strong>'+message+'</strong></p>' );
		var close   = $( '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' );
		notice.append( message )
		notice.addClass( ( 'yetix_settings_save_success' === code ) ? 'notice-success' : 'notice-error' )
		if( is_dismissible ){
			notice.addClass( 'is-dismissible' )
			notice.append( close )
			close.on( 'click', function(){ notice.remove() } )
		}else{
			setTimeout( function(){ notice.remove() }, 5555 )
		}
		$( '#wpbody' ).prepend( notice )
	}

	/** disable_form
	 *  disable all form input and buttons
	 */
	function disable_form( form ){
		$( form ).find( 'button,input,textarea,select' ).prop( 'disabled', true )
	}

	/** enable_form
	 *  enable all form input and buttons
	 */
	function enable_form( form ){
		$( form ).find( 'button,input,textarea,select' ).prop( 'disabled', false )
	}

	/** enable_loading
	 *  display loading box
	 */
	function enable_loading( form ){
		$( form ).addClass( 'loading' )
	}

	/** disable_loading
	 *  hide loading box
	 */
	function disable_loading( form ){
		$( form ).removeClass( 'loading' )
	}

	//jquery triggers
	$( 'form.yetix__development__settings_form' ).on( 'submit', function( e ){
		e.preventDefault()
		save_settings( this )
	} )

	//load status in ajax
	reload_status();

} )( jQuery );