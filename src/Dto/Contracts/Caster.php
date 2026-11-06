<?php

namespace Nfse\Dto\Contracts;

interface Caster
{
    public function cast(mixed $value): mixed;
}
