<?php
namespace whodunit\utility;

use whodunit\client\zendesk\ZendeskApiClient;
use whodunit\yetix\model\ModelUser;
use whodunit\yetix\Plugin;

class Tracker{

	static protected $cache = [
		'ticket_fields' => [ 'yetix_request__ticket_fields', DAY_IN_SECONDS ],
		'end_users'     => [ 'yetix_request__end_users', DAY_IN_SECONDS ],
		'oauth_clients' => [ 'yetix_request__oauth_app', DAY_IN_SECONDS ]
	];
	static protected $user_client;
	static protected $administrator_client;

	/**
	 * is_a_zendesk_user
	 * check if a given user is a valid zendesk user
	 * this will check for valid oauth token when zendesk user role is >= to agent
	 * elseif end user management is activated only for zendesk user id
	 **/
	static function is_a_zendesk_user( $user = false ){
		$user = ( false === $user ) ? wp_get_current_user() : $user;
		if( is_numeric( $user ) ){ $user = get_user_by( 'ID', $user ); }
		if( ! is_a( $user, 'WP_User' ) || 0 === @$user->ID ){ return false; }

		$user_option  = Plugin::getInstance()->get_options( 'user' );
		$zendesk_user = ( isset( $user_option[ 'manage_end_user' ] ) && 'true' === $user_option[ 'manage_end_user' ] && in_array( 'yetix_zendesk_user' , $user->roles ) )
			? get_user_meta( $user->ID, 'yetix_request__zendesk_user_id', true )
			: get_user_meta( $user->ID, 'yetix_request__zendesk_user_oauth_token', true );

		//TODO::maybe validate user id or user oauth token if this is not too heavy in resources and time

		return ( boolean ) $zendesk_user;
	}

	static function get_current_zendesk_user( $user = false ){
		if( self::is_a_zendesk_user() ) {
			$wp_user_id      = get_current_user_id();
			$zendesk_user_id = get_user_meta( $wp_user_id, 'yetix_request__zendesk_user_id', true );
			return new ModelUser( $zendesk_user_id );
		}
		return false;

	}

	static function unlink_zendesk_user( $user ){
		if( is_numeric( $user ) ){ $user = get_user_by( 'ID', $user ); }
		if( ! is_a( $user, 'WP_User' ) || 0 === @$user->ID ){ return false; }
		$now = strtotime( 'now' );
		delete_user_meta( $user->ID, 'yetix_request__zendesk_user_id' );
		delete_user_meta( $user->ID, 'yetix_request__zendesk_user_oauth_token' );
		delete_user_meta( $user->ID, 'yetix_request__zendesk_user_meta' );
		update_user_meta( $user->ID, 'yetix_request__zendesk_user_update', $now + DAY_IN_SECONDS );
	}

	static function link_zendesk_user( $user, $zendesk_user_id ){
		if( is_numeric( $user ) ){ $user = get_user_by( 'ID', $user ); }
		if( ! is_a( $user, 'WP_User' ) || 0 === @$user->ID ){ return false; }
		update_user_meta( $user->ID, 'yetix_request__zendesk_user_id', $zendesk_user_id );
		delete_user_meta( $user->ID, 'yetix_request__zendesk_user_oauth_token' );
		self::update_zendesk_user( $user, true );
	}

	static function revoke_zendesk_user( $user ){
		if( is_numeric( $user ) ){ $user = get_user_by( 'ID', $user ); }
		if( ! is_a( $user, 'WP_User' ) || 0 === @$user->ID ){ return false; }
		$now = strtotime( 'now' );
		delete_user_meta( $user->ID, 'yetix_request__zendesk_user_oauth_token' );
		delete_user_meta( $user->ID, 'yetix_request__zendesk_user_meta' );
		self::update_zendesk_user( $user, true );
	}

	/**
	 * update_zendesk_user
	 * try to update zendesk user meta for current user or given user
	 **/
	static function update_zendesk_user( $user = false, $force_update = false ){
		$user = ( false === $user ) ? wp_get_current_user() : $user;
		if( is_numeric( $user ) ){ $user = get_user_by( 'ID', $user ); }
		if( ! is_a( $user, 'WP_User' ) || 0 === @$user->ID ){ return false; }

		$now                 = strtotime( 'now' );
		$zendesk_user_id     = get_user_meta( $user->ID, 'yetix_request__zendesk_user_id', true );
		$zendesk_user_update = get_user_meta( $user->ID, 'yetix_request__zendesk_user_update', true );
		$zendesk_user_token  = get_user_meta( $user->ID, 'yetix_request__zendesk_user_oauth_token', true );

		$user_option         = Plugin::getInstance()->get_options( 'user' );
		$response            = null;

		//user has an zendesk_id and manage end user is activated, profile must be yetix_zendesk_user too
		if( $zendesk_user_id
			&& 'true' === $user_option[ 'manage_end_user' ]
			&& in_array( 'yetix_zendesk_user' , $user->roles )
			&& ( intval( $zendesk_user_update ) < $now || $force_update )
		){
			$client = Tracker::get_zendesk_administrator_client();
			try{
				if( 1 === $client->get_status() ){ $response = $client->users()->find( $zendesk_user_id ); }
			}catch( \Exception $e ){
				//TODO::handle error
				var_dump( $e->getMessage() );
			}
		//user a oauth token try to use that
		}elseif( $zendesk_user_token && ( intval( $zendesk_user_update ) < $now || $force_update ) ){
			$client = Tracker::get_zendesk_user_client( null, null, $user->ID );
			if( 1 === $client->get_status() ){ $response = $client->users()->me(); }
		}

		if( isset( $response->user->id ) ){
			update_user_meta( $user->ID, 'yetix_request__zendesk_user_meta', [
				'id'              => $response->user->id,
				'name'            => $response->user->name,
				'email'           => $response->user->email,
				'organization_id' => $response->user->organization_id,
				'role'            => $response->user->role,
				'last_login_at'   => isset( $response->user->last_login_at ) ? $response->user->last_login_at : false,
				'verified'        => $response->user->verified,
			] );
			update_user_meta( $user->ID , 'yetix_request__zendesk_user_id', $response->user->id );
			update_user_meta( $user->ID , 'yetix_request__zendesk_user_update', $now + DAY_IN_SECONDS );
			return true;
		}else{
			return false;
		}
	}

	static function process_user_for_listing( $user ){
		$user = ( false === $user ) ? wp_get_current_user() : $user;
		if( is_numeric( $user ) ){ $user = get_user_by( 'ID', $user ); }
		if( ! is_a( $user, 'WP_User' ) || 0 === @$user->ID ){ return false; }

		$user_option                   = Plugin::getInstance()->get_options( 'user' );
		$zendesk_user_id               = get_user_meta( $user->ID, 'yetix_request__zendesk_user_id', true );
		$zendesk_user_update           = get_user_meta( $user->ID, 'yetix_request__zendesk_user_update', true );
		$zendesk_user_token            = get_user_meta( $user->ID, 'yetix_request__zendesk_user_oauth_token', true );
		$zendesk_user_meta             = get_user_meta( $user->ID, 'yetix_request__zendesk_user_meta', true );
		$zendesk_last_zendesk_login    = get_user_meta( $user->ID, 'yetix_request__zendesk_user_last_login', true );
		$wordpress_user_last_connexion = get_user_meta( $user->ID, 'yetix_request__wordpress_user_last_connexion', true );
		//TODO::find a better way to do this
		$wordpress_user_verified       = ! get_user_meta( $user->ID, 'default_password_nag', true );

		$list_user = [
			'wordpress' => [
				'id'                       => $user->ID,
				'name'                     => ( $user->display_name ) ? $user->display_name : $user->user_login,
				'email'                    => $user->user_email,
				'role'                     => implode(', ', $user->roles ),
				'last_connexion_wordpress' => $wordpress_user_last_connexion,
				'last_connexion_zendesk'   => $zendesk_last_zendesk_login,
				'verified'                 => $wordpress_user_verified,
			],
			'zendesk'   => [
				'id'             => ( $zendesk_user_id  && isset( $zendesk_user_meta[ 'id' ] ) ) ? $zendesk_user_meta[ 'id' ] : 'unknown',
				'name'           => isset( $zendesk_user_meta[ 'name' ] ) ? $zendesk_user_meta[ 'name' ] : 'unknown',
				'email'          => isset( $zendesk_user_meta[ 'email' ] ) ? $zendesk_user_meta[ 'email' ] : 'unknown',
				'role'           => isset( $zendesk_user_meta[ 'role' ] ) ? $zendesk_user_meta[ 'role' ] : 'unknown',
				'last_connexion' => isset( $zendesk_user_meta[ 'last_login_at' ] ) ? $zendesk_user_meta[ 'last_login_at' ] : 'unknown',
				'verified'       => isset( $zendesk_user_meta[ 'verified' ] ) ? $zendesk_user_meta[ 'verified' ] : 'unknown',

			],
			'sync'      => ( $zendesk_user_update ) ? $zendesk_user_update : 'unsync' ,
			'link_type' => ( $zendesk_user_token ) ? 'oauth' : ( ( $zendesk_user_id ) ? 'linked' : 'none' ),
			'actions'   => []
		];
		if( 'true' === $user_option[ 'manage_end_user' ] && in_array( 'yetix_zendesk_user' , $user->roles ) ){
			$list_user[ 'actions' ][] = ( $zendesk_user_id ) ? 'unlink' : 'link';
		}
		if( $zendesk_user_token ){
			$list_user[ 'actions' ][] = 'revoke';
		}
		return $list_user;
	}

	static function check_zendesk_token_api_connection(){
		$return = [
			'code'   => 'success',
			'message' => __( 'Zendesk connected', 'yetix-request-form' )
		];
		if( ! self::$administrator_client ){ self::get_zendesk_administrator_client(); }
		if( 1 !== self::$administrator_client->get_status() ){
			$error   = self::$administrator_client->get_last_error();
			$json    = json_decode( $error[ 'message' ], true );
			$message = ( isset( $json[ 'error' ][ 'message' ] ) ) ? $json[ 'error' ][ 'message' ] : $error[ 'message' ];
			$return[ 'code' ]    = 'error';
			$return[ 'message' ] = $message;
		}
		return $return;
	}

	static function get_zendesk_user_client( $email = null, $password = null, $user_id = null ){
		if( ! is_null( $email ) && ! is_null( $password ) ){
			$subdomain = Plugin::getInstance()->get_options( 'zendesk_domain' );
			$client    = new ZendeskApiClient( $subdomain );
			$client->authenticate_end_user( $email, $password );
		}else{
			if( self::$user_client && is_null( $user_id ) ){ return self::$user_client; }
			$subdomain = Plugin::getInstance()->get_options( 'zendesk_domain' );
			$client    = new ZendeskApiClient( $subdomain );
			$user_id = ( is_numeric( $user_id ) ) ? ( int ) $user_id : get_current_user_id();
			if( Tracker::is_a_zendesk_user( $user_id ) ){
				$token   = ( 0 !== $user_id ) ? get_user_meta( $user_id , 'yetix_request__zendesk_user_oauth_token', true ) : false;
				if( $token ){ $client->authenticate_oauth_user( $token ); }
			}
		}
		self::$user_client = $client;
		return $client;
	}

	static function get_zendesk_administrator_client(){
		if( self::$administrator_client ){ return self::$administrator_client; }
		$subdomain = Plugin::getInstance()->get_options( 'zendesk_domain' );
		$user      = Plugin::getInstance()->get_options( 'zendesk_user' );
		$token     = Plugin::getInstance()->get_options( 'zendesk_token' );
		$client    = new ZendeskApiClient( $subdomain );
		$client->authenticate_administrator( $user, $token );
		self::$administrator_client = $client;
		return $client;
	}

	static function is_sytem_field( $field_type ){
		return ( in_array( $field_type, [ 'subject', 'description', 'tickettype', 'status', 'priority', 'group', 'assignee' ] ) ) ? true : false;
	}

	static function get_ticket_fields( $ticket_field_id = null, $reload_cache = false ){
		if( $reload_cache ){ delete_transient( self::$cache[ 'ticket_fields' ][ 0 ] ); }
		$ticket_fields = get_transient( self::$cache[ 'ticket_fields' ][ 0 ] );
		if( ! is_int( $ticket_field_id ) && $ticket_fields ){
			return $ticket_fields;
		}elseif( isset( $ticket_fields[ $ticket_field_id ] ) ){
			return $ticket_fields[ $ticket_field_id ];
		}

		$client = self::get_zendesk_administrator_client();
		if( ! $ticket_fields ){ $ticket_fields = []; }

		if( 1 === $client->get_status() ){
			if( is_int( $ticket_field_id ) ){
				$response = $client->ticketFields()->find( $ticket_field_id );
				$ticket_fields[ $ticket_field_id ] = $response->ticket_field;
			}else{
				$response = $client->ticketFields()->findAll();
				foreach( $response->ticket_fields as $ticket_field ){
					//remove some unsupported type
					if( in_array( $ticket_field->type, [ 'group', 'assignee' ] ) ){ continue; }
					$ticket_fields[ $ticket_field->id ] = $ticket_field;
				}
			}
			set_transient( self::$cache[ 'ticket_fields' ][ 0 ], $ticket_fields, self::$cache[ 'ticket_fields' ][ 1 ] );
		}

		if( is_int( $ticket_field_id ) ){
			if( isset( $ticket_fields[ $ticket_field_id ] ) ){
				return $ticket_fields[ $ticket_field_id ];
			}
			return false;
		}
		return $ticket_fields;
	}

	static function get_custom_ticket_fields( $ticket_field_id = null, $reload_cache = false ){
		$ticket_fields = self::get_ticket_fields();
		$ticket_fields = array_filter( $ticket_fields, function( $f ){
			return ! self::is_sytem_field( $f->type );
		} );
		if( is_int( $ticket_field_id ) ){
			if( isset( $ticket_fields[ $ticket_field_id ] ) ){
				return $ticket_fields[ $ticket_field_id ];
			}
			return false;
		}
		return $ticket_fields;
	}

	//TODO rename this oauth zendesk app
	static function get_oauth_clients( $client_id = null, $reload_cache = false ){
		if( $reload_cache ){ delete_transient( self::$cache[ 'oauth_clients' ][ 0 ] ); }
		$oauth_clients = get_transient( self::$cache[ 'oauth_clients' ][ 0 ] );
		if( ! is_int( $client_id ) && $oauth_clients ){
			return $oauth_clients;
		}elseif( isset( $oauth_clients[ $client_id ] ) ){
			return $oauth_clients[ $client_id ];
		}

		$client = self::get_zendesk_administrator_client();
		if( ! $oauth_clients ){ $clients = []; }

		try {
			if (1 === $client->get_status()) {
				if (is_int($client_id)) {
					$response = $client->oauthClients()->find($client_id);
					$oauth_clients[$client_id] = $response->client;
				} else {
					$response = $client->oauthClients()->findAll();
					foreach ($response->clients as $oauth_client) {
						$oauth_clients[$oauth_client->id] = $oauth_client;
					}
				}
				set_transient(self::$cache['oauth_clients'][0], $oauth_clients, self::$cache['oauth_clients'][1]);
			}
		}catch ( \Exception $e ){
			//TODO::handle error
			var_dump( $e->getMessage() );
		}

		if( is_int( $client_id ) ){
			return $oauth_clients[ $client_id ];
		}
		return $oauth_clients;
	}

	/**
	 * get_zendesk_end_user
	 * retrieve zendesk end-user from cache or from zendesk api token connection.
	 * @param false $reload_cache flag ignore existing cache and refresh it.
	 * @return array return an array of with zendesk user name and user id, or an empty array.
	 */
	static function get_zendesk_end_user( $reload_cache = false ){
		//cache
		if( $reload_cache ){ delete_transient( self::$cache[ 'end_users' ][ 0 ] ); }
		$end_users = get_transient( self::$cache[ 'end_users' ][ 0 ] );
		if( $end_users ){ return $end_users; }

		//load client
		$client = self::get_zendesk_administrator_client();
		if( ! $end_users ){ $end_users = []; }
		try {
			if ( 1 === $client->get_status()) {
				$response = $client->users()->findAll( [ 'role' => 'end-user' ] );
				if( isset( $response->users ) ){ $end_users = $response->users; }
				set_transient( self::$cache[ 'end_users' ][ 0 ], $end_users, self::$cache[ 'end_users' ][ 1 ] );
			}
		}catch ( \Exception $e ){
			//TODO::handle error
			var_dump( $e->getMessage() );
		}
		return $end_users;
	}

}