<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities\TemplateViewModels;

use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\SecurityRequirements;
use cebe\openapi\spec\SecurityScheme;
use cebe\openapi\spec\Server;
use InvalidArgumentException;
use Kekos\PrestDoc\ApiEntities\TopicGroup;
use Kekos\PrestDoc\Exceptions\ResolveException;

use function array_reduce;
use function is_string;
use function parse_url;
use function sprintf;

use const PHP_URL_HOST;

final class OperationsViewModel
{
    /**
     * @return TopicOperationViewModel[]
     */
    public function getOperations(OpenApi $open_api, TopicGroup $topic_group): array
    {
        $server_url = $this->getServerUrl($open_api);

        $operation_models = [];

        foreach ($topic_group->topics as $path => $path_item) {
            foreach ($path_item->getOperations() as $method => $operation) {
                $operation_models[] = new TopicOperationViewModel(
                    $server_url,
                    $path,
                    $method,
                    $operation,
                    $open_api->components?->securitySchemes ?? [],
                );
            }
        }

        return $operation_models;
    }

    public function getServerUrl(OpenApi $open_api): string
    {
        $server_url = array_reduce(
            $open_api->servers,
            static fn($carry, Server $server) => $server->url,
            'http://localhost',
        );

        $url_host = parse_url($server_url, PHP_URL_HOST);

        if (!is_string($url_host)) {
            throw new InvalidArgumentException('Could not resolve server url');
        }

        return $url_host;
    }

    /**
     * @return non-empty-array<int, string|null>
     */
    public function getConsumers(Operation $operation): array
    {
        if (!($operation->requestBody instanceof RequestBody)) {
            return [null];
        }

        $consumers = [];
        foreach ($operation->requestBody->content as $content_type => $content) {
            if (is_string($content_type)) {
                $consumers[] = $content_type;
            }
        }

        if (!$consumers) {
            $consumers[] = null;
        }

        return $consumers;
    }

    /**
     * @return array<string, string>
     * @throws UnresolvableReferenceException
     */
    public function getAuthExamples(Operation $operation, array $security_schemes): array
    {
        if (!$security_schemes) {
            return [];
        }

        $one_of_required_security = $this->getRequiredAuth($operation, $security_schemes);
        if (!$one_of_required_security) {
            return [];
        }

        $examples = [];

        foreach ($security_schemes as $name => $security_scheme) {
            if (!isset($one_of_required_security[$name])) {
                continue;
            }

            if ($security_scheme instanceof Reference) {
                $security_scheme = $security_scheme->resolve();

                if (!$security_scheme instanceof SecurityScheme) {
                    throw new ResolveException('Could not resolve security scheme');
                }
            }

            switch ($security_scheme->type) {
                case 'apiKey':
                    if ($security_scheme->in === 'header') {
                        $examples[$security_scheme->name] = 'API_KEY';
                    }

                    break;
                case 'http':
                    if ($security_scheme->in === 'header') {
                        $examples[$security_scheme->name] = sprintf('%s {access-token}', $security_scheme->scheme);
                    }

                    break;
                case 'openIdConnect':
                case 'oauth2':
                    $examples['Authorization'] = 'Bearer {access-token}';
                    break;
            }
        }

        return $examples;
    }

    /**
     * @param Reference[]|SecurityScheme[] $security_schemes
     * @return array<int, array<string, SecurityScheme>>
     * @throws UnresolvableReferenceException
     */
    public function getRequiredAuth(Operation $operation, array $security_schemes): array
    {
        if (!isset($operation->security)) {
            return [];
        }

        /** @var SecurityRequirements $operation_security */
        $operation_security = $operation->security;
        $one_of_required_security = [];

        foreach ($operation_security->getRequirements() as $security_requirement) {
            $all_of_required_security = [];
            // All schemes in this loop are "OR"
            foreach ($security_schemes as $security_name => $security_scheme) {
                if (!is_string($security_name)) {
                    continue;
                }

                if ($security_scheme instanceof Reference) {
                    $security_scheme = $security_scheme->resolve();

                    if (!$security_scheme instanceof SecurityScheme) {
                        throw new ResolveException(
                            sprintf('Could not resolve security scheme for `%s`', $security_name)
                        );
                    }
                }

                // All schemes in this loop are "AND"
                if (isset($security_requirement->$security_name)) {
                    $all_of_required_security[$security_name] = $security_scheme;
                }
            }

            $one_of_required_security[] = $all_of_required_security;
        }

        return $one_of_required_security;
    }
}
