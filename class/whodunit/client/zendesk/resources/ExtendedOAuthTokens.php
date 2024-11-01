<?php
namespace whodunit\client\zendesk\resources;

use Zendesk\API\Resources\Core\OAuthTokens;
use Zendesk\API\Traits\Resource\Create;

/**
 * Class ExtendedOAuthTokens
 */
class ExtendedOAuthTokens extends OAuthTokens{
    use Create;
}
