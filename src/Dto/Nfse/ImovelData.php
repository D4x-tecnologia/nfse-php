<?php

namespace Nfse\Dto\Nfse;

use Nfse\Dto\Dto;
use Nfse\Dto\Attributes\MapFrom;

class ImovelData extends Dto
{
    /**
     * Inscrição imobiliária fiscal fornecida pela Prefeitura Municipal.
     */
    #[MapFrom('inscImobFisc')]
    public ?string $inscricaoImobiliariaFiscal = null;

    /**
     * Código do Cadastro Imobiliário Brasileiro (CIB).
     */
    #[MapFrom('cCIB')]
    public ?string $codigoCib = null;

    /**
     * Endereço do imóvel.
     */
    #[MapFrom('end')]
    public ?EnderecoData $endereco = null;
}
