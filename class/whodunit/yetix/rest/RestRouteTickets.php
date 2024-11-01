<?php
//<namespace::begin>
namespace whodunit\yetix\rest;
//<namespace::end>

//<use::begin>
use whodunit\utility\WordPress;
use whodunit\yetix\model\ModelTicket;
use whodunit\yetix\model\ModelUser;
use whodunit\yetix\model\ModelView;
use whodunit\yetix\Plugin;
use whodunit\utility\Tracker;
//<use::end>

/**
 * RestRouteTickets class
 * handle ticket read/write wp rest api route
 */
class RestRouteTickets extends RestRoute{

	protected $name_space;
	protected $route;
	protected $params;

	/**
	 * Constructor
	 * final object need no arguments
	 * - define params use by this route
	 * 		some are created dynamically with zendesk custom field and zendesk parameters
	 * - register route
	 */
	function __construct(){
		$connection               = Tracker::check_zendesk_token_api_connection();
		$attachment_field_options = Plugin::getInstance()->get_options( 'zendesk_attachment_field' );
		$optional_field_options   = Plugin::getInstance()->get_options( 'zendesk_fields' );
		$field_params             = [];
		$params                   = [
			'name' => [
				'required' => ( Tracker::get_current_zendesk_user() ) ? false : true,
				'validate' => function( $v, $r, $k ){ return ! empty( $v ); },
				'sanitize' => function( $v, $r, $k ){ return sanitize_text_field( $v ); },
			],
			'email' => [
				'required' => ( Tracker::get_current_zendesk_user() ) ? false : true,
				'validate' => function( $v, $r, $k ){ return is_email( $v ); },
				'sanitize' => function( $v, $r, $k ){ return sanitize_text_field( $v ); },
			],
			'subject' => [
				'required' => true,
				'validate' => function( $v, $r, $k ){ return ! empty( $v ); },
				'sanitize' => function( $v, $r, $k ){ return sanitize_text_field( $v ); },
			],
			'description' => [
				'required' => true,
				'validate' => function( $v, $r, $k ){ return ! empty( $v ); },
				'sanitize' => function( $v, $r, $k ){ return wp_kses( $v, [
					'a'          => [ 'href' => true, 'title' => true ],
					'p'          => [],
					'br'         => [],
					'hr'         => [],
					'pre'        => [],
					'blockquote' => [],
					'code'       => [],
					'strong'     => [],
					'em'         => [],
					'span'       => [ 'style' => true ],
					'ul'         => [],
					'ol'         => [],
					'li'         => [],
					'img'        => [ 'src' => true, 'alt' => true, 'height' => true, 'width' => true ],
				] ); },
			],
			'attachment_token' => [
				'required' => ( 'true' === $attachment_field_options[ 'display' ] ) ? ( 'true' === $attachment_field_options[ 'required' ] ) : false,
				'validate' => function( $v, $r, $k ){ return ! empty( $v ); },
				'sanitize' => function( $v, $r, $k ){ return sanitize_text_field( $v ); },
			],
			'view' => [
				'required' => false,
				//'validate' => function( $v, $r, $k ){ return true; },
				'sanitize' => function( $v, $r, $k ){ return ( is_numeric( $v ) && 0 < $v ) ? ( int ) $v : null; },
			],
			'ticket-status' => [
				'required' => false,
				'validate' => function( $v, $r, $k ){ return in_array( $v, [ 'all', 'new', 'open', 'pending', 'hold', 'solved', 'closed' ] ); },
				'sanitize' => function( $v, $r, $k ){ return ( 'all' === $v ) ? null : sanitize_text_field( $v ) ; },
			],
			'ticket-type' => [
				'required' => false,
				'validate' => function( $v, $r, $k ){ return in_array( $v, [ 'all', 'question', 'incident', 'problem', 'task' ] ); },
				'sanitize' => function( $v, $r, $k ){ return ( 'all' === $v ) ? null : sanitize_text_field( $v ) ; },
			],
			'ticket-priority' => [
				'required' => false,
				'validate' => function( $v, $r, $k ){ return in_array( $v, [ 'all', 'low', 'normal', 'high', 'urgent' ] ); },
				'sanitize' => function( $v, $r, $k ){ return ( 'all' === $v ) ? null : sanitize_text_field( $v ) ; },
			],
			'ticket-customfield' => [
				'required' => false,
				'validate' => function( $v, $r, $k ){ return is_array( $v ); }, //todo validate custom field ?
				'sanitize' => function( $v, $r, $k ){ return ( 'all' === $v ) ? null : $v ; }, //todo sanitize custom field !!
			],
			'ticket-search' => [
				'required' => false,
				'validate' => function( $v, $r, $k ){ return ! empty( $v ); },
				'sanitize' => function( $v, $r, $k ){ return '"'.sanitize_text_field( $v ).'"' ; },
			],
			'ticket-created-by-me' => [
				'required' => false,
				'validate' => function( $v, $r, $k ){ return in_array( $v, [ 'true', 'false' ] ); },
				'sanitize' => function( $v, $r, $k ){ return ( 'true' === $v ) ? true : false; },
			],
			'ticket-limit' => [
				'required' => false,
				'validate' => function( $v, $r, $k ){ return is_numeric( $v ) && 0 < $v && 100 >= $v; }, //100 is the max per_page for a zendesk search
				'sanitize' => function( $v, $r, $k ){ return ( int ) $v; },
			],
			'ticket-page' => [
				'required' => false,
				'validate' => function( $v, $r, $k ){ return is_numeric( $v ) && 0 < $v; }, //100 is the max per_page for a zendesk search
				'sanitize' => function( $v, $r, $k ){ return ( int ) $v; },
			],
			'comment-ticket' => [
				'required' => true,
				'validate' => function( $v, $r, $k ){ return is_numeric( $v ); },
				'sanitize' => function( $v, $r, $k ){ return ( int ) $v; },
			],
			'comment-body' => [
				'required' => true,
				'validate' => function( $v, $r, $k ){ return true; },
				'sanitize' => function( $v, $r, $k ){ return wp_kses( $v, [
					'a'          => [ 'href' => true, 'title' => true ],
					'p'          => [],
					'br'         => [],
					'hr'         => [],
					'pre'        => [],
					'blockquote' => [],
					'code'       => [],
					'strong'     => [],
					'em'         => [],
					'span'       => [ 'style' => true ],
					'ul'         => [],
					'ol'         => [],
					'li'         => [],
					'img'        => [ 'src' => true, 'alt' => true, 'height' => true, 'width' => true ],
				] );
			},
			],
			'comment-status' => [
				'required' => true,
				'validate' => function( $v, $r, $k ){ return in_array( $v, [ 'open', 'solved' ] ); },
				'sanitize' => function( $v, $r, $k ){ return sanitize_text_field( $v ) ; },
			],
		];

		//TODO::this need to be deprecated
		if( ! empty( $optional_field_options ) && 'success' === $connection[ 'code' ] ){
			foreach( $optional_field_options as $field_id => $field_options ){
				if( isset( $field_options[ 'display' ] ) && 'true' === $field_options[ 'display' ] ){
					$field_data            = Tracker::get_ticket_fields( $field_id );
					if( in_array( $field_data->type, [ 'subject','description' ] ) ){ continue; }
					$hold_multiple_value   = ( in_array( $field_data->type, [ 'multiselect' ] ) ) ? true : false;
					//TODO::redundant with Helpers.php l88
					$field_name           = ( is_null( $field_id ) || in_array( $field_data->type, [ 'subject', 'description', 'tickettype', 'priority', 'status' ] ) )
						? $field_data->type : sanitize_title( $field_data->title ).'_'.$field_data->id;
					$pattern               = ( isset( $field_data->regexp_for_validation ) ) ? $field_data->regexp_for_validation : null;
					$field_params[ $field_name ] = [
						'required' => ( 'true' === $field_options[ 'required' ] ),
						'validate' => ( is_null( $pattern ) ) //TODO::validate 'tickettype', 'priority', 'status' properly too
							? function( $v, $r, $k ){ return ! empty( $v ); }
							: function( $v, $r, $k )use( $pattern ){
								$match = @preg_match( '/'.$pattern.'/', $v );
								if( false === $match ){
									//TODO::preg_match return false on failure
									//we need to find a way to disable field with invalid regex pattern
								}
								return ( boolean ) $match;
							},
						'sanitize' => ( $hold_multiple_value )
							? function( $v, $r, $k ){ return WordPress::recursive_sanitize_text_field( $v ); }
							: function( $v, $r, $k ){ return WordPress::recursive_sanitize_text_field( $v ); },
					];
				}
			}
		}

		$api_fields_data     = Tracker::get_custom_ticket_fields();
		$custom_field_params = [];
		foreach( $api_fields_data as $custom_field ){
			if( $custom_field->editable_in_portal ){
				$hold_multiple_value   = ( in_array( $field_data->type, [ 'multiselect' ] ) ) ? true : false;
				$field_name            = sanitize_title( $field_data->title ).'_'.$field_data->id;
				$pattern               = ( isset( $field_data->regexp_for_validation ) ) ? $field_data->regexp_for_validation : null;
				$custom_field_params[ $field_name ] = [
					'required' => $custom_field->required_in_portal,
					'validate' => ( is_null( $pattern ) )
						? function( $v, $r, $k ){ return ! empty( $v ); }
						: function( $v, $r, $k )use( $pattern ){
							$match = @preg_match( '/'.$pattern.'/', $v );
							return ( boolean ) $match;
						},
					'sanitize' => ( $hold_multiple_value )
						? function( $v, $r, $k ){ return WordPress::recursive_sanitize_text_field( $v ); }
						: function( $v, $r, $k ){ return WordPress::recursive_sanitize_text_field( $v ); },
				];
			}
		}

		if( 'success' === $connection[ 'code' ] ){
			parent::__construct( 'yetix/v1', 'tickets', array_merge( $params, $field_params, $custom_field_params ) );

			if( ! empty( $optional_field_options ) ){
				$this->set_routes(
					\WP_REST_Server::CREATABLE,
					[ $this, 'endpoint_controller_post_ticket' ],
					array_merge( [ 'name', 'email', 'subject', 'description', 'attachment_token' ], array_keys( $field_params ) ),
					function( $request ){
						$nonce = $request->get_header( 'X-WP-Nonce' );
						return ( 1 === wp_verify_nonce( $nonce, 'wp_rest' ) );
					}
				);
			}

			$this->set_routes(
				\WP_REST_Server::READABLE,
				[ $this, 'endpoint_controller_gets_tickets' ],
				[ 'view', 'ticket-page', 'ticket-created-by-me', 'ticket-followed', 'ticket-limit', 'ticket-search', 'ticket-status', 'ticket-type', 'ticket-priority', 'ticket-customfield' ],
				function( $request ){
					if( ! is_user_logged_in() ){ return false; }
					$nonce = $request->get_header( 'X-WP-Nonce' );
					return ( 1 === wp_verify_nonce( $nonce, 'wp_rest' ) );
				}
			);
			$this->set_routes(
				\WP_REST_Server::CREATABLE,
				[ $this, 'endpoint_controller_post_ticket_comment' ],
				array_merge( [ 'comment-ticket', 'comment-status', 'comment-body', 'attachment_token' ], array_keys( $custom_field_params ) ),
				function( $request ){
					if( ! is_user_logged_in() ){ return false; }
					$nonce = $request->get_header( 'X-WP-Nonce' );
					return ( 1 === wp_verify_nonce( $nonce, 'wp_rest' ) );
				},
				'comment'
			);

		}
	}

	/**
	 * endpoint_controller_gets_tickets
	 * GET tickets route controller
	 * @param \Requests $request client request
	 */
	public function endpoint_controller_gets_tickets( $request ){
		$params          = $request->get_params();
		$wp_user_id      = get_current_user_id();
		$zendesk_user_id = get_user_meta( $wp_user_id, 'yetix_request__zendesk_user_id', true );
		$view            = null;
		if( ! $zendesk_user_id ){
			return new \WP_Error( 'yetix_error', __('Your account is not link a any zendesk account', 'yetix-request-form') );
		}
		$admin_client    = Tracker::get_zendesk_administrator_client();

		if( 1 !== $admin_client->get_status() ){
			return new \WP_Error( 'zendesk_api_error', __( 'Cannot connect to zendesk, please contact an administrator.', 'yetix-request-form' ) );
		}

		$zendesk_user   = Tracker::get_current_zendesk_user();
		if( ! $zendesk_user->is_active() ){
			return new \WP_Error( 'zendesk_api_error', __( 'Your link to a invalid zendesk user.', 'yetix-request-form' ) );
		}
		$query = [ 'type:ticket' ];
		if( isset( $params[ 'ticket-created-by-me' ] ) && $params[ 'ticket-created-by-me' ] ){ $query[] = 'requester:'.$zendesk_user->get_id(); }
		if( isset( $params[ 'ticket-followed' ] ) && $params[ 'ticket-followed' ] ){ $query[] = 'cc:'.$zendesk_user->get_id(); }
		if( isset( $params[ 'ticket-search' ] ) ){ $query[] = $params[ 'ticket-search' ]; }
		if( isset( $params[ 'ticket-status' ] ) ){ $query[] = 'status:'.$params[ 'ticket-status' ]; }
		if( isset( $params[ 'ticket-type' ] ) ){ $query[] = 'ticket_type:'.$params[ 'ticket-type' ]; }
		if( isset( $params[ 'ticket-priority' ] ) ){ $query[] = 'priority:'.$params[ 'ticket-priority' ]; }
		if( ! is_null( $params[ 'view' ] ) ){ $this->add_view_conditions_to_search_query( $query,  $params[ 'view' ] ); }
		if( isset( $params[ 'ticket-customfield' ] ) ){
			foreach ( $params[ 'ticket-customfield' ] as $param ){
				list( $field_id, $field_value ) = explode( ':' , $param );
				if( 'all' !== $field_value ){ $query[] = 'custom_field_'.$field_id.':'.$field_value; }
			}
		}

		$ticket_restriction = $zendesk_user->get_api_data( 'ticket_restriction' );
		switch ( $ticket_restriction ){
			case 'requested' :
				$query[] = 'requester:'.$zendesk_user->get_id();
				break;
			case 'organization' :
				$query[] =  'organization:'.$zendesk_user->get_api_data( 'organization_id' );
				break;
			/*
			 * TODO :: add agent restriction support
			case 'assigned' :
				//$query[] = 'assignee:'.$zendesk_user->get_id();
			case 'groups' :
				//https://developer.zendesk.com/api-reference/ticketing/groups/group_memberships/#show-membership
				//$query[] = 'group:'.$zendesk_user->get_groups( true );
			*/
		}

		$query_params = [
			'per_page' => ( isset( $params[ 'ticket-limit' ] ) ) ? ( int ) $params[ 'ticket-limit' ] : 10,
			'page'     => ( isset( $params[ 'ticket-page' ] ) ) ? ( int ) $params[ 'ticket-page' ] : 1,
		];
		try{
			$response = $admin_client->search()->find( implode( ' ',$query ), $query_params );
		}catch( \Exception $e ){
			$message = sprintf( esc_html__( 'An unknown error has occurred, please contact a administrator with this error code : %s.', 'yetix-request-form' ) , $e->getCode() );
			return new \WP_Error( 'zendesk_api_error', $message );
		}
		$message = sprintf( esc_html__( 'Ticket #%s find.', 'yetix-request-form' ) , $response->count );
		$tickets = array_map( function( $t ){
			$ticket = new ModelTicket( $t );
			return $ticket->get_rest_model();
		}, $response->results );
		return new \WP_REST_Response( [
			'code'    => 'zendesk_ticket_read',
			'message' => $message,
			'page'    => $query_params[ 'page' ],
			'limit'   => $query_params[ 'per_page' ],
			'count'   => ( 1000 < $response->count ) ? 1000 : $response->count,
			'tickets' => $tickets,
			'params'  => $params,
			'query'   => $query,
		] );

		return new \WP_Error( 'yetix_error', __( 'Unknown error', 'yetix-request-form') );
	}

	protected function add_view_conditions_to_search_query( &$query, $view_id ){
		if( ! is_int( $view_id ) ){ return; }
		$view = new ModelView( $view_id );
		$conditions = $view->get_conditions();
		$operator_map = [
			'is'           => ':',
			'is_not'       => ':',
			'less_than'    => '<',
			'greater_than' => '>',
		];
		if( isset( $conditions->all ) && !empty( $conditions->all ) ){
			//tags:"important urgent"
			$process = [];
			foreach( $conditions->all as $condition ){
				if( ! isset( $operator_map[ $condition->operator ] ) ) { continue; }
				$field_operator = ( ('is_not' === $condition->operator ) ? '-' : '').$condition->field.$operator_map[ $condition->operator ];
				$process[ $field_operator ][] = $condition->value;
			}
			foreach( $process as $operator => $values ){
				$query[] = $operator.'"'.implode( ' ',$values ).'"';
			}
		}
		if( isset( $conditions->any ) && !empty( $conditions->any ) ){
			//tags:important tags:urgent
			foreach( $conditions->any as $condition ){
				if( ! isset( $operator_map[ $condition->operator ] ) ) { continue; }
				$query[] = ( ( 'is_not' === $condition->operator ) ? '-' : '' )
					.$condition->field.$operator_map[ $condition->operator ].$condition->value;
			}
		}
		$execution = $view->get_execution();
		if( ! is_null( $execution->sort_by ) && ! is_null( $execution->sort_order ) ){
			$query[] = 'order_by:'.$execution->sort_by;
			$query[] = 'sort:'.( ( 'asc' === $execution->sort_by ) ? 'asc' : 'desc' );
		}
	}

	/**
	 * endpoint_controller_post_ticket
	 * POST tickets route controller
	 * @param \Requests $request client request
	 */
	public function endpoint_controller_post_ticket( $request ){
		$params       = $request->get_params();
		$zendesk_user = Tracker::get_current_zendesk_user();
		$admin_client = Tracker::get_zendesk_administrator_client();

		//test connection
		if( 1 !== $admin_client->get_status() ){
			$message = esc_html__( 'Cannot connect to zendesk, please contact an administrator.', 'yetix-request-form' );
			return new \WP_Error( 'zendesk_api_error', $message, [ 'params' => $params ] );
		}

		//try sending ticket
		try{
			$custom_fields = [];
			$field_data    = Tracker::get_ticket_fields();
			foreach( $field_data as $custom_field ){
				$params_name = sanitize_title( $custom_field->title ).'_'.$custom_field->id;
				if( ! isset( $params[ $params_name ] ) ){ continue; }
				$custom_fields[ $custom_field->id ] = $params[ $params_name ];
			}

			$priority         = ( isset( $params[ 'priority' ] ) ) ? $params[ 'priority' ] : null;
			$type             = ( isset( $params[ 'tickettype' ] ) ) ? $params[ 'tickettype' ] : null;
			$status           = ( isset( $params[ 'status' ] ) ) ? $params[ 'status' ] : null;
			$attachment_token = ( isset( $params[ 'attachment_token' ] ) ) ? explode( ',', $params[ 'attachment_token' ] ) : [];

			$ticket = [
				'subject'   => $params[ 'subject' ],
				'comment'   => [ 'html_body' => $params[ 'description' ] ],
			];
			$ticket[ 'requester' ] = ( $zendesk_user )
				? [ 'name'  => $zendesk_user->get_name(), 'email' => $zendesk_user->get_email() ]
				: [ 'name'  => $params[ 'name' ], 'email' => $params[ 'email' ] ];

			if( ! empty( $custom_fields ) ){ $ticket[ 'custom_fields' ] = $custom_fields; }
			if( ! empty( $priority ) ||  ! is_null( $priority ) ){ $ticket[ 'priority' ] = $priority; }
			if( ! empty( $status ) ||  ! is_null( $status ) ){ $ticket[ 'status' ] = $status; }
			if( ! empty( $type ) ||  ! is_null( $type ) ){ $ticket[ 'type' ] = $type; }
			if( ! empty( $attachment_token ) ){ $ticket[ 'comment' ][ 'uploads' ] = $attachment_token; }

			$response = $admin_client->tickets()->create( $ticket );

		}catch( \Exception $e ){
			//zendesk return a error
			//translators: argument 1 is a error code send by zendesk
			$message = sprintf( esc_html__( 'An unknown error has occurred, please contact a administrator with this error code : %s.', 'yetix-request-form' ) , $e->getCode() );
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
		//translators: argument 1 is a ticket number send by zendesk
		$message = sprintf( esc_html__( 'Ticket #%s created.', 'yetix-request-form' ) , $response->ticket->id );
		return new \WP_REST_Response( [
			'code'    => 'zendesk_ticket_send',
			'message' => $message,
			'ticket'  => $ticket,
		] );
	}

	/**
	 * endpoint_controller_post_ticket_comment
	 * Post tickets/comment route controller
	 * @param \Requests $request client request
	 */
	public function endpoint_controller_post_ticket_comment( $request ){
		$params = $request->get_params();
		$user   = Tracker::get_current_zendesk_user();
		$client = Tracker::get_zendesk_administrator_client();
		$ticket = new ModelTicket( $params[ 'comment-ticket' ] );

		if( ! $user ){
			$message = esc_html__( 'Your are not link to a valid zendesk user', 'yetix-request-form' );
			return new \WP_Error( 'zendesk_api_error', $message );
		}
		if( ! $user->has_right( $ticket ) ){
			$message = esc_html__( 'Your cant update this ticket', 'yetix-request-form' );
			return new \WP_Error( 'zendesk_api_error', $message );
		}
		if( 1 !== $client->get_status() ){
			$message = esc_html__( 'Cannot connect to zendesk, please contact an administrator.', 'yetix-request-form' );
			return new \WP_Error( 'zendesk_api_error', $message );
		}

		//Process custom_field
		$custom_fields = [];
		$field_data    = Tracker::get_ticket_fields();
		foreach( $field_data as $custom_field ){
			$params_name = sanitize_title( $custom_field->title ).'_'.$custom_field->id;
			if( ! isset( $params[ $params_name ] ) ){ continue; }
			$custom_fields[ $custom_field->id ] = $params[ $params_name ];
		}

		//if attachments and comment is empty send an error.
		if( ! empty( $params[ 'attachment_token' ] ) && empty( $params[ 'comment-body' ] ) ){
			return new \WP_Error(
				'zendesk_api_error',
				esc_html__( 'You must add a comment to send along your attachments.', 'yetix-request-form' )
			);
		}

		//try update ticket
		$ticket_update    = [ 'status' => $params[ 'comment-status' ] ];
		//if comment empty juste send other params
		if( ! empty( $params[ 'comment-body' ] ) ){
			$ticket_update [ 'comment' ] = [
				'html_body' => $params[ 'comment-body' ],
				'public'    => true,
				'author_id' => $user->get_id(),
			];
		}
		$attachment_token = ( isset( $params[ 'attachment_token' ] ) ) ? explode( ',', $params[ 'attachment_token' ] ) : [];
		if( ! empty( $attachment_token ) ){ $ticket_update[ 'comment' ][ 'uploads' ] = $attachment_token; }
		if( ! empty( $custom_fields ) ){ $ticket_update[ 'custom_fields' ] = $custom_fields; }

		try{
			$response = $client->tickets()->update( $params[ 'comment-ticket' ], $ticket_update );
			if( isset( $response->ticket ) ){
				$ticket = new ModelTicket();
				$ticket->load_from_data( $response->ticket );
			}

		}catch( \Exception $e ){
			//zendesk return a error
			//translators: argument 1 is a error code send by zendesk
			$message = sprintf( esc_html__( 'An unknown error has occurred, please contact a administrator with this error code : %s.', 'yetix-request-form' ) , $e->getCode() );
			return new \WP_Error(
				'zendesk_api_error', $message, [
					'params'    => $params,
					'exception' => [ 'code' => $e->getCode(), 'msg' => $e->getMessage() ]
				]
			);
		}

		//success response
		return new \WP_REST_Response( [
			'code'    => 'zendesk_ticket_updated',
			'message' => esc_html__( 'Comment send.', 'yetix-request-form' ),
			'debug'   => [ 'response' => $response, 'params' => $params ]
		] );
	}

}