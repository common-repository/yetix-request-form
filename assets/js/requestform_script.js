( function( $ ){
	const { __, _x, _n, _nx, sprintf  } = wp.i18n

	var requestform_timeoutHandles = {}

	/** send_ticket
	 *  use plugin rest route to send ticket to zendesk
	 */
	function send_ticket( form ){
		var block_id  = $( form ).parent().attr( 'id' )
		var form_data = process_form_data( form );
		clear_message( block_id )
		$.ajax( {
			url         : requestform.ticket_api_url,
			beforeSend  : function( xhr ){ xhr.setRequestHeader( 'X-WP-Nonce', requestform[ block_id ].nonce ) },
			method      : 'POST',
			data        : form_data,
			success : function ( response ){
				display_message( block_id, response.code, response.message )
			},
			error   : function ( response ) {
				display_error( block_id, response.responseJSON.code, response.responseJSON.message, response.responseJSON )
			}
		})
	}

	/** send_attachment
	 *  use plugin rest route to send a attachment to zendesk
	 */
	function send_file( form ){
		var block_id     = $( form ).parent().attr( 'id' )
		var form_data    = process_file_data( form )
		var progress_bar = $( '#'+block_id ).find( '.yetix-progress' )
		if( 'undefined' === typeof requestform_timeoutHandles[ block_id ] ){
			requestform_timeoutHandles[ block_id ] = {}
		}
		if( 'undefined' !== typeof requestform_timeoutHandles[ block_id ].progress_bar ){
			window.clearTimeout( requestform_timeoutHandles[ block_id ].progress_bar );
		}
		progress_bar.progress( 'reset' )
		progress_bar.removeClass( 'indeterminate indicating success error' )
		progress_bar.show()
		disable_upload( form )
		$.ajax( {
			url         : requestform.attachment_api_url,
			beforeSend  : function( xhr ){ xhr.setRequestHeader( 'X-WP-Nonce', requestform[ block_id ].nonce ) },
			xhr         : function() {
				var xhr = new window.XMLHttpRequest();
				xhr.upload.addEventListener(
					'progress',
					( e ) => update_upload_progress( block_id, e ),
					false
				)
				return xhr;
			},
			method      : 'POST',
			data        : form_data,
			processData : false,
			contentType : false,
			cache       : false,
			success     : function ( response ){
				update_token_input( block_id, response.attachments );
				display_upload_message( block_id, 'attachment_token', response.code, response.message, response.attachments )
				$( '#'+block_id ).find( '#requestform_attachment_file' ).val( '' )
				$( '#'+block_id ).find( '#requestform_attachment_file' ).trigger( 'change' )
				complete_upload_process( block_id, 'success', __( 'Upload complete', 'yetix-request-form' ) )
				enable_upload( form )
			},
			error       : function ( response ) {
				display_error_field( block_id, 'attachment_token', response.responseJSON.code, response.responseJSON.message );
				$( '#'+block_id ).find( '#requestform_attachment_file' ).val( '' )
				$( '#'+block_id ).find( '#requestform_attachment_file' ).trigger( 'change' )
				complete_upload_process( block_id, 'error', __( 'Upload error', 'yetix-request-form'  ) )
				enable_upload( form )
			}
		} )
	}

	function process_form_data( form ){
		var form_data = {}
		$( form ).find( '.field-wrapper' ).each( function(){
			var name  = $( this ).data( 'name' )
			var input = $( this ).find( '#requestform_'+name ).each( function(){
				var value = get_input_value( this )
				if( value && 0 < value.length ){
					if( 'undefined' === typeof form_data[ name ] ){
						form_data[ name ] = value
					}else if( Array.isArray( form_data[ name ] ) ){
						form_data[ name ].push( value )
					}else{
						form_data[ name ] = [ form_data[ name ] ]
						form_data[ name ].push( value )
					}
				}
			} )
		} )
		return form_data
	}

	function get_input_value( input ){
		var val = null;
		console.log( this );
		if( [ 'select-one','select-multiple', 'hidden' ].includes( input.type ) ){
			return $( input ).val();
		//checkbox, radio
		}else if( [ 'checkbox', 'radio' ].includes( input.type ) ){
			if( input.checked ) return input.value
		//date
		}else if( 0 < $( input ).parent( '.yetix-calendar' ).length ){
			var leading_zero = function ( num, size ) {
				var s = num + ""
				while ( s.length < size ) s = "0" + s
				return s
			}
			var date =  $( input ).parent( '.yetix-calendar' ).calendar( 'get date' )
			if( date ){
				var formated_date = date.getFullYear()+'-'+ leading_zero( date.getMonth()+1, 2 )+'-'+leading_zero( date.getDate(), 2 )
			}else{
				var formated_date = null
			}
			return formated_date
		// text, textarea and password and everything else
		}else if( $( input ).hasClass( 'mce-content-body' ) ){
			return tinyMCE.get( $( input ).attr( 'id' ) ).getContent()
		}else{
			return $.trim( input.value )
		}
		return null
	}

	function process_file_data( form ){
		var form_data = new FormData()
		var files     = $( form ).find( '#requestform_attachment_file' ).prop('files')
		for( const [ key, file ] of Object.entries( files ) ){
			form_data.append("file[]", file )
		}
		form_data.append( "fail", 'safe' )
		return form_data;
	}

	function update_upload_progress( block_id, progress_event ){
		if ( progress_event.lengthComputable) {
			var percent      = progress_event.loaded / progress_event.total * 100;
			var progress_bar = $( '#'+block_id ).find( '.yetix-progress' )
			if( 0 < progress_bar.length ){
				progress_bar.progress( 'set percent', percent )
				if( 100 > percent ){
					progress_bar.addClass('indicating' )
				}else{
					progress_bar.removeClass( 'indicating' )
					progress_bar.addClass('indeterminate' )
					setTimeout( () => progress_bar.progress( 'set bar label', __( 'Processing', 'yetix-request-form' ) ), 500 )

				}
			}
		}
	}

	function complete_upload_process( block_id, status, message ){
		var progress_bar = $( '#'+block_id ).find( '.yetix-progress' )
		if( 0 < progress_bar.length ) {
			progress_bar.removeClass( 'indeterminate indicating' )
			progress_bar.addClass( status )
			progress_bar.progress( 'set bar label', message )
			requestform_timeoutHandles[ block_id ].progress_bar = setTimeout(function(){
				progress_bar.progress( 'reset' )
				progress_bar.hide();
			}, 5000 );
		}
	}

	function update_token_input( block_id, attachments ){
		var tokens = $( '#'+block_id ).find( '#requestform_attachment_token' ).val();
		tokens     = ( 0 === tokens.length ) ? [] : tokens.split( ',' )
		attachments.forEach( function( attachment ){
			if( 'success' === attachment.code ){ tokens.push( attachment.token ) }
		} )
		$( '#'+block_id ).find( '#requestform_attachment_token' ).val( tokens );
	}

	/** display_message
	 *  display result message
	 */
	function display_message( block_id, status_code, message ){
		var global_msg_box = $( '#'+block_id ).find( '.form-message' )
		var form           = $( '#'+block_id ).find( '.form' )
			disable_loding( form )

		global_msg_box.append( '<span class="content">'+message+'</span>' )
		global_msg_box.addClass( 'message success center aligned' )
		global_msg_box.show()

		//if form is hide after response
		if( requestform[ block_id ].hide_form_after_send ){ form.hide() }

		//if response is sent to a another page return here, no need to enable the form
		if( 'redirect' === requestform[ block_id ].return.type ){
			setTimeout( function(){
				var url = new URL( requestform[ block_id ].return.url );
				url.searchParams.set( 'code', status_code )
				url.searchParams.set( 'msg', message )
				window.location.href = url;
			}, requestform[ block_id ].return.timeout )
			return
		}

		//re enable/display form if is not hide or timeout is set to more than 0
		if( ! requestform[ block_id ].hide_form_after_send || 0 < requestform[ block_id ].return.timeout ) {
			setTimeout(function () {
				global_msg_box.hide()
				form.show()
				enable_form(form)
			}, requestform[block_id].return.timeout )
		}
	}

	/** display_message
	 *  display result message
	 */
	function display_upload_message( block_id, field, status_code, message, attachments ){
		var msg_box   = $( '#'+block_id ).find( '.field-wrapper[data-name="'+field+'"] > .field-message' )
		var file_list = $( '#'+block_id ).find( '.field-wrapper[data-name="'+field+'"] > .file-list' )
		msg_box.removeClass( 'error success' )
		msg_box.empty()
		attachments.forEach( function( attachment ){
			var dismiss_btn  = $( '<button class="ui icon button"><i class="times icon"></i></button>' )
			var item         = $( '<div class="item '+attachment.code+'" >'
					+'<div class="inner">'
						+'<span class="content">'+attachment.file_name+'</span>'
					+'</div>'
					+'<div class="message">'+attachment.message+'</div>'
				+'</div>' )
			if( 'success' === attachment.code ){
				item.attr( 'data-token', attachment.token )
				item.find( '.inner' ).append( dismiss_btn )
				dismiss_btn.on( 'click', function(){ remove_attachment( block_id, attachment.token ) } )
				setTimeout( function (){ item.find( '.message' ).remove() }, 10000 )
			}else{
				setTimeout( function (){ item.remove() }, 10000 )
			}
			file_list.append( [ item ] )
		} )
		msg_box.append( '<span class="message">'+message+'</span>' )
		if( 'zendesk_attachment_send' === status_code ){  msg_box.addClass( 'success' ) }
		msg_box.show()
	}

	/** display_error
	 *  display error messages
	 */
	function display_error( block_id, status_code, message, response ){
		var global_msg_box = $( '#'+block_id ).find( '.form-message' )
		var form           = $( '#'+block_id ).find( '.form' )
		disable_loding( form )

		if( 'rest_invalid_param' === status_code ){
			var labels = [];
			for( const [ field_name, field_message ] of Object.entries( response.data.params ) ){
				display_error_field(
					block_id,
					field_name,
					status_code,
					//translators: argument 1 is the field label
					sprintf( __( '%s field is invalid.', 'yetix-request-form' ), requestform.localize.field_labels[ field_name ] )
				)
				labels.push( requestform.localize.field_labels[ field_name ] )
			}
			message = sprintf(
				//translators: argument 1 is the number of invalid field, argument 2 is a line break, argument 3 is a list of all labels
				_n( '%1$s field is invalid.%2$s %3$s.', '%1$s fields are invalid.%2$s %3$s.', labels.length, 'yetix-request-form' ),
				labels.length,
				"<br>",
				labels.join( ', ' )
			)
		}else if( 'rest_missing_callback_param' === status_code ){
			var labels = [];
			for( const [ field_key, field_name ] of Object.entries( response.data.params ) ){
				display_error_field(
					block_id,
					field_name,
					status_code,
					//translators: argument 1 is the field label
					sprintf( __( '%s field is missing.', 'yetix-request-form' ), requestform.localize.field_labels[ field_name ] )
				)
				labels.push( requestform.localize.field_labels[ field_name ] )
			}
			message = sprintf(
				//translators: argument 1 is the number of invalid field, argument 2 is a line break, argument 3 is a list of all labels
				_n( '%1$s field is missing.%2$s %3$s.', '%1$s fields are missing.%2$s %3$s.', labels.length, 'yetix-request-form' ),
				labels.length,
				"<br>",
				labels.join( ', ' )
			)
		}

		global_msg_box.append( '<span class="content">'+message+'</span>' )
		global_msg_box.addClass( 'message error center aligned' )
		global_msg_box.show()

		enable_form( form )
	}

	/** clear_message_field
	 *  clear a specific or all field error message
	 */
	function display_error_field( block_id, field, status_code, message ){
		var msg_box = $( '#'+block_id ).find( '.field-wrapper[data-name="'+field+'"] > .field-message' )
		msg_box.removeClass( 'error success' )
		msg_box.empty()
		msg_box.append( '<span class="error">'+message+'</span>' )
		msg_box.addClass( 'message error' )
		msg_box.show()
	}

	/** clear_message
	 *  clear all success or error message of the form
	 */
	function clear_message( block_id ){
		var msg_box = $( '#'+block_id ).find( '.message' )
		msg_box.empty()
		msg_box.removeClass( 'error success' )
		msg_box.hide()
		clear_message_field( block_id )
	}

	/** clear_message_field
	 *  clear a specific or all field error message
	 */
	function clear_message_field( block_id, field = null ){
		var msg_box = ( field )
			? $( '#'+block_id ).find( '.field-wrapper[data-name="'+field+'"] > .field-message' )
			: $( '#'+block_id ).find( '.field-wrapper > .field-message' )
		msg_box.empty()
		msg_box.removeClass( 'error success' )
		msg_box.hide()
	}

	function remove_attachment( block_id, token ){
		var tokens = $( '#'+block_id ).find( '#requestform_attachment_token' ).val();
		tokens     = ( 0 === tokens.length ) ? [] : tokens.split( ',' )
		tokens     = tokens.filter( function( e ){ return e !== token } )
		$( '#'+block_id ).find( '#requestform_attachment_token' ).val( tokens );
		$( '#'+block_id ).find( '[data-token="'+ token+'"]' ).remove()
		clear_message_field( block_id, 'attachment_token' );
	}

	/** disable_form
	 *  disable all form input and buttons
	 */
	function disable_form( form ){
		form.find( 'button,input,textarea,select' ).prop( 'disabled', true )
		form.find( '.yetix-dropdown' ).addClass( 'disabled' );
		disable_upload( form )
	}

	/** enable_form
	 *  enable all form input and buttons
	 */
	function enable_form( form ){
		form.find( 'button,input,textarea,select' ).prop( 'disabled', false )
		form.find( '.yetix-dropdown' ).removeClass( 'disabled' );
		enable_upload( form )
	}

	/** disable_upload
	 *  disable upload input
	 */
	function disable_upload( form ){
		form.find( '#requestform_attachment_file' ).prop( 'disabled', true )
		form.find( '#requestform_attachment_upload' ).prop( 'disabled', false )
		form.find( '#requestform_attachment_upload' ).addClass( 'disabled' )
	}

	/** enable_upload
	 *  enable upload input
	 */
	function enable_upload( form ){
		form.find( '#requestform_attachment_file' ).prop( 'disabled', false )
		form.find( '#requestform_attachment_upload' ).prop( 'disabled', false )
		form.find( '#requestform_attachment_upload' ).removeClass( 'disabled' )
	}

	/** enable_loading
	 *  display loading box
	 */
	function enable_loading( form ){
		form.siblings( '.loading' ).show()
	}

	/** disable_loading
	 *  hide loading box
	 */
	function disable_loding( form ){
		form.siblings( '.loading' ).hide()
	}

	function format_string( format ) {
		var args = Array.prototype.slice.call( arguments, 2 );
		return format.replace( /{(\d+)}/g, function( match, number ) {
			return typeof ( args[ number ] != 'undefined' ) ? args[ number ] : match
		} );
	};

	//---fields
	$('.yetix-progress').progress()
	$('.yetix-progress').hide()
	$('.yetix-checkbox').checkbox()
	$('.yetix-dropdown').dropdown()
	$('.yetix-calendar').calendar( {
		type : 'date',
		text : {
			days        : [
				_x( 'S', 'first letter of Sunday', 'yetix-request-form' ),
				_x( 'M', 'first letter of Monday', 'yetix-request-form' ),
				_x( 'T', 'first letter of Tuesday', 'yetix-request-form' ),
				_x( 'W', 'first letter of Wednesday', 'yetix-request-form' ),
				_x( 'T', 'first letter of Thursday', 'yetix-request-form' ),
				_x( 'F', 'first letter of Friday', 'yetix-request-form' ),
				_x( 'S', 'first letter of Saturday', 'yetix-request-form' )
			],
			months      : [
				__( 'January', 'yetix-request-form' ),
				__( 'February', 'yetix-request-form' ),
				__( 'March', 'yetix-request-form' ),
				__( 'April', 'yetix-request-form' ),
				__( 'May', 'yetix-request-form' ),
				__( 'June', 'yetix-request-form' ),
				__( 'July', 'yetix-request-form' ),
				__( 'August', 'yetix-request-form' ),
				__( 'September', 'yetix-request-form' ),
				__( 'October', 'yetix-request-form' ),
				__( 'November', 'yetix-request-form' ),
				__( 'December', 'yetix-request-form' )
			],
			monthsShort : [
				_x( 'Jan', 'short for January', 'yetix-request-form' ),
				_x( 'Feb', 'short for February', 'yetix-request-form' ),
				_x( 'Mar', 'short for March', 'yetix-request-form' ),
				_x( 'Apr', 'short for April', 'yetix-request-form' ),
				_x( 'May', 'short for May', 'yetix-request-form' ),
				_x( 'Jun', 'short for June', 'yetix-request-form' ),
				_x( 'Jul', 'short for July', 'yetix-request-form' ),
				_x( 'Aug', 'short for August', 'yetix-request-form' ),
				_x( 'Sep', 'short for September', 'yetix-request-form' ),
				_x( 'Oct', 'short for October', 'yetix-request-form' ),
				_x( 'Nov', 'short for November', 'yetix-request-form' ),
				_x( 'Dec', 'short for December', 'yetix-request-form' )
			],
			today       : __( 'Today', 'yetix-request-form' ),
			now         : __( 'Now', 'yetix-request-form' ),
			am          : _x( 'AM', 'short for ante meridiem', 'yetix-request-form' ),
			pm          : _x( 'PM', 'short for post meridiem', 'yetix-request-form' )
		}
	} );

	//---triggers
	//validate, submit, upload attachment
	$( 'button.yetix-requestform-attachment-upload' ).on( 'click', function( e ){
		e.preventDefault()
		send_file( $( this ).parents( 'form' ) );
	} );

	$( 'form.yetix-requestform' ).on( 'submit', function( e ){
		e.preventDefault()
		enable_loading( $( this ) )
		disable_form( $( this ) )
		send_ticket( $( this ) )
	} )

	$( '.yetix-requestform-readonly' ).on( 'keypress', function( e ){
		e.preventDefault()
		if ( 13 === e.which ) {
			$( this ).siblings( '#requestform_attachment_file' ).trigger( 'click' )
		}
	} )

	//clear error on change
	$( 'input, textarea, select' ).on( 'click', function(){
		var block = $( this ).parents( '.wp-block-yetix-requestform' )
		if( block.length ){
			clear_message_field( block.attr( 'id' ), $( this ).parents( '.field-wrapper' ).data( 'name' ) )
		}
	} )

	//attachment input trickery
	$( 'input:text' ).click( function(){
		$( this ).siblings( 'input:file' ).click()
	});
	$( 'input:file', '.ui.action.input' ).on( 'change', function( e ) {
		var name = __( 'No files selected.', 'yetix-request-form' )
		if( 0 < this.files.length ){
			//translators: argument 1 is the number of file selected
			name = sprintf( _n( '%s file selected.', '%s files selected.', this.files.length, 'yetix-request-form' ), this.files.length )
		}
		$( this ).siblings( 'input:text' ).val( name )
	} )

	//tinymce init description textarea
	tinymce.init( {
		selector             : '#requestform_description',
		plugins              : 'hr lists',
		inline               : true,
		toolbar              : 'bold italic underline | bullist numlist | blockquote code pre hr | undo redo ',
		menubar              : false,
		statusbar            : true,
		setup: function( editor ) {
			editor.addButton( 'code', {
				icon         : 'code',
				onclick      : function(){ editor.execCommand('mceToggleFormat', false, 'code'); },
				onpostrender : function(){ var btn = this;
					editor.on( 'init', function(){
						editor.formatter.formatChanged( 'code', function( state ){ btn.active( state ); } );
					} );
				}
			});
			editor.addButton( 'pre', {
				icon         : 'codesample',
				onclick      : function(){ editor.execCommand('mceToggleFormat', false, 'pre'); },
				onpostrender : function(){ var btn = this;
					editor.on( 'init', function(){
						editor.formatter.formatChanged( 'pre', function( state ){ btn.active( state ); } );
					} );
				}
			} );
			editor.addButton( 'blockquote', {
				icon         : 'blockquote',
				onclick      : function(){ editor.execCommand('mceToggleFormat', false, 'blockquote'); },
				onpostrender : function(){ var btn = this;
					editor.on( 'init', function(){
						editor.formatter.formatChanged( 'blockquote', function( state ){ btn.active( state ); } );
					} );
				}
			} );
		}
	} );

} )( jQuery )