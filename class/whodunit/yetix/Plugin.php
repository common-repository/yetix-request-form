<?php
//<namespace::begin>
namespace whodunit\yetix;
//<namespace::end>

//<use::begin>
use whodunit\utility\Notices;
use whodunit\utility\Tracker;
use whodunit\utility\WordPress;
use whodunit\yetix\behavior\BehaviorUsers;
use whodunit\yetix\block\BlockOrganization;
use whodunit\yetix\block\BlockRequestForm;
use whodunit\yetix\block\BlockRequestsList;
use whodunit\yetix\entity\EntityTicket;
use whodunit\yetix\metric\Metric;
use whodunit\yetix\page\PageAdminDebug;
use whodunit\yetix\page\PageAdminSettings;
use whodunit\yetix\page\PageTLSettings;
use whodunit\yetix\page\PageTLModules;
use whodunit\yetix\page\PageTLLicense;
use whodunit\yetix\page\PageTLWizard;
use whodunit\yetix\page\PageTLZendeskUsers;
use whodunit\yetix\rest\RestRouteAttachments;
use whodunit\yetix\rest\RestRouteMetrics;
use whodunit\yetix\rest\RestRouteOAuth;
use whodunit\yetix\rest\RestRouteViews;
use whodunit\yetix\rest\RestRouteZendeskUser;
use whodunit\yetix\rest\RestRouteOrganizations;
use whodunit\yetix\rest\RestRouteSettings;
use whodunit\yetix\rest\RestRouteBlockTemplates;
use whodunit\yetix\rest\RestRouteTicketFields;
use whodunit\yetix\rest\RestRouteTickets;
use whodunit\yetix\rest\RestRouteTracker;
use whodunit\yetix\shortcode\ShortCodeRequestForm;
//<use::end>

/**
 * Plugin class
 * Plugin main class is a singleton instantiated at init, use Plugin::get_instance() to get the instance
 * - init Page objects for back and Entity object for front office
 * - init Block, Shortcode, Rest routes and plugin behavior
 * - can access and save options
 * - can access Metrics
 */
class Plugin {

	private static $instance;
	protected      $initiated   = false;
	protected      $metrics     = [];
	protected      $permissions = [
		'administrator' => [
			'yetix_request__manage_plugin_options',
			'yetix_request__debug_plugin'
		]
	];
	protected      $options     = [
		'connection_method'        => 'api_token',
		'zendesk_domain'           => null,
		'zendesk_user'             => null,
		'zendesk_token'            => null,
		'zendesk_name_field'       => [
			'display'   => 'true',
			'required'  => 'true',
			'label'     => '',
			'default'   => '',
			'order'     => '0',
		],
		'zendesk_email_field'      => [
			'display'  => 'true',
			'required' => 'true',
			'label'    => '',
			'default'  => '',
			'order'     => '1',
		],
		'zendesk_attachment_field' => [
			'display'     => 'false',
			'required' 	  => 'false',
			'label'       => '',
			'max_size'    => '2097152',
			'allowed_ext' => 'jpeg, jpg ,png ,gif ,pdf',
			'order'       => '3',
		],
		'zendesk_fields'           => [],
	];
	protected $options_filters  = [];

	/**
	 * getInstance
	 * Plugin is a singleton
	 * @return Plugin return the unique instance of plugin
	 */
	public static function getInstance(){
		if( is_null ( self::$instance ) ){ self::$instance = new Plugin(); }
		return self::$instance;
	}

	/**
	 * Constructor
	 * - declare plugin init hook
	 * - declare options filters
	 */
	final private function __construct(){
		add_action( 'plugins_loaded', [ $this, 'plugin_loaded' ] );
		add_action( 'admin_init', [ $this, 'plugin_admin_init' ], 100 );
		add_action( 'wp_enqueue_scripts', [ $this, 'plugin_register_common_script' ], 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'plugin_register_common_script' ], 1 );

		register_activation_hook( YETIX_REQUEST__PLUGIN_FILE, [ $this, 'plugin_enable' ] );
		register_deactivation_hook( YETIX_REQUEST__PLUGIN_FILE, [ $this, 'plugin_disable' ] );

		//TODO::move this !
		$this->set_filter_options( 'allowed_ext', function( $v ){
			$exts = explode( ',', $v );
			$exts = array_map( function( $ext ){ return trim( $ext, " .\n\r\t\v\0" ); }, $exts );
			$exts = array_filter( $exts, function( $ext ){  return ( ! empty( $ext ) ); } );
			$exts = array_unique( $exts );
			//TODO::add a filter for the really dangerous file like php, exe, js, ect
			return implode( ', ', $exts );
		} );
		$this->set_filter_options( 'max_size', function( $v ){
			$max_uplaod = wp_max_upload_size();
			return ( 0 >= $v || $v > $max_uplaod ) ? $max_uplaod : $v;
		} );

	}

	/**
	 * No Clone
	 */
	final public function __clone(){
		throw new Exception( 'Clone not Allowed' );
	}

	/**
	 * No wakeup
	 */
	final public function __wakeup(){
		throw new Exception( 'Wakeup not Allowed' );
	}

	/**
	 * plugin_loaded
	 * front and back plugin init
	 * - load option
	 * - load translation
	 * - load plugin package
	 * - flag Plugin as initialized
	 * @return void
	 */
	public function plugin_loaded(){
		//load plugin metas
		$this->load_options();

		//load translation
		if( ! WordPress::wordpress_dot_org_trad_exist() ) {
			load_plugin_textdomain( 'yetix-request-form', false, basename(YETIX_REQUEST__PLUGIN_DIR) . '/languages' );
		}
		//load packages
new BlockRequestForm();
new PageAdminSettings();
new RestRouteSettings();
new RestRouteTracker();
new RestRouteTicketFields();
new RestRouteTickets();
new RestRouteAttachments();
new ShortCodeRequestForm();

		//set init flag
		$this->initiated = true;
	}

	/**
	 * plugin_register_common_script
	 * register common script and style
	 * @return void
	 */
	function plugin_register_common_script() {
		wp_register_script(
			'yetix_request__semantic-ui',
			YETIX_REQUEST__PLUGIN_URL . '/assets/distributable/semantic-ui/semantic.min.js',
			['jquery']
		);
		wp_register_style(
			'yetix_request__semantic-ui',
			YETIX_REQUEST__PLUGIN_URL . '/assets/distributable/semantic-ui/semantic-yetix.min.css' // semantic-ui inside .yetix
		);
	}

	/**
	 * plugin_enable
	 * activation process
	 * - add plugin rights to defined roles in $this->permissions
	 * - update version in base
	 * - create an installation notice
	 * - force redirection to the setting page
	 * @return void
	 */
	public function plugin_enable(){
		foreach( $this->permissions as $role => $caps ){
			$r = get_role( $role );
			if( is_array( $caps ) ){
				foreach( $caps as $cap ){ $r->add_cap( $cap ); }
			}
		}
		update_option( 'yetix_request__version', YETIX_REQUEST__VERSION );
		update_option( 'yetix_request__activation_trigger', true );
		$notices = Notices::get_instance();
		//translators: argument 1 is the plugin display name
		$notices->append( 'success', sprintf( __( '%s has been activated with success.', 'yetix-request-form' ), YETIX_REQUEST__DISPLAY_NAME ) );
	}

	/**
	 * plugin_admin_init
	 * init plugin process ( low priority )
	 * - remove activation trigger option
	 * @return void
	 */
	public function plugin_admin_init(){
		if( get_option( 'yetix_request__activation_trigger', false ) ){
			delete_option( 'yetix_request__activation_trigger' );
		}
	}

	/**
	 * plugin_disable
	 * deactivation process to defined roles in $this->permissions
	 * - remove plugin rights
	 * @return void
	 */
	public function plugin_disable(){
		foreach( $this->permissions as $role => $caps ){
			$r = get_role( $role );
			if( is_array( $caps ) ){
				foreach( $caps as $cap ){ $r->remove_cap( $cap ); }
			}
		}
	}

	/**
	 * load_options
	 * load saved option into $this->options
	 * @return void
	 */
	protected function load_options(){
		$options = get_option( YETIX_REQUEST__METAS_OPTIONS );
		if( $options ){
			$this->options = WordPress::recursive_wp_parse_args( $options, $this->options, true );
		}
	}

	/**
	 * get_options
	 * return saved option
	 * @param null $index if index is define, it will return the specified option, else return all options
	 * @return array|mixed|null
	 */
	public function get_options( $index = null ){
		if( is_null( $index ) ){ return $this->options; }
		if( isset( $this->options[ $index ] ) ){ return $this->options[ $index ]; }
		return null;
	}

	/**
	 * set_filter_options
	 * set a input filter on a option key
	 * @param string $key the option key where you want to apply the filter, be careful options is a multi-dimensional array
	 * @param callable $filter the filter callable has only 1 argument value ( value can be change by reference or when return by filter )
	 * @return void
	 */
	protected function set_filter_options( $key, callable $filter ){
		$this->options_filters[ $key ] = $filter;
	}

	/**
	 * filter_options
	 * apply pre defined filter
	 * @param string $key use key to identify what filter to apply
	 * @param mixed $value value to filter as a reference
	 * @return boolean ruturn true if a filter have been apply
	 */
	protected function filter_options( $key, &$value ){
		if( isset( $this->options_filters[ $key ] )  && is_callable( $this->options_filters[ $key ] ) ){
			$value = $this->options_filters[ $key ]( $value );
			return true;
		}
		return false;
	}

	/**
	 * init_default_options
	 * reset custom field options, this trigger at first connection to a new subdomain
	 * @return void
	 */
	public function init_default_options(){
		$this->options[ 'zendesk_fields' ] =  [];
		$custom_fields = Tracker::get_ticket_fields( null, true );
		$order = 4;
		foreach( $custom_fields as $field ){
			if( ! $field->active ){ continue; }
			if( in_array( $field->type, [ 'subject', 'description' ] ) ){
				$this->options[ 'zendesk_fields' ][ $field->id ] = [
					'display'  => 'true',
					'required' => 'true',
					'label'    => '',
					'default'  => '',
					'order'    => ( 'subject' === $field->type ) ? '2' : '4' ,
				];
			}else{
				$this->options[ 'zendesk_fields' ][ $field->id ] = [
					'display'  => ( $field->visible_in_portal ) ? 'true' : 'false',
					'required' => ( $field->required_in_portal ) ? 'true' : 'false',
					'label'    => '',
					'default'  => '',
					'order'    => ( string ) $order++,
				];
			}
		}
		$this->save_options();
	}

	/**
	 * set_options
	 * set option, is recursive for multidimensional array
	 * do not remove or clear existing value but not specified in $values
	 * @param $options, options array to update
	 * @param $values, new value, can be a multidimensional array
	 * @return void
	 */
	protected function set_options( &$options, $values ){
		foreach( $values as $k=>&$v ){
			//if not filtered and value is a array use recursion
			if( ! $this->filter_options( $k, $v ) && is_array( $v )){
				if( ! isset ( $options[ $k ] ) ){ $options[ $k ] = []; }
				$this->set_options($options[ $k ], $v );
			}else{
				$options[ $k ] = $v;
			}
		}
	}

	/**
	 * save_options
	 * save current option and the given array as serialised array into the site meta table
	 * this use $this->set_options methode, this don't modify existing options non specified into the $options array
	 * @param array $options an array of options to save
	 * @return boolean return true is options has been updated, else return false
	 * Careful this can not differentiate a writing error from unchanged options
	 */
	public function save_options( array $options = [] ){
		$this->set_options( $this->options, $options );
		$old_options = get_option( YETIX_REQUEST__METAS_OPTIONS );
		if ( $this->options === $old_options || maybe_serialize( $this->options ) === maybe_serialize( $old_options ) ) {
			return 2;
		}
		return ( update_option( YETIX_REQUEST__METAS_OPTIONS, $this->options ) ) ? 1 : 0;
	}

	/**
	 * get_metric
	 * return currently active Metrics Object can return a specific object by handle
	 * @param string $handle
	 * @return Metric or array of Metric return null if handle is not found or an empty array if no Metric are declared
 	 */
	public function get_metric( $handle = null ){
		if( ! is_null( $handle ) ){
			return ( isset( $this->metrics[ $handle ] ) ) ? $this->metrics[ $handle ] : null;
		}
		return array_values( $this->metrics );
	}

	public function add_metric( Metric &$new_metric ){
		$handle = $new_metric->get_handle();
		if( isset( $this->metrics[ $handle ] ) ){ return false; }
		$this->metrics[ $handle ] = $new_metric;
		return true;
	}

}