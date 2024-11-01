<?php
//<namespace::begin>
namespace whodunit\yetix\rest;
//<namespace::end>

//<use::begin>
use whodunit\utility\WordPress;
use whodunit\yetix\Plugin;
//<use::end>

/**
 * RestRouteSettings class
 * handle whodunit read and write plugin option wp rest api route
 */
class RestRouteSettings extends RestRoute{

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
		$params = [
			'options' => [
				'required' => true,
				'validate' => function( $v, $r, $k ){ return is_array( $v ); },
				'sanitize' => function( $v, $r, $k ){ return WordPress::recursive_sanitize_text_field( $v ); },
			],
		];
		parent::__construct(
			'yetix/v1',
			'settings',
			$params
		);
		$this->set_routes(
			\WP_REST_Server::READABLE,
			[ $this, 'endpoint_controller_get_plugin_option' ],
			[ 'options' ],
			function(){ return current_user_can( 'yetix_request__manage_plugin_options' ); }
		);
		$this->set_routes(
			\WP_REST_Server::CREATABLE,
			[ $this, 'endpoint_controller_set_plugin_option' ],
			[ 'options' ],
			function(){ return current_user_can( 'yetix_request__manage_plugin_options' ); }
		);
	}

	/**
	 * endpoint_controller_get_plugin_option
	 * GET options route controller
	 * @param \Requests $request client request
	 */
	public function endpoint_controller_get_plugin_option( $request ){
		//Plugin::getInstance()->get_options();
		return new \WP_REST_Response( [
			'code'    => 'yetix_options_load',
			'message' => __( 'yetix options load', 'yetix-request-form' ),
		] );
	}

	/**
	 * endpoint_controller_set_plugin_option
	 * Post options route controller
	 * @param \Requests $request client request
	 */
	public function endpoint_controller_set_plugin_option( $request ){
		//TODO:: detect if option has been changed, wp dont do it and return a false
		//so before saving the new params, get currently loaded option and check it against the new one
		//this need to support multidimensional array and ignore missing key from the new param ( value are parsed )
		//this need to be done here or save_options return type need to change from boolean to int
		$params = $request->get_param( 'options' );
		$saved  = Plugin::getInstance()->save_options( $params );
		if( 1 === $saved ) {
			return new \WP_REST_Response([
				'code' => 'yetix_settings_save_success',
				'message' => __( 'yetix options saved', 'yetix-request-form'),
			]);
		}elseif( 2 === $saved ){
			return new \WP_Error(
				'yetix_settings_save_error',
				__( 'yetix options have not change', 'yetix-request-form')
			);
		}
		return new \WP_Error(
			'yetix_settings_save_error',
			__( 'yetix options cant save', 'yetix-request-form')
		);
	}

}