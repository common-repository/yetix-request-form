<?php
namespace whodunit\client\zendesk;

use Psr\Http\Message\RequestInterface;
use Zendesk\API\Exceptions\AuthException;
use Zendesk\API\Utilities\Auth;

/**
 * Class Auth
 * This helper would manage all Authentication related operations.
 */
class ExtendedAuth extends Auth{
    const OAUTH    = 'oauth';
    const BASIC    = 'basic';
	const PASSWORD = 'password';

    /**
     * Returns an array containing the valid auth strategies
     * @return array
     */
    protected static function getValidAuthStrategies(){
        return [self::BASIC, self::OAUTH, self::PASSWORD ];
    }

    /**
     * Auth constructor.
     *
     * @param       $strategy
     * @param array $options
     *
     * @throws AuthException
     *
     */
    public function __construct( $strategy, array $options ){
        if( ! in_array( $strategy, self::getValidAuthStrategies() ) ){
            throw new AuthException(
            	sprintf(
					//translators: argument 1 is opening code tag  , argument 1 is a list of valid authentication strategies separated by , argument 2 is a closing code tag
					__( 'Invalid auth strategy set, please use %1$s %2$s %3$s', 'yetix-request-form' ),
					'<code>',
					implode( ', ', self::getValidAuthStrategies(),
					'</code>'
					)
				)
			);
        }

        $this->authStrategy = $strategy;
        if( $strategy == self::BASIC ){
            if( ! array_key_exists('username', $options ) || empty( $options[ 'username' ] ) ){
				//translators: argument 1 is a opening code tag, argument 2 is a closing code tag
                throw new AuthException( sprintf( __( 'Please provide %1$susername%2$s for basic auth.', 'yetix-request-form' ), '<code>', '</code>' ) );
            }
			if( ! array_key_exists('token', $options ) || empty( $options[ 'token' ] ) ){
				//translators: argument 1 is a opening code tag, argument 2 is a closing code tag
				throw new AuthException( sprintf( __( 'Please provide %1$stoken%2$s for basic auth.', 'yetix-request-form' ), '<code>', '</code>' ) );
			}
        }elseif( $strategy == self::PASSWORD ){
			if( ! array_key_exists('username', $options ) || empty( $options[ 'username' ] ) ){
				//translators: argument 1 is a opening code tag, argument 2 is a closing code tag
				throw new AuthException( sprintf( __( 'Please provide %1$susername%2$s for password auth.', 'yetix-request-form' ), '<code>', '</code>' ) );
			}
			if( ! array_key_exists('password', $options ) || empty( $options[ 'password' ] ) ){
				//translators: argument 1 is a opening code tag, argument 2 is a closing code tag
				throw new AuthException( sprintf( __( 'Please provide %1$spassword%2$s for password auth.', 'yetix-request-form' ), '<code>', '</code>' ));
			}
		}elseif( $strategy == self::OAUTH ){
            if( ! array_key_exists( 'token', $options ) || empty( $options[ 'token' ] ) ){
				//translators: argument 1 is a opening code tag, argument 2 is a closing code tag
                throw new AuthException( sprintf( __( 'Please provide %1$stoken%2$s for oauth.', 'yetix-request-form' ), '<code>', '</code>' ) );
            }
        }

        $this->authOptions = $options;
    }

    /**
     * @param RequestInterface $request
     * @param array            $requestOptions
     *
     * @return array
     * @throws AuthException
     */
    public function prepareRequest( RequestInterface $request, array $requestOptions = [] ){
        if( $this->authStrategy === self::BASIC ){
            $requestOptions = array_merge( $requestOptions, [
                'auth' => [
                    $this->authOptions[ 'username' ].'/token',
                    $this->authOptions[ 'token' ],
                    'basic'
                ]
            ] );
        }elseif($this->authStrategy === self::PASSWORD) {
			$requestOptions = array_merge( $requestOptions, [
				'auth' => [ $this->authOptions[ 'username' ], $this->authOptions[ 'password' ], 'basic' ]
			]);
        }elseif($this->authStrategy === self::OAUTH) {
			$oAuthToken = $this->authOptions[ 'token' ];
			$request    = $request->withAddedHeader( 'Authorization', ' Bearer ' . $oAuthToken );
		}else{
            throw new AuthException( __( 'Please set authentication to send requests.', 'yetix-request-form' ) );
        }
        return [ $request, $requestOptions ];
    }

    public function get_strategy(){
    	return $this->authStrategy;
	}
}
