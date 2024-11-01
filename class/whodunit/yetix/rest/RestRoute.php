<?php
//<namespace::begin>
namespace whodunit\yetix\rest;
//<namespace::end>

/**
 * Abstract RestRoute class
 * define base feature of all Yetix wp api endpoint
 */
abstract class RestRoute{

	protected $name_space;
	protected $route;
	protected $params;

	/**
	 * Constructor
	 * - declare plugin init hook
	 * - declare options filters
	 * @param string $name_space route name space
	 * @param string $route      route name
	 * @param array $params      set all route available params, see
	 * https://developer.wordpress.org/reference/functions/register_rest_route/
	 * https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 */
	public function __construct( $name_space = 'yetix/v1', $route = null, $params = [] ){
		if( ! empty( $name_space ) && is_string( $name_space ) ){ $this->name_space = $name_space; }
		if( ! empty( $route ) && is_string( $route ) ){ $this->route = $route; }
		if( is_array( $params ) ){ $this->params = $params; }
	}

	/**
	 * set_routes
	 * register a route for the current name space
	 * @param string | array $method
	 * @param callable $callback set a route controller as a callable
	 * @param array $params set route params use params key set in constructor for this route to use
	 * @param callable $permission_callback set route permission validation callback
	 * @param string | null $sub_route define a sub route for the current route like <rest_root>/<namespace>/<route>/<sub_route>
	 */
	public function set_routes( $method, $callback, $params, $permission_callback, $sub_route = null ) {
		$args = [];
		foreach( $params as $p ){
			//TODO::use validate_params and sanitize_params
			if( isset( $this->params[ $p ] ) ){
				$k = ( isset( $this->params[ $p ][ 'key' ] ) ) ? $this->params[ $p ][ 'key' ] : $p;
				if( true === $this->params[ $p ][ 'required' ] ){
					$args[ $k ][ 'required' ] = true;
				}
				if( isset( $this->params[ $p ][ 'validate' ] ) && is_callable( $this->params[ $p ][ 'validate' ] ) ){
					$args[ $k ][ 'validate_callback' ] = $this->params[ $p ][ 'validate' ];
				}
				if( isset( $this->params[ $p ][ 'sanitize' ] ) && is_callable( $this->params[ $p ][ 'sanitize' ] ) ){
					$args[ $k ][ 'sanitize_callback' ] = $this->params[ $p ][ 'sanitize' ];
				}
			}
		}
		add_action( 'rest_api_init', function() use( $method, $callback, $args, $permission_callback, $sub_route ){
			$route = ( is_null( $sub_route ) ) ? $this->route : $this->route.'/'.$sub_route;
		    register_rest_route( $this->name_space, '/'.$route, [
				'methods'             => $method,
				'callback'            => $callback,
				'args'                => $args,
				'permission_callback' => $permission_callback,
			] );
		} );
	}

	/**
	 * validate_params
	 * resolve validation callback
	 * @param mixed $param_value params value
	 * @param \Requests $request current request
	 * @param string $key the param key use has identifier
	 */
	public function validate_params( $param_value, $request, $key ){
		if( isset( $this->params[ $key ] ) ){
			if( isset( $this->params[ $key ][ 'validate' ] ) && is_callable( $this->params[ $key ][ 'validate' ] ) ){
				return $this->params[ $key ][ 'validate' ]( $param_value, $request, $key );
			}
			return false;
		}
		return true;
	}

	/**
	 * sanitize_params
	 * resolve sanitize callback
	 * @param mixed $param_value params value
	 * @param \Requests $request client request
	 * @param string $key the param key use has identifier
	 */
	public function sanitize_params( $param_value, $request, $key ){
		if( isset( $this->params[ $key ] ) ){
			if( isset( $this->params[ $key ][ 'sanitize' ] ) && is_callable( $this->params[ $key ][ 'sanitize' ] ) ){
				return $this->params[ $key ][ 'sanitize' ]( $param_value, $request, $key );
			}
		}
		return sanitize_text_field( $param_value );
	}

}