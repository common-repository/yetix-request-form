<?php
namespace whodunit\yetix\page;

use whodunit\utility\Notices;
use whodunit\utility\Tracker;
use whodunit\utility\WordPress;

class PageAdminSettings extends Page {

	protected $parent_page_slug = null;
	protected $page_slug        = 'yetix_request__settings';
	protected $page_template    = 'admin_settings';
	protected $page_cap         = 'yetix_request__manage_plugin_options';
	protected $page_title       = null;
	protected $page_menu_title  = null;
	protected $page_menu_pos    = 55;

	/**
	 */
	function __construct(){
		$this->page_title      = __( 'General Settings', 'yetix-request-form' );
		$this->page_menu_title = __( 'Yetix', 'yetix-request-form' );
		$this->page_icon       =  'data:image/svg+xml;base64,'.base64_encode(
			file_get_contents( YETIX_REQUEST__PLUGIN_DIR.'assets/icon/icon-admin_menu.svg' )
		);
		parent::__construct(
			$this->parent_page_slug,
			$this->page_slug,
			$this->page_template,
			$this->page_cap,
			$this->page_title,
			$this->page_menu_title,
			$this->page_menu_pos,
			$this->page_icon
		);
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue' ], 5 );
		add_action( 'admin_init',            [ $this, 'plugin_admin_init' ], 10 );
		add_filter( 'admin_footer_text',     [ $this, 'admin_footer_text' ], 15, 1 );
		add_filter( 'update_footer',         [ $this, 'admin_footer_update' ], 15, 1 );
		add_filter( 'plugin_action_links_'.YETIX_REQUEST__PLUGIN_FILE_BASENAME, [ $this, 'plugin_action_links' ], 5, 1 );
	}

	/**
	 * plugin_admin_init
	 * use in hook admin_init do not call this method directly
	 * - redirect to this page after redirection
	 * @return void
	 */
	public function plugin_admin_init(){
		//TODO::test this behavior, it seem headers_sent do not detect header set by previous wp_redirect call.
		if( get_option( 'yetix_request__activation_trigger', false )
			&& ! headers_sent()
			&& 'true' !== @$_GET[ 'activate-multi' ]
		){
			wp_redirect( admin_url('admin.php?page=' . $this->page_slug) );
		}
	}

	/**
	 * plugin_action_links
	 * use in hook plugin_action_links_{ plugin basename } do not call this method directly
	 * - add a setting link to the plugin list
	 * @return void
	 */
	public function plugin_action_links( $actions ) {
		$actions[] = '<a href="'. esc_url( get_admin_url( null, '/admin.php?page=yetix_request__rf_settings' ) ) .'">'.__( 'Settings', 'yetix-request-form' ).'</a>';
		return $actions;
	}

	/**
	 * admin_footer_text
	 * use in hook admin_footer_text do not call this method directly
	 * - change footer text in this page
	 * @return void
	 */
	public function admin_footer_text( $text ){
		global $hook_suffix;
		if( 'toplevel_page_yetix_request__settings' === $hook_suffix ){
			$open_tag  = '<a href="'.esc_url( 'https://wordpress.org/support/plugin/'.YETIX_REQUEST__NAME.'/reviews/?filter=5' ).'">' ;
			$close_tag = '</a>' ;
			return '<span id="footer-thankyou">'. sprintf(
				//translators: argument 1 is a link opening tag to the plugin rating page, argument 2 a link closing tag
				__( 'Thank you for using Yetix Request Form! %1$sPlease rate us ★★★★★ on WordPress.org%2$s to help us spread the word.', 'yetix-request-form' ),
				$open_tag,
				$close_tag
			).'</span>';
		}
		return $text;
	}

	/**
	 * admin_footer_update
	 * use in hook admin_footer_update do not call this method directly
	 * - change version display in this page
	 * @return void
	 */
	public function admin_footer_update( $text ){
		global $hook_suffix;
		if( 'toplevel_page_yetix_request__settings' === $hook_suffix ){

			$yetix_footer_update_html  = '<span class="yetix_request__admin-footer-upgrade">';
			$yetix_footer_update_html .= '<img src="' . esc_url( YETIX_REQUEST__PLUGIN_URL .  'assets/img/yetix-admin-signature.svg'  ) . '" alt="">' ;
			$yetix_footer_update_html .= '<span>' . YETIX_REQUEST__DISPLAY_VERSION . '</span>';
			$yetix_footer_update_html .= '</span>' ;

			return $yetix_footer_update_html;
		}

		return $text;
	}

	/**
	 * admin_enqueue
	 * use in hook admin_enqueue_scripts do not call this method directly
	 * - register styles, scripts and js translation in this page ( template has the enqueue call )
	 * @return void
	 */
	public function admin_enqueue(){
		if( ! is_admin() ){ return; }
		global $hook_suffix;
		if( 'toplevel_page_yetix_request__settings' === $hook_suffix ){
			//register style
			wp_register_style(
				'yetix_request__style-admin',
				YETIX_REQUEST__PLUGIN_URL.'assets/css/style-admin.min.css',
				[ 'yetix_request__semantic-ui' ]
			);
			//register script
			wp_register_script(
				'yetix_request__admin_setting_script',
				YETIX_REQUEST__PLUGIN_URL.'assets/js/admin_settings.min.js',
				[ 'jquery', 'wp-i18n', 'jquery-ui-sortable', 'yetix_request__semantic-ui' ]
			);
			//register translation
			wp_set_script_translations(
				'yetix_request__admin_setting_script',
				'yetix-request-form',
				( WordPress::wordpress_dot_org_trad_exist() ) ? WP_LANG_DIR.'/plugins/' : YETIX_REQUEST__PLUGIN_DIR.'languages/'
			);
		}
	}

}