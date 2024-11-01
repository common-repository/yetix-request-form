<?php
namespace whodunit\client\zendesk\resources;

use Zendesk\API\Resources\Core\OAuthClients;

/**
 * Class FIX_OAuthClients
 */
class ExtendedOAuthClients extends OAuthClients{
	/**
	 * {@inheritdoc}
	 */
	protected $resourceName = 'oauth/clients';
}
