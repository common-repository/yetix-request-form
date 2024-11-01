<?php
namespace whodunit\yetix\page;

/**
 * Abstract class for administration pages
 * assure minimum functionality and behavior of an admin page
 * TODO::get set methods
 */
abstract class Page{

	protected $parent_page_slug;
	protected $page_slug;
	protected $page_template;
	protected $page_cap;
	protected $page_title;
	protected $page_menu_title;
	protected $page_menu_pos;
	protected $icon_url;

	/**
	 * __construct
	 * page default constructor
	 * @param null $parent_page_slug if string given set slugify parent_page_slug for admin parent page slug
	 * @param null $page_slug if string given set slugify page_slug for admin page slug
	 * @param null $page_cap if string given set slugify page_cap for admin page needed user capacity
	 * @param null $page_title if string given set page_title for admin page title
	 * @param null $page_menu_title if string given set page_menu_title for title in menu
	 * @param null $page_menu_pos if int given set page_menu_pos for position in menu
	 * @param null $page_icon if string given set icon_url for menu icon
	 */
	function __construct( $parent_page_slug = null, $page_slug = null, $page_template = null, $page_cap = null, $page_title = null, $page_menu_title = null, $page_menu_pos = null, $page_icon = null ){
		if( ! empty( $parent_page_slug ) && is_string( $parent_page_slug ) ){ $this->parent_page_slug = sanitize_title( $parent_page_slug ); }
		if( ! empty( $page_slug ) && is_string( $page_slug ) ){ $this->page_slug = sanitize_title( $page_slug ); }
		if( ! empty( $page_template ) && is_string( $page_template ) ){ $this->page_template = sanitize_title( $page_template ); }
		if( ! empty( $page_slug ) && is_string( $page_cap ) ){ $this->page_cap = sanitize_title( $page_cap ); }
		if( ! empty( $page_slug ) && is_string( $page_title ) ){ $this->page_title = $page_title; }
		if( ! empty( $page_slug ) && is_string( $page_menu_title ) ){ $this->page_menu_title = $page_menu_title; }
		if( ! empty( $page_icon ) && is_string( $page_icon ) ){ $this->page_icon = $page_icon; }
		if( is_int( $page_menu_pos ) ){ $this->page_menu_pos = $page_menu_pos; }

		add_action( 'admin_menu',        [ $this, 'admin_menu' ] );
		add_filter( 'admin_footer_text', [ $this, 'admin_footer_text' ], 15, 1 );
		add_filter( 'update_footer',     [ $this, 'admin_footer_update' ], 15, 1 );
	}

	/**
	 * admin_menu
	 * Pages default menu register
	 */
	public function admin_menu(){
		if( $this->parent_page_slug ){
			add_submenu_page(
				$this->parent_page_slug,
				$this->page_title,
				$this->page_menu_title,
				$this->page_cap,
				$this->page_slug,
				[ $this, 'render_admin_page' ],
				$this->page_menu_pos
			);
		}else{
			add_menu_page(
				$this->page_title,
				$this->page_menu_title,
				$this->page_cap,
				$this->page_slug,
				[ $this, 'render_admin_page' ],
				$this->page_icon,
				$this->page_menu_pos
			);
			add_submenu_page(
				$this->page_slug,
				$this->page_menu_title,
				$this->page_title,
				$this->page_cap,
				$this->page_slug,
				[ $this, 'render_admin_page' ],
				$this->page_menu_pos
			);
		}
	}

	/**
	 * render_admin_page
	 * Pages default render
	 */
	public function render_admin_page(){
		$this->load_template( [], true );
	}

	/**
	 * admin_footer_text
	 * admin_footer_text hook callback do not use
	 * @param $text
	 * @return mixed|string
	 */
	function admin_footer_text( $text ){
		global $hook_suffix;
		if( $this->page_slug === $hook_suffix ){
			return '<span id="footer-thankyou">'.__( 'Thank you for using our product :)', 'yetix-request-form').'</span>';
		}
		return $text;
	}

	/**
	 * admin_footer_update
	 * update_footer hook callback do not use
	 * @param $text
	 * @return mixed|string
	 */
	function admin_footer_update( $text ){
		global $hook_suffix;
		if( $this->page_slug === $hook_suffix ){
			return YETIX_REQUEST__DISPLAY_VERSION;
		}
		return $text;
	}

	/**
	 * load_template
	 * display template page from views/pages/ plugin directory.
	 * page template contrary to blocks, shortcode and widget are not overwritable
	 * view file must be named <page_slug>.php
	 * @param array $attributes an array of attributes, attributes are extract so take care of having an array with valid keys
	 * @param false $echo echo flag, default false, echo template if true, try to return template as string if false
	 * @return false|string return template as string or false if output buffering fail, return an empty string if $echo is true.
	 */
	public function load_template( $attributes = [], $echo = false ){
		if( false === $echo ){ ob_start(); }
		$file = YETIX_REQUEST__PLUGIN_DIR.'/views/pages/'.$this->page_template.'.php';
		if( file_exists( $file ) ){
			if( is_array( $attributes ) ){
				extract( $attributes );
				unset( $attributes );
			}
			include( $file );
		}
		if( false === $echo ){ return ob_get_clean(); }
		return "";
	}

}