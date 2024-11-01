<?php
namespace whodunit\yetix\block;

abstract class Block{

	protected $block_name;
	protected $version;
	protected $categories;
	protected $category   = null;

	/**
	 */
	public function __construct(  ) {
		$this->categories = [
			'yetix_blocks' => [
				'slug'  => 'yetix_blocks',
				'title' => __( 'Yetix', 'yetix-request-form' ),
				'icon'  => null,
			],
		];
		add_filter( 'block_categories_all', [ $this, 'add_category' ], 10, 2 );
		add_action( 'admin_init',           function(){
			wp_register_script( 'yetix_request__common_block_script', YETIX_REQUEST__PLUGIN_URL.'/assets/js/blocks/common.min.js', [ 'wp-blocks' ] );
		} );
	}

	/**
	 */
	public function add_category( $categories, $context ) {
		if( is_null( $this->category ) ){ return $categories; }
		if( ! isset( $this->categories[ $this->category ] ) ){ return $categories; }
		$categories_slugs = array_map( function( $e ){ return $e[ 'slug' ]; }, $categories );
		if( ! in_array( $this->category, $categories_slugs ) ){
			$categories = array_merge( $categories, [ $this->categories[ $this->category ] ] );
			return $categories;
		}
		return $categories;
	}

	/**
	 */
	public function block_render_callback( $attributes, $content = null ){
		$template                = ( isset( $attributes[ 'template' ] ) ) ? $attributes[ 'template' ] : 'default';
		$attributes[ 'content' ] = $content;
		return apply_filters( "yetix_block_{$this->block_name}_{$template}_template", $this->load_template( $template, $attributes ), $attributes );
	}

	/**
	 */
	public function load_template( $file_name, $attributes = [], $echo = false ){
		if( false === $echo ){ ob_start(); }
		$theme_file = get_stylesheet_directory().'/template_yetix/blocks/'.$this->block_name.'/'.$file_name.'.php';
		$block_file = YETIX_REQUEST__PLUGIN_DIR.'/views/blocks/'.$this->block_name.'/'.$file_name.'.php';
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