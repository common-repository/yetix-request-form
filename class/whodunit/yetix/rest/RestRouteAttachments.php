<?php
//<namespace::begin>
namespace whodunit\yetix\rest;
//<namespace::end>

//<use::begin>
use whodunit\utility\Tracker;
use whodunit\yetix\Plugin;
//<use::end>

/**
 * RestRouteAttachments class
 * handle write zendesk attachments
 */
class RestRouteAttachments extends RestRoute{

	protected $name_space;
	protected $route;
	protected $params;
	protected $options;

	/**
	 * Constructor
	 * final object need no arguments
	 * - define params use by this route
	 * - register route
	 */
	function __construct(){
		$this->options = Plugin::getInstance()->get_options( 'zendesk_attachment_field' );
		$params        = [];

		parent::__construct(
			'yetix/v1',
			'attachments',
			$params
		);

		$this->set_routes(
			\WP_REST_Server::CREATABLE,
			[ $this, 'endpoint_controller_create_attachment' ],
			[], //FILE parameter can't be validated by a callback, validation will be handle in endpoint_controller_create_attachment
			function( $request ){
				$nonce = $request->get_header('X-WP-Nonce');
				return ( 1 === wp_verify_nonce( $nonce, 'wp_rest' ) );
			}
		);
	}

	/**
	 * validate_file_type
	 * Validate file type of uploaded attachments
	 * @param string $file_name client request
	 * @return boolean return true if the file type is in plugin options
	 */
	protected function validate_file_type( $file_name ){
		$allowed_ext = explode( ', ', $this->options[ 'allowed_ext' ] );
		return ( ! in_array ( pathinfo( $file_name, PATHINFO_EXTENSION ), $allowed_ext ) );
	}

	/**
	 * validate_file_size
	 * Validate file size of uploaded attachments
	 * @param integer $file_size file size in byte
	 * @return boolean return true if the file size is inferior to plugin options and server max upload size
	 */
	protected function validate_file_size( $file_size ){
		$server_max_size = wp_max_upload_size();
		$max_size        = ( 0 >= $this->options[ 'max_size' ] || $this->options[ 'max_size' ] > $server_max_size )
			? $server_max_size : $this->options[ 'max_size' ];
		return ( $max_size < $file_size );
	}

	/**
	 * endpoint_controller_create_attachment
	 * Handle attachments route
	 * create attachment from multiple uploaded files
	 * @param \Requests $request client request
	 */
	public function endpoint_controller_create_attachment( $request ){
		$file_params = $request->get_file_params();
		$params      = $request->get_params();
		$client      = Tracker::get_zendesk_administrator_client();

		//check if the server have dropped the upload
		if( $_SERVER['CONTENT_LENGTH'] && empty( $_FILES ) && empty( $_POST ) ){
			$message = __( 'Your documents exceed the size limit. Try to upload one file at the time', 'yetix-request-form' );
			return new \WP_Error( 'yetix_attachment_error', $message, [ 'params' => $file_params ] );
		}

		//check file exist
		if( ! isset( $file_params[ 'file' ] ) && empty( $file_params[ 'file' ] ) ){
			$message = __( 'No document specified.', 'yetix-request-form' );
			return new \WP_Error( 'rest_missing_callback_param', $message, [ 'params' => $file_params, ] );
		}

		//test connection
		if( 1 !== $client->get_status() ){
			$message = __( 'Cannot connect to zendesk, please contact an administrator.', 'yetix-request-form' );
			return new \WP_Error( 'zendesk_api_error', $message, [ 'params' => $file_params ] );
		}

		try {
			//Upload
			$files_response = [];
			for( $i = 0; $i < sizeof( $file_params[ 'file' ][ 'error' ] ); $i++ ){
				if( 0 === $file_params[ 'file' ][ 'error' ][ $i ] ) {
					if( $this->validate_file_type( $file_params[ 'file' ][ 'name' ][ $i ] ) ){
						$message = __( 'This document type is not allowed.', 'yetix-request-form' );
					}elseif( $this->validate_file_size( $file_params[ 'file' ][ 'size' ][ $i ] ) ){
						$message = __( 'This document exceed the size limit.', 'yetix-request-form' );
					}else{
						$attachment = $client->attachments()->upload( [
							'file' => $file_params[ 'file' ][ 'tmp_name' ][ $i ],
							'type' => $file_params[ 'file' ][ 'type' ][ $i ],
							'name' => $file_params[ 'file' ][ 'name' ][ $i ]
						] );
						if( $attachment->upload ){
							$files_response[ $i ] = [
								'code'       => 'success',
								'message'    => _x( 'This document have been uploaded.', 'upload_success', 'yetix-request-form' ),
								'token'      => $attachment->upload->token,
								'expires_at' => $attachment->upload->expires_at,
								'file_name'  => $attachment->upload->attachment->file_name
							];
							continue;
						}
					}
				}else{
					$errors = [
						UPLOAD_ERR_INI_SIZE   => _x( 'This document exceed the server upload limit.', 'upload_error_ini_size', 'yetix-request-form' ),
						UPLOAD_ERR_FORM_SIZE  => _x( 'This document exceed the form upload limit.', 'upload_error_form_size', 'yetix-request-form' ),
						UPLOAD_ERR_PARTIAL    => _x( 'This document have been partially upload.', 'upload_error_err_partial', 'yetix-request-form' ),
						UPLOAD_ERR_NO_FILE    => _x( 'There is no document to upload.', 'upload_error_no_file', 'yetix-request-form' ),
						UPLOAD_ERR_NO_TMP_DIR => _x( 'This document cant be uploaded due to a server error.', 'upload_error_no_tmp_dir', 'yetix-request-form' ),
						UPLOAD_ERR_CANT_WRITE => _x( 'This document cant be uploaded due to a server error.', 'upload_error_cant_write', 'yetix-request-form' ),
						UPLOAD_ERR_EXTENSION  => _x( 'This document cant be uploaded due to a server error.', 'upload_error_extention', 'yetix-request-form' ),
					];
					$message = ( isset( $errors[ $file_params[ 'file' ][ $i ][ 'error' ] ] ) )
						? $errors[ $file_params[ 'file' ][ $i ][ 'error' ] ]
						: _x( 'This document cant be uploaded due to a unknown error.', 'upload_error_unknown', 'yetix-request-form' );
				}
				$files_response[ $i ] = [
					'code'      => 'error',
					'message'   => $message,
					'file_name' => $file_params[ 'file' ][ 'name' ][ $i ]
				];
			}

		} catch (\Zendesk\API\Exceptions\ApiResponseException $e) {
			//translators: argument 1 is an error code from zendesk
			$message = sprintf( __( 'An unknown error has occurred, please contact a administrator with this error code %s.', 'yetix-request-form' ) , $e->getCode() );
			return new \WP_Error(
				'zendesk_api_error',
				$message,
				[
					'params'    => $file_params,
					'exception' => [
						'code'  => $e->getCode(),
						'msg'   => $e->getMessage(),
						'trace' => $e->getTrace(),
					]
				]
			);
		}

		//success response
		return new \WP_REST_Response( [
			'code'        => 'zendesk_attachment_send',
			'message'     => __( 'Attachment uploaded', 'yetix-request-form' ),
			'attachments' => $files_response,
		] );


	}

}