<?php

namespace Nfse\Dto\Nfse;

use Nfse\Dto\Dto;
use Nfse\Dto\Attributes\MapFrom;

class DpsData extends Dto
{
    #[MapFrom('@attributes.versao')]
    public ?string $versao = null;

    #[MapFrom('infDPS')]
    public ?InfDpsData $infDps = null;
}
