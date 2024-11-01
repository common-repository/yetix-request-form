<?php
//<namespace::begin>
namespace whodunit\yetix\rest;
//<namespace::end>

//<use::begin>
use whodunit\utility\Tracker;
//<use::end>

/**
 * RestRouteTracker class
 * handle utility wp rest api route
 */
class RestRouteTracker extends RestRoute{

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
			'tracker',
			[]
		);
		$this->set_routes(
			\WP_REST_Server::READABLE,
			[ $this, 'endpoint_controller_get_tracker_token_status' ],
			[],
			function(){ return current_user_can( 'yetix_request__manage_plugin_options' ); },
			'check_token_connection'
		);
	}

	/**
	 * endpoint_controller_get_tracker_token_status
	 * GET tracker/check_token_connection route controller
	 * @param \Requests $request client request
	 */
	public function endpoint_controller_get_tracker_token_status( $request ){
        $connection = Tracker::check_zendesk_token_api_connection();
        return new \WP_REST_Response( [
            'code'    => $connection[ 'code' ],
            'message' => $connection[ 'message' ],
        ] );
	}

}