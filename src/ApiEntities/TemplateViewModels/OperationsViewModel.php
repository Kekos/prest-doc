<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities\TemplateViewModels;

use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\SecurityRequirement;
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
     * @throws UnresolvableReferenceException
     */
    public function getOperations(OpenApi $open_api, TopicGroup $topic_group): array
    {
        $server_url = $this->getServerUrl($open_api);

        $operation_models = [];

        foreach ($topic_group->topics as $path => $path_item) {
            foreach ($path_item->getOperations() as $method => $operation) {
                $security_requirements = $this->getApplicableOperationSecurity($open_api, $operation);

                $operation_models[] = new TopicOperationViewModel(
                    $server_url,
                    $path,
                    $method,
                    $operation,
                    $security_requirements,
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
     */
    public function getAuthExamples(TopicOperationViewModel $operation_view_model): array
    {
        if (!$operation_view_model->security_schemes) {
            return [];
        }

        $security_requirements = $operation_view_model->security_requirements->all();

        $examples = [];

        foreach ($security_requirements as $security_scheme) {
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
     * @throws UnresolvableReferenceException
     */
    private function getApplicableOperationSecurity(OpenApi $open_api, Operation $operation): OperationSecurity
    {
        $security_schemes = $open_api->components?->securitySchemes ?? [];
        if (!$security_schemes) {
            return new OperationSecurity([], []);
        }

        $local_requirements = [];
        $global_requirements = [];

        if (isset($operation->security)) {
            /** @var SecurityRequirements $operation_security */
            $operation_security = $operation->security;

            $local_requirements = $this->resolveSecurityRequirementsToSchemes($security_schemes, $operation_security->getRequirements());
        }

        if (isset($open_api->security)) {
            /** @var SecurityRequirements $global_security */
            $global_security = $open_api->security;

            $global_requirements = $this->resolveSecurityRequirementsToSchemes($security_schemes, $global_security->getRequirements());
        }

        return new OperationSecurity($local_requirements, $global_requirements);
    }

    /**
     * @param array<string, SecurityScheme|Reference> $security_schemes
     * @param array<string, SecurityRequirement|string> $security_requirements
     * @return array<string, SecurityScheme>
     * @throws UnresolvableReferenceException
     */
    private function resolveSecurityRequirementsToSchemes(array $security_schemes, array $security_requirements): array
    {
        $resolved_schemes = [];
        foreach ($security_requirements as $security_name => $requirement) {
            if (!is_string($security_name)) {
                continue;
            }

            if (!isset($security_schemes[$security_name])) {
                continue;
            }

            $security_scheme = $security_schemes[$security_name];

            if ($security_scheme instanceof Reference) {
                $security_scheme = $security_scheme->resolve();

                if (!$security_scheme instanceof SecurityScheme) {
                    throw new ResolveException(
                        sprintf('Could not resolve security scheme for `%s`', $security_name)
                    );
                }
            }

            $resolved_schemes[$security_name] = $security_scheme;
        }

        return $resolved_schemes;
    }
}
