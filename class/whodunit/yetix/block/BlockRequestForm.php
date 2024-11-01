<?php
namespace whodunit\yetix\block;

Use whodunit\utility\WordPress;

class BlockRequestForm extends Block{

	protected $block_name = 'requestform';
	protected $version    = '0.1';
	protected $category   = 'yetix_blocks';

	/**
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'init', [ $this, 'init_registers' ] );
	}

	/**
	 */
	public function init_registers() {

		// register react block and front page script
		wp_register_script(
			'yetix_request__block_'.$this->block_name,
			YETIX_REQUEST__PLUGIN_URL.'assets/js/blocks/'.$this->block_name.'.min.js',
			[ 'yetix_request__common_block_script', 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-editor' ]
		);
		wp_register_script(
			'yetix_request__block_'.$this->block_name.'_script',
			YETIX_REQUEST__PLUGIN_URL.'assets/js/'.$this->block_name.'_script.min.js',
			[ 'jquery', 'wp-i18n', 'wp-tinymce', 'yetix_request__semantic-ui' ]
		);
		
		//register front and editor style
		wp_register_style(
			'yetix_request__block_'.$this->block_name.'_editor_style',
			YETIX_REQUEST__PLUGIN_URL.'assets/css/blocks/'.$this->block_name.'_style-editor.min.css',
			[ 'wp-edit-blocks', 'yetix_request__semantic-ui' ]
		);
		
		wp_register_style(
			'yetix_request__block_'.$this->block_name.'_style',
			YETIX_REQUEST__PLUGIN_URL.'assets/css/blocks/'.$this->block_name.'.min.css',
			[ 'yetix_request__semantic-ui' ]
		);
		

		//register the block
		register_block_type( 'yetix/'.$this->block_name, [
			'editor_script'   => 'yetix_request__block_'.$this->block_name,
			'editor_style'    => 'yetix_request__block_'.$this->block_name.'_editor_style',
			'attributes'      => [
				'template'             => [ 'type' => 'string', 'default' => 'default' ],
				'hide_form_after_send' => [ 'type' => 'boolean', 'default' => false ],
				'return_type'          => [ 'type' => 'string', 'default' => 'display_msg' ],
				'return_url'           => [ 'type' => 'string', 'default' => null ],
				'return_timeout'       => [ 'type' => 'number', 'default' => 5000 ],
				'align'                => [ 'type' => 'string' ]
			],
			'render_callback' => [ $this, 'block_render_callback' ],
		] );

		//register translation
		wp_set_script_translations(
			'yetix_request__block_'.$this->block_name,
			'yetix-request-form',
			( WordPress::wordpress_dot_org_trad_exist() ) ? WP_LANG_DIR.'/plugins/' : YETIX_REQUEST__PLUGIN_DIR.'languages/'
		);
		wp_set_script_translations(
			'yetix_request__block_requestform_script',
			'yetix-request-form',
			( WordPress::wordpress_dot_org_trad_exist() ) ? WP_LANG_DIR.'/plugins/' : YETIX_REQUEST__PLUGIN_DIR.'languages/'
		);
	}

}