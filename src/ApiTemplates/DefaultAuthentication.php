<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiTemplates;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\SecurityScheme;
use Kekos\PrestDoc\Exceptions\ResolveException;

use const PHP_EOL;

final class DefaultAuthentication implements Contracts\Authentication
{
    public function renderAuthentication(OpenApi $open_api): string
    {
        if (!$open_api->components?->securitySchemes) {
            return '';
        }

        $markdown = <<<MD
## <span id="authentication">Authentication</span>

MD;

        foreach ($open_api->components->securitySchemes as $security_name => $security_scheme) {
            if ($security_scheme instanceof Reference) {
                $security_scheme = $security_scheme->resolve();

                if (!$security_scheme instanceof SecurityScheme) {
                    throw new ResolveException('Could not resolve security scheme');
                }
            }

            $markdown .= $this->getAuthenticationScheme($security_name, $security_scheme);
        }

        return $markdown;
    }

    private function getAuthenticationScheme(string $security_name, SecurityScheme $security_scheme): string
    {
        $markdown = <<<MD
### <span id="authentication_$security_name">$security_scheme->name</span>

MD;

        $markdown .= match ($security_scheme->type) {
            'apiKey' => 'API key',
            'http' => 'HTTP authorization',
            'oauth2' => 'OAuth 2',
            'openIdConnect' => 'OpenId Connect',
            default => 'unknown',
        };

        $markdown .= "\n\nType: *$security_scheme->in*\n\n";

        if ($security_scheme->scheme) {
            $markdown .= "Scheme: *$security_scheme->scheme*\n\n";
        }

        if ($security_scheme->bearerFormat) {
            $markdown .= "Bearer: *$security_scheme->bearerFormat*\n\n";
        }

        if ($security_scheme->openIdConnectUrl) {
            $markdown .= "OpenId Connect URL: *$security_scheme->openIdConnectUrl*\n\n";
        }

        $markdown .= $security_scheme->description . PHP_EOL . PHP_EOL;

        return $markdown;
    }
}
