<?php
//<namespace::begin>
namespace whodunit\yetix\rest;
//<namespace::end>

//<use::begin>
use whodunit\utility\Tracker;
use whodunit\yetix\Plugin;
//<use::end>

/**
 * RestRouteTicketFields class
 * handle zendesk ticket field read wp rest api route
 */
class RestRouteTicketFields extends RestRoute{

	protected $name_space;
	protected $route;
	protected $params;

	/**
	 * Constructor
	 * final object need no arguments
	 * - define params use by this route
	 * - register route
	 */
	function __construct(){
		parent::__construct(
			'yetix/v1',
			'ticket_fields',
			[]
		);
		$this->set_routes(
			\WP_REST_Server::READABLE,
			[ $this, 'endpoint_controller_get_ticket_fields' ],
			[],
			function( $request ){
				if( ! is_user_logged_in() ){ return false; }
				$nonce = $request->get_header('X-WP-Nonce');
				return ( 1 === wp_verify_nonce( $nonce, 'wp_rest' ) );
			}
		);
		$this->set_routes(
			\WP_REST_Server::READABLE,
			[ $this, 'endpoint_controller_get_custom_ticket_fields' ],
			[],
			function( $request ){
				if( ! is_user_logged_in() ){ return false; }
				$nonce = $request->get_header('X-WP-Nonce');
				return ( 1 === wp_verify_nonce( $nonce, 'wp_rest' ) );
			},
			'custom_fields'
		);
	}

	/**
	 * endpoint_controller_get_ticket_fields
	 * GET ticket_fields route controller
	 * @param \Requests $request client request
	 */
	public function endpoint_controller_get_ticket_fields( $request ){
		$params = $request->get_params();
		$client = Tracker::get_zendesk_administrator_client();

		//test connection
		if( 1 !== $client->get_status() ){
			$message = __( 'Cannot connect to zendesk, please contact an administrator.', 'yetix-request-form' );
			return new \WP_Error( 'zendesk_api_error', $message, [ 'params' => $params ] );
		}

		//try getting field
		try{
			$response = $client->ticketFields()->findAll();
		}catch( \Exception $e ){
			//zendesk return a error
			$message = sprintf( __( 'An unknown error has occurred, please contact a administrator with this error code %s.', 'yetix-request-form' ) );
			return new \WP_Error(
				'zendesk_api_error',
				$message,
				[
					'params'    => $params,
					'exception' => [
						'code'  => $e->getCode(),
						'msg'   => $e->getMessage(),
					]
				]
			);
		}

		//success response
		//translators: argument 1 is the number of field return by zendesk
		$message = sprintf( __( '%s ticket fields find.', 'yetix-request-form' ) , $response->count );

		foreach( $response->ticket_fields as $field ){ $field->is_system = Tracker::is_sytem_field( $field->type ); }

		return new \WP_REST_Response( [
			'code'                 => 'zendesk_ticket_fields_load',
			'message'              => $message,
			'fields'               => $response->ticket_fields,
			'options'              => [
				'name_field'       => Plugin::getInstance()->get_options( 'zendesk_name_field' ),
				'email_field'      => Plugin::getInstance()->get_options( 'zendesk_email_field' ),
				'attachment_field' => Plugin::getInstance()->get_options( 'zendesk_attachment_field' ),
				'custom_fields'    => Plugin::getInstance()->get_options( 'zendesk_fields' ),
			],
		] );
	}

	/**
	 * endpoint_controller_get_custom_ticket_fields
	 * GET custom ticket fields route controller
	 * @param \Requests $request client request
	 */
	public function endpoint_controller_get_custom_ticket_fields( $request ){
		$params = $request->get_params();
		$client = Tracker::get_zendesk_administrator_client();

		//test connection
		if( 1 !== $client->get_status() ){
			$message = __( 'Cannot connect to zendesk, please contact an administrator.', 'yetix-request-form' );
			return new \WP_Error( 'zendesk_api_error', $message, [ 'params' => $params ] );
		}

		//try sending ticket
		try{
			$response = $client->ticketFields()->findAll();
		}catch( \Exception $e ){
			//zendesk return a error
			$message = sprintf( __( 'An unknown error has occurred, please contact a administrator with this error code %s.', 'yetix-request-form' ) );
			return new \WP_Error(
				'zendesk_api_error',
				$message,
				[
					'params'    => $params,
					'exception' => [
						'code'  => $e->getCode(),
						'msg'   => $e->getMessage(),
					]
				]
			);
		}

		$fields = $response->ticket_fields;
		$fields = array_filter( $fields, function ( $tf ) { return ! Tracker::is_sytem_field( $tf->type ); } );

		//success response
		//translators: argument 1 is the number of field return by zendesk
		$message = sprintf( __( '%s custom ticket fields find.', 'yetix-request-form' ) , sizeof( $fields ) );

		return new \WP_REST_Response( [
			'code'                 => 'zendesk_ticket_custom_fields_loaded',
			'message'              => $message,
			'fields'               => array_values( $fields ),
		] );
	}

}