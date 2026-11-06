<?php

namespace Nfse\Dto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class MapFrom
{
    public function __construct(public string $name) {}
}
