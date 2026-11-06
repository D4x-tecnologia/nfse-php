<?php

namespace Nfse\Dto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class CastWith
{
    /** @param array<string, mixed> $arguments */
    public array $arguments;

    public function __construct(public string $caster, mixed ...$arguments)
    {
        $this->arguments = $arguments;
    }
}
