<?php
//<namespace::begin>
namespace whodunit\yetix\shortcode;
//<namespace::end>

//<use::begin>
use whodunit\utility\WordPress;
//<use::end>

/*
 * ShortCodeRequestForm
 * Define RequestForm shortcode behavior and functionality
 */
class ShortCodeRequestForm extends ShortCode {

	protected $shortcode_name;
	protected $version;

	/**
	 * Constructor
	 * final object need no arguments
	 * - register shortcode
	 * - register needed script and style
	 */
	public function __construct() {
		parent::__construct( 'requestform', '0.1' );
		add_action( 'init', function(){
			wp_register_script(
				'yetix_request__block_'.$this->shortcode_name.'_script',
				YETIX_REQUEST__PLUGIN_URL.'assets/js/'.$this->shortcode_name.'_script.min.js',
				[ 'jquery', 'wp-i18n', 'yetix_request__semantic-ui' ]
			);
			//register front style
			wp_register_style(
				'yetix_request___block_'.$this->shortcode_name.'_style',
				YETIX_REQUEST__PLUGIN_URL.'assets/css/block/'.$this->shortcode_name.'.min.css',
				[ 'yetix_request__semantic-ui' ]
			);
			//register translation
			wp_set_script_translations(
				'yetix_request__block_requestform_script',
				'yetix-request-form',
				( WordPress::wordpress_dot_org_trad_exist() ) ? WP_LANG_DIR.'/plugins/' : YETIX_REQUEST__PLUGIN_DIR.'languages/'
			);
		} );
	}

	/**
	 * shortcode_render_callback
	 * - parses shortcode arguments
	 * - load template
	 * @param array $attributes array of attributes
	 * @return string return requestform shortcode html view
	 */
	public function shortcode_render_callback( $attributes ){
		$template   = ( isset( $attributes[ 'template' ] ) ) ? $attributes[ 'template' ] : 'default';
		$attributes = shortcode_atts( [
			'template'             => 'default',
			'hide_form_after_send' => false,
			'return_type'          => 'display_msg',
			'return_url'           => null,
			'return_timeout'       => 5000,
			'class_name'           => null,
			'align'                => null
		], $attributes, $this->shortcode_name );

		$attribute_filters = [
			'template'             => function( $a ){ return ( in_array( $a, [ 'default' ] ) ) ? $a : 'default';  }, //TODO : add template a files getter method
			'hide_form_after_send' => function( $a ){ return ( 'true' === $a ) ? true : false; },
			'return_type'          => function( $a ){ return ( in_array( $a, [ 'display_msg', 'redirect' ] ) ) ? $a : 'display_msg'; },
			'return_url'           => function( $a ){ return ( filter_var( $a, FILTER_VALIDATE_URL ) ) ? esc_url_raw( $a ) : null; },
			'return_timeout'       => function( $a ){ return ( is_numeric( $a ) ) ? ( int ) $a : 5000; },
			'class_name'           => function( $a ){ return ( ! empty( $a ) ) ? esc_attr( $a ) : null; },
			'align'                => function( $a ){ return ( in_array( $a, [ 'left', 'center', 'right', 'wide', 'full' ] ) ) ? $a : null; }
		];
		//TODO::filter attributes

		return apply_filters( "yetix_block_{$this->shortcode_name}_{$template}_template", $this->load_template( $template, $attributes ), $attributes );
	}

}