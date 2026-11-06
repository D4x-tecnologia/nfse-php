<?php

use Nfse\Dto\Nfse\DeducaoReducaoData;
use Nfse\Dto\Nfse\DocumentoDeducaoData;
use Nfse\Dto\Nfse\DpsData;
use Nfse\Dto\Nfse\EnderecoData;
use Nfse\Dto\Nfse\NfseData;
use Nfse\Dto\Nfse\TomadorData;
use Nfse\Xml\NfseXmlParser;

it('casts a single associative fiscal document and a list into typed DTO collections', function () {
    $single = new DeducaoReducaoData([
        'documentos' => [
            'chNFSe' => 'NFS00000000000000000000000000000000000000000000000000',
            'vDeducaoReducao' => 125.50,
        ],
    ]);

    $multiple = new DeducaoReducaoData([
        'documentos' => [
            ['chNFSe' => 'NFS00000000000000000000000000000000000000000000000001'],
            ['chNFe' => 'NFE00000000000000000000000000000000000000000000000002'],
        ],
    ]);

    expect($single->documentos)->toHaveCount(1)
        ->and($single->documentos[0])->toBeInstanceOf(DocumentoDeducaoData::class)
        ->and($single->documentos[0]->chaveNfse)->toBe('NFS00000000000000000000000000000000000000000000000000')
        ->and($multiple->documentos)->toHaveCount(2)
        ->and($multiple->documentos[0])->toBeInstanceOf(DocumentoDeducaoData::class)
        ->and($multiple->documentos[1])->toBeInstanceOf(DocumentoDeducaoData::class)
        ->and($multiple->documentos[1]->chaveNfe)->toBe('NFE00000000000000000000000000000000000000000000000002');
});

it('preserves empty, missing, and explicitly null nullable document collections', function () {
    $empty = new DeducaoReducaoData(['documentos' => []]);
    $missing = new DeducaoReducaoData([]);
    $explicitNull = new DeducaoReducaoData(['documentos' => null]);

    expect($empty->documentos)->toBe([])
        ->and($missing->documentos)->toBeNull()
        ->and($explicitNull->documentos)->toBeNull();
});

it('leaves an already-instantiated collection item as the same typed DTO', function () {
    $documento = new DocumentoDeducaoData(['chNFSe' => 'NFS00000000000000000000000000000000000000000000000003']);

    $deducao = new DeducaoReducaoData(['documentos' => [$documento]]);

    expect($deducao->documentos[0])->toBe($documento)
        ->and($deducao->documentos[0])->toBeInstanceOf(DocumentoDeducaoData::class);
});

it('constructs a typed DTO list with arrayOf while preserving mapped input', function () {
    $tomadores = TomadorData::arrayOf([
        ['xNome' => 'Tomador A', 'CPF' => '11111111111'],
        ['xNome' => 'Tomador B', 'CNPJ' => '11111111000111'],
    ]);

    expect($tomadores)->toHaveCount(2)
        ->and($tomadores[0])->toBeInstanceOf(TomadorData::class)
        ->and($tomadores[0]->nome)->toBe('Tomador A')
        ->and($tomadores[0]->cpf)->toBe('11111111111')
        ->and($tomadores[1]->nome)->toBe('Tomador B')
        ->and($tomadores[1]->cnpj)->toBe('11111111000111');
});

it('returns public DTO state from all and applies only and except to serialized copies', function () {
    $tomador = new TomadorData([
        'xNome' => 'Cliente Baseline',
        'CPF' => '11111111111',
        'email' => 'cliente@example.test',
    ]);

    $all = $tomador->all();
    $only = $tomador->only('nome', 'email');
    $except = $tomador->except('cpf');

    expect($all)->toHaveKey('nome', 'Cliente Baseline')
        ->and($all)->toHaveKey('cpf', '11111111111')
        ->and($all['endereco'])->toBeNull()
        ->and($only->toArray())->toBe([
            'nome' => 'Cliente Baseline',
            'email' => 'cliente@example.test',
        ])
        ->and($except->toArray())->not->toHaveKey('cpf')
        ->and($except->toArray())->toHaveKey('nome', 'Cliente Baseline')
        ->and($tomador->toArray())->toHaveKey('cpf', '11111111111');
});

it('creates an independent DTO through clone and applies mapped overrides', function () {
    $original = new TomadorData(['xNome' => 'Original', 'CPF' => '11111111111']);
    $copy = $original->clone(nome: 'Cópia');

    expect($copy)->toBeInstanceOf(TomadorData::class)
        ->and($copy)->not->toBe($original)
        ->and($copy->nome)->toBe('Cópia')
        ->and($copy->cpf)->toBe('11111111111')
        ->and($original->nome)->toBe('Original');
});

it('accepts a nested DTO instance as input without rebuilding it', function () {
    $endereco = new EnderecoData([
        'endNac.cMun' => '1505486',
        'xLgr' => 'Rua de teste',
    ]);

    $tomador = new TomadorData([
        'xNome' => 'Cliente Baseline',
        'end' => $endereco,
    ]);

    expect($tomador->endereco)->toBe($endereco)
        ->and($tomador->endereco->codigoMunicipio)->toBe('1505486');
});

it('maps root and nested fiscal XML attributes and defaults absent or null values consistently', function () {
    $dps = new DpsData([
        '@attributes' => ['versao' => '1.00'],
        'infDPS' => [
            '@attributes' => ['Id' => 'DPS150548623130519900019000900000000000000003'],
            'nDPS' => '3',
            'tpAmb' => null,
        ],
    ]);

    $missing = new DpsData([]);
    $explicitNull = new DpsData(['@attributes' => ['versao' => null], 'infDPS' => null]);

    expect($dps->versao)->toBe('1.00')
        ->and($dps->infDps->id)->toBe('DPS150548623130519900019000900000000000000003')
        ->and($dps->infDps->numeroDps)->toBe('3')
        ->and($dps->infDps->tipoAmbiente)->toBeNull()
        ->and($missing->versao)->toBeNull()
        ->and($missing->infDps)->toBeNull()
        ->and($explicitNull->versao)->toBeNull()
        ->and($explicitNull->infDps)->toBeNull();
});

it('retains typed nested values and missing fiscal data when parsing the NFS-e XML fixture', function () {
    $xml = file_get_contents(dirname(__DIR__, 2).'/fixtures/nfse.xml');
    $nfse = (new NfseXmlParser)->parse($xml);

    expect($nfse)->toBeInstanceOf(NfseData::class)
        ->and($nfse->versao)->toBe('1.00')
        ->and($nfse->infNfse->id)->toBe('NFS15054862231305199000190000000000000225121645026250')
        ->and($nfse->infNfse->codigoVerificacao)->toBeNull()
        ->and($nfse->infNfse->dps->infDps->id)->toBe('DPS150548623130519900019000900000000000000003')
        ->and($nfse->infNfse->dps->infDps->tomador)->toBeInstanceOf(TomadorData::class)
        ->and($nfse->infNfse->dps->infDps->tomador->cpf)->toBe('76920500230')
        ->and($nfse->nfseXml)->toBe($xml);
});

it('maps attributes and nested typed DTOs from the official fiscal DPS XML example', function () {
    $xml = simplexml_load_file(dirname(__DIR__, 3).'/references/dps_example.xml');

    expect($xml)->not->toBeFalse();

    $input = json_decode(json_encode($xml, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    $dps = new DpsData($input);

    expect($dps->versao)->toBe('1.00')
        ->and($dps->infDps->id)->toBe('DPS150548624482769200011100900000000000000031')
        ->and($dps->infDps->numeroDps)->toBe('31')
        ->and($dps->infDps->tomador)->toBeInstanceOf(TomadorData::class)
        ->and($dps->infDps->tomador->endereco)->toBeInstanceOf(EnderecoData::class)
        ->and($dps->infDps->tomador->endereco->codigoMunicipio)->toBe('1505486');
});
