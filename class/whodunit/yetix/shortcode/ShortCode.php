<?php
//<namespace::begin>
namespace whodunit\yetix\shortcode;
//<namespace::end>

/**
 * Abstract ShortCode class
 * define base feature of all Yetix shortcode
 */
abstract class ShortCode{

	protected $shortcode_name;
	protected $version;

	/**
	 * Constructor
	 * @param null|string $shortcode_name determine view filename and shortcode name
	 * @param null|string $vesion not in use
	 */
	public function __construct( $shortcode_name = null, $version = null ) {
		if( ! empty( $shortcode_name ) && is_string( $shortcode_name ) ){ $this->shortcode_name = sanitize_title( $shortcode_name ); }
		if( ! empty( $version ) && is_string( $version ) ){ $this->version = $version; }
		add_shortcode( $this->shortcode_name, [ $this, 'shortcode_render_callback' ] );
	}

	/**
	 * shortcode_render_callback
	 * overwrite that method
	 */
	public function shortcode_render_callback( $attributes ){}

	/**
	 * load_template
	 * template loader for shortcode seach into themefolder and fallback to plugin default view
	 * @param $file_name template filename, do not give the file extension, this will search for <$file_name>.php only
	 * @param array $attributes an array of attributes to pass to the template, attributes are extracted
	 * @param bool $echo flag echo if true else result will be return, false by default
	 * @return string return html template, nothing if flag is true or if the template file can't be found
	 */
	public function load_template( $file_name = 'default', $attributes = [], $echo = false ){
		if( false === $echo ){ ob_start(); }
		$theme_file = get_stylesheet_directory().'/template_yetix/shortcode/'.$this->shortcode_name.'/'.$file_name.'.php';
		$block_file = YETIX_REQUEST__PLUGIN_DIR.'/views/shortcodes/'.$this->shortcode_name.'/'.$file_name.'.php';
		$file = ( file_exists( $theme_file ) ) ? $theme_file : $block_file ;
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