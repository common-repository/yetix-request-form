<?php
namespace whodunit\client\zendesk;

use whodunit\client\zendesk\resources\ExtendedOAuthTokens;
use whodunit\yetix\Plugin;
use whodunit\client\zendesk\resources\ExtendedOAuthClients;
use Zendesk\API\HttpClient;
use Zendesk\API\Exceptions\ApiResponseException;
use Zendesk\API\Exceptions\AuthException;
use Zendesk\API\Resources\Core\Activities;
use Zendesk\API\Resources\Core\AppInstallations;
use Zendesk\API\Resources\Core\Apps;
use Zendesk\API\Resources\Core\Attachments;
use Zendesk\API\Resources\Core\AuditLogs;
use Zendesk\API\Resources\Core\Autocomplete;
use Zendesk\API\Resources\Core\Automations;
use Zendesk\API\Resources\Core\Bookmarks;
use Zendesk\API\Resources\Core\Brands;
use Zendesk\API\Resources\Core\CustomRoles;
use Zendesk\API\Resources\Core\DynamicContent;
use Zendesk\API\Resources\Core\GroupMemberships;
use Zendesk\API\Resources\Core\Groups;
use Zendesk\API\Resources\Core\Incremental;
use Zendesk\API\Resources\Core\JobStatuses;
use Zendesk\API\Resources\Core\Locales;
use Zendesk\API\Resources\Core\Macros;
use Zendesk\API\Resources\Core\OAuthTokens;
use Zendesk\API\Resources\Core\OrganizationFields;
use Zendesk\API\Resources\Core\OrganizationMemberships;
use Zendesk\API\Resources\Core\Organizations;
use Zendesk\API\Resources\Core\OrganizationSubscriptions;
use Zendesk\API\Resources\Core\PushNotificationDevices;
use Zendesk\API\Resources\Core\Requests;
use Zendesk\API\Resources\Core\SatisfactionRatings;
use Zendesk\API\Resources\Core\Search;
use Zendesk\API\Resources\Core\Sessions;
use Zendesk\API\Resources\Core\SharingAgreements;
use Zendesk\API\Resources\Core\SlaPolicies;
use Zendesk\API\Resources\Core\SupportAddresses;
use Zendesk\API\Resources\Core\SuspendedTickets;
use Zendesk\API\Resources\Core\Tags;
use Zendesk\API\Resources\Core\Targets;
use Zendesk\API\Resources\Core\TicketFields;
use Zendesk\API\Resources\Core\TicketImports;
use Zendesk\API\Resources\Core\Tickets;
use Zendesk\API\Resources\Core\Triggers;
use Zendesk\API\Resources\Core\TwitterHandles;
use Zendesk\API\Resources\Core\UserFields;
use Zendesk\API\Resources\Core\Users;
use Zendesk\API\Resources\Core\Views;
use \WP_Error;
/**
 * @method ExtendedOAuthClients oauthClients($id = null)
 * @method ExtendedOAuthTokens oauthTokens($id = null)
 **/
class ZendeskApiClient extends HttpClient{

	public const STATUS_NOT_CONNECTED = 0;
	public const STATUS_CONNECTED     = 1;
	public const STATUS_ERRORS        = 2;
	protected    $connection_status   = 0;
	protected    $error               = [];
	protected    $errors              = [];

	/**
	 * Constructor
	 **/
	public function __construct( $subdomain ) {
		$username = '';
        $scheme   = "https";
        $hostname = "zendesk.com";
        $port     = 443;
        $guzzle   = null;
        $this->set_errors( [
        	'Zendesk\API\Exceptions\ApiResponseException' => function( ApiResponseException $e ){
        		//TODO::error detail can be json object or string check that
				//$error_details = json_decode( $e->getErrorDetails() );
        		return [
					'code'    => $e->getCode(),
					'message' => $e->getErrorDetails(),
				];
			},
			'Zendesk\API\Exceptions\AuthException' => function( AuthException $e ){
				return [
					'code'    => $e->getCode(),
					'message' => $e->getMessage(),
				];
			},
			'no_subdomain'                                => [
				'code'    => 'no_subdomain',
				'message' => __( 'subdomain not defined', 'yetix-request-form' ),
			],
			'unknown_error'                               => [
				'code'    => 'unknown_error',
				'message' => __( 'Unknown error', 'yetix-request-form' ),
			],
			'unverified_user'                             => [
				'code'    => 'unverified_user',
				'message' => __( 'Invalid user', 'yetix-request-form' ),
			],
			'insufficient_rights'                         => [
				'code'    => 'insufficient_rights',
				'message' => __( 'Insufficient user rights', 'yetix-request-form' ),
			],
		] );
        parent::__construct( $subdomain, $username, $scheme, $hostname, $port, $guzzle );
	}

	public static function getValidSubResources()
	{
		return [
			'apps'                      => Apps::class,
			'activities'                => Activities::class,
			'appInstallations'          => AppInstallations::class,
			'attachments'               => Attachments::class,
			'auditLogs'                 => AuditLogs::class,
			'autocomplete'              => Autocomplete::class,
			'automations'               => Automations::class,
			'bookmarks'                 => Bookmarks::class,
			'brands'                    => Brands::class,
			'customRoles'               => CustomRoles::class,
			'dynamicContent'            => DynamicContent::class,
			'groupMemberships'          => GroupMemberships::class,
			'groups'                    => Groups::class,
			'incremental'               => Incremental::class,
			'jobStatuses'               => JobStatuses::class,
			'locales'                   => Locales::class,
			'macros'                    => Macros::class,
			'oauthClients'              => ExtendedOAuthClients::class,
			'oauthTokens'               => ExtendedOAuthTokens::class,
			'organizationFields'        => OrganizationFields::class,
			'organizationMemberships'   => OrganizationMemberships::class,
			'organizations'             => Organizations::class,
			'organizationSubscriptions' => OrganizationSubscriptions::class,
			'pushNotificationDevices'   => PushNotificationDevices::class,
			'requests'                  => Requests::class,
			'satisfactionRatings'       => SatisfactionRatings::class,
			'sharingAgreements'         => SharingAgreements::class,
			'search'                    => Search::class,
			'slaPolicies'               => SlaPolicies::class,
			'sessions'                  => Sessions::class,
			'supportAddresses'          => SupportAddresses::class,
			'suspendedTickets'          => SuspendedTickets::class,
			'tags'                      => Tags::class,
			'targets'                   => Targets::class,
			'tickets'                   => Tickets::class,
			'ticketFields'              => TicketFields::class,
			'ticketImports'             => TicketImports::class,
			'triggers'                  => Triggers::class,
			'twitterHandles'            => TwitterHandles::class,
			'userFields'                => UserFields::class,
			'users'                     => Users::class,
			'views'                     => Views::class,
		];
	}

	public function __call( $name, $arguments ){
		try{
			$class = parent::__call( $name, $arguments );
		}catch( Exception $e ){
			$this->process_exceptions( $e );
		}
		return $class;
	}

	protected function set_errors( $errors ){
		$this->errors = $errors;
	}

	public function authenticate_end_user( $mail, $password ){
		try{
			$this->setAuth('password', [ 'username' => $mail, 'password' => $password ] );
			$this->check_status();
		}catch( \Exception $e ){
			$this->process_exceptions( $e );
		}
	}

	public function authenticate_oauth_user( $oauth_token ){
		try{
			$this->setAuth('oauth', [ 'token' => $oauth_token ] );
			$this->check_status();
		}catch( \Exception $e ){
			$this->process_exceptions( $e );
		}
	}

	public function authenticate_administrator( $mail, $token ){
		try{
			$this->setAuth('basic', [ 'username' => $mail, 'token' => $token ] );
			$this->check_status();
			if( self::STATUS_CONNECTED === $this->connection_status ){
				$last_domain_connection = get_option( 'yetix_request__last_token_connection_subdomain', false );
				if( $last_domain_connection !== $this->subdomain ){
					update_option( 'yetix_request__last_token_connection_subdomain', $this->subdomain );
					Plugin::getInstance()->init_default_options();
				}
			}
		}catch( \Exception $e ){
			$this->process_exceptions( $e );
		}
	}

	public function check_status(){
		//check if domain is defined
		if( empty( $this->subdomain ) ){
			$this->connection_status = self::STATUS_ERRORS;
			$this->error = $this->errors[ 'no_subdomain' ];
			return $this->connection_status;
		}

		//try connection
		try {
			$me = $this->users()->me();
		}catch( \Exception $e ){
			$this->process_exceptions( $e );
			return $this->connection_status;
		}

		//if user is null, there is no connection
		if( is_null( $me ) ){
			return $this->connection_status;
		}

		//check if user is verified
		if( false === $me->user->verified ){
			$this->connection_status = self::STATUS_ERRORS;
			$this->error = $this->errors[ 'unverified_user' ];
			return $this->connection_status;
		}

		//check if user has sufficient rights
		if( 'password' !== $this->auth->get_strategy() && 'end_user' === $me->user->role ){
			$this->connection_status = self::STATUS_ERRORS;
			$this->error = $this->errors[ 'insufficient_rights' ];
			return $this->connection_status;
		}

		$this->connection_status = self::STATUS_CONNECTED;
		return $this->connection_status;
	}

	public function get_status(){
		return $this->connection_status;
	}

	protected function process_exceptions( \Exception $e  ){
		$this->connection_status = self::STATUS_ERRORS;
		if( isset( $this->errors[ get_class( $e ) ] ) && is_callable( $this->errors[ get_class( $e ) ] ) ){
			$this->error = $this->errors[ get_class( $e ) ]( $e );
		}else{
			$this->error = $this->errors[ 'unknown_error' ];
		}
	}

	public function get_last_error(){
		return ( isset( $this->error ) ) ? $this->error : null;
	}

	public function setAuth( $strategy, array $options ){
		$this->auth = new ExtendedAuth( $strategy, $options );
	}

	/**
	 * This is a helper method to do a get request.
	 *
	 * @param       $endpoint
	 * @param array $queryParams
	 *
	 * @return \stdClass | null
	 * @throws \Zendesk\API\Exceptions\AuthException
	 * @throws \Zendesk\API\Exceptions\ApiResponseException
	 */
	public function get( $endpoint, $queryParams = [] ){
		$sideloads = $this->getSideload($queryParams);
		if ( is_array( $sideloads ) ){
			$queryParams[ 'include' ] = implode( ',', $sideloads );
			unset( $queryParams[ 'sideload' ] );
		}
		try {
			$response = Http::send(
				$this,
				$endpoint,
				['queryParams' => $queryParams]
			);
		}catch( \Exception $e ){
			//$e->getMessage();
			$response = null;
		}
		return $response;
	}

	/**
	 * This is a helper method to do a post request.
	 *
	 * @param       $endpoint
	 * @param array $postData
	 *
	 * @param array $options
	 * @return null|\stdClass
	 * @throws \Zendesk\API\Exceptions\AuthException
	 * @throws \Zendesk\API\Exceptions\ApiResponseException
	 */
	public function post( $endpoint, $postData = [], $options = [] ){
		$extraOptions = array_merge($options, [
			'postFields' => $postData,
			'method'     => 'POST'
		]);
		$response     = Http::send(
			$this,
			$endpoint,
			$extraOptions
		);
		return $response;
	}

	/**
	 * This is a helper method to do a put request.
	 *
	 * @param       $endpoint
	 * @param array $putData
	 *
	 * @return \stdClass | null
	 * @throws \Zendesk\API\Exceptions\AuthException
	 * @throws \Zendesk\API\Exceptions\ApiResponseException
	 */
	public function put( $endpoint, $putData = [] ){
		$response = Http::send(
			$this,
			$endpoint,
			['postFields' => $putData, 'method' => 'PUT']
		);
		return $response;
	}

	/**
	 * This is a helper method to do a delete request.
	 *
	 * @param $endpoint
	 *
	 * @return null
	 * @throws \Zendesk\API\Exceptions\AuthException
	 * @throws \Zendesk\API\Exceptions\ApiResponseException
	 */
	public function delete( $endpoint ){
		$response = Http::send(
			$this,
			$endpoint,
			['method' => 'DELETE']
		);
		return $response;
	}

}
