<?php declare(strict_types=1);

namespace Kekos\PrestDoc\ApiEntities\TemplateViewModels;

use cebe\openapi\spec\SecurityScheme;

/**
 * This model represents two lists of security requirements.
 * Only one of the arrays needs to be satisfied to authorize a request to the operation (OR).
 * Within each array, all requirements must be satisfied (AND).
 */
final class OperationSecurity
{
    /**
     * @param array<string, SecurityScheme> $requirements_local
     * @param array<string, SecurityScheme> $requirements_global
     */
    public function __construct(
        public readonly array $requirements_local,
        public readonly array $requirements_global,
    ) {
    }

    public function hasAny(): bool
    {
        return $this->requirements_local || $this->requirements_global;
    }

    /**
     * @return array<string, SecurityScheme>
     */
    public function all(): array
    {
        return $this->requirements_local + $this->requirements_global;
    }
}
