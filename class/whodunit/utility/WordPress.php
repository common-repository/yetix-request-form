<?php
namespace whodunit\utility;

class WordPress {

	/**
	 */
	static function recursive_wp_parse_args( $a, $b, $replace_by_empty_value = false ) {
		$a      = (array) $a;
		$b      = (array) $b;
		$result = $b;
		foreach( $a as $k => &$v ) {
			if( is_array( $v ) ){
				if( ! isset( $result[ $k ] ) ){ $result[ $k ] = []; }
				$result[ $k ] = self::recursive_wp_parse_args( $v, $result[ $k ] );
			}elseif( $replace_by_empty_value || '' !== $v ){
				$result[ $k ] = $v;
			}
		}
		return $result;
	}

	/**
	 */
	static function recursive_sanitize_text_field( $value ) {
		if( is_string( $value ) ){
			$value = sanitize_text_field( $value );
		}elseif( is_array( $value ) ){
			foreach( $value as &$entry ){
				$entry = self::recursive_sanitize_text_field( $entry );
			}
		}else{
			$value = null;
		}
		return $value;
	}

	/**
	 */
	static function update_localize_script( $handle, $var_name, $localized_data ){
		global $wp_scripts;
		$script_data = $wp_scripts->get_data( $handle, 'data' );

		if( empty( $script_data ) ){
			wp_localize_script( $handle, $var_name, $localized_data);
		}else{
			if( ! is_array( $script_data ) ){
				//TODO::add a json integrity check
				$script_data = json_decode( str_replace('var '.$var_name.' = ', '', substr( $script_data, 0, -1 ) ), true );
			}
			foreach( $script_data as $key => $value ){
				$localized_data[$key] = $value;
			}
			$wp_scripts->add_data( $handle, 'data', '' );
			wp_localize_script( $handle, $var_name, $localized_data );
		}
	}

	static function wordpress_dot_org_trad_exist(){
		return file_exists( WP_LANG_DIR.'/plugins/yetix-request-form-'.determine_locale().'.mo' );
	}

	static function object_error_log( $var ){
		if ( true === WP_DEBUG ) {
			if ( is_array( $var ) || is_object( $var ) ) {
				error_log( print_r( $var, true) );
			} else {
				error_log( $var );
			}
		}
	}

	static function remove_url_arguments( &$object ){
		if ( is_array( $object ) || is_object( $object ) ) {
			foreach ( $object as $prop => &$value ) {
				if( is_array( $value ) || is_object( $value ) ){
					self::remove_url_arguments( $value );
				} elseif( 'url' === $prop ){
					unset( $object->{$prop} );
				}
			}
		}
	}

static function string_to_float_unkwon_local( $num ){
		$dotPos   = strrpos( $num, '.' );
		$commaPos = strrpos( $num, ',' );
		$sep      = ( $dotPos > $commaPos && $dotPos )
			? $dotPos
			: ( ( $commaPos > $dotPos && $commaPos ) ? $commaPos : false );
		if( !$sep ){ return floatval(preg_replace("/[^0-9]/", "", $num)); }
		return floatval(
			preg_replace("/[^0-9]/", "", substr( $num, 0, $sep ) ).'.'
			.preg_replace("/[^0-9]/", "", substr( $num, $sep+1, strlen( $num ) ) )
		);
	}

}