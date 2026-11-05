<?php

use Nfse\Dto\Nfse\EnderecoData;
use Nfse\Dto\Nfse\TomadorData;
use Nfse\Dto\Nfse\TributacaoData;
use Nfse\Enums\TributacaoIssqn;

it('normalizes named arguments to mapped paths', function () {
    $endereco = new EnderecoData(
        codigoMunicipio: '3550308',
        cep: '01001000',
        logradouro: 'Praça da Sé',
    );

    expect($endereco->codigoMunicipio)->toBe('3550308')
        ->and($endereco->cep)->toBe('01001000')
        ->and($endereco->logradouro)->toBe('Praça da Sé');
});

it('keeps mapped input precedence over original property names', function () {
    $endereco = new EnderecoData([
        'codigoMunicipio' => '0000000',
        'endNac' => [
            'cMun' => '3550308',
        ],
    ]);

    expect($endereco->codigoMunicipio)->toBe('3550308');
});

it('casts nested arrays to their declared DTO types', function () {
    $tomador = new TomadorData([
        'xNome' => 'Cliente Baseline',
        'end' => [
            'endNac' => [
                'cMun' => '3550308',
                'CEP' => '01001000',
            ],
            'xLgr' => 'Praça da Sé',
        ],
    ]);

    expect($tomador->endereco)->toBeInstanceOf(EnderecoData::class)
        ->and($tomador->endereco->codigoMunicipio)->toBe('3550308')
        ->and($tomador->endereco->cep)->toBe('01001000');
});

it('casts numeric strings to int backed enums', function () {
    $tributacao = new TributacaoData([
        'tribMun.tribISSQN' => '2',
    ]);

    expect($tributacao->tributacaoIssqn)->toBe(TributacaoIssqn::Imunidade);
});

it('keeps invalid nullable enum values as null', function () {
    $tributacao = new TributacaoData([
        'tribMun.tribISSQN' => '99',
    ]);

    expect($tributacao->tributacaoIssqn)->toBeNull();
});

it('ignores unknown properties for non strict DTOs', function () {
    $tomador = new TomadorData([
        'xNome' => 'Cliente Baseline',
        'campoFuturo' => 'ignorado',
    ]);

    expect($tomador->nome)->toBe('Cliente Baseline')
        ->and(property_exists($tomador, 'campoFuturo'))->toBeFalse()
        ->and($tomador->toArray())->not->toHaveKey('campoFuturo');
});

it('serializes public property names and nested DTOs recursively', function () {
    $tomador = new TomadorData([
        'xNome' => 'Cliente Baseline',
        'end' => [
            'endNac.cMun' => '3550308',
            'xLgr' => 'Praça da Sé',
        ],
    ]);

    $serialized = $tomador->toArray();

    expect($serialized['nome'])->toBe('Cliente Baseline')
        ->and($serialized['endereco'])->toBeArray()
        ->and($serialized['endereco']['codigoMunicipio'])->toBe('3550308')
        ->and($serialized)->not->toHaveKey('xNome')
        ->and($serialized)->not->toHaveKey('end');
});

it('rejects values incompatible with typed properties', function () {
    expect(fn () => new TomadorData([
        'CPF' => [],
    ]))->toThrow(\TypeError::class);
});
