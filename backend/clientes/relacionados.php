<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function clienteRelacionadoConfig(string $tipo): ?array
{
    return match ($tipo) {
        'endereco' => [
            'tabela' => 'enderecos_clientes', 'rotulo' => 'endereço', 'aba' => 'enderecos',
            'acao' => 'ENDERECO_CLIENTE',
            'campos' => ['tipo_endereco_id', 'logradouro', 'numero', 'complemento', 'bairro_id',
                'cep', 'contato', 'telefone', 'celular', 'email', 'observacao'],
            'limites' => ['logradouro'=>200, 'numero'=>20, 'complemento'=>200, 'contato'=>100,
                'telefone'=>20, 'celular'=>20, 'email'=>150],
        ],
        'contato' => [
            'tabela' => 'contatos_clientes', 'rotulo' => 'contato', 'aba' => 'contatos',
            'acao' => 'CONTATO_CLIENTE',
            'campos' => ['departamento_id', 'nome', 'telefone', 'celular', 'email', 'observacao'],
            'limites' => ['nome'=>100, 'telefone'=>20, 'celular'=>20, 'email'=>150],
        ],
        default => null,
    };
}

function clienteRelacionadoConsultar(PDO $pdo, array $config, int $id, int $clienteId, bool $bloquear = false): array|false
{
    // A tabela vem exclusivamente da lista interna de dois tipos permitidos.
    $stmt = $pdo->prepare('SELECT * FROM ' . $config['tabela'] . ' WHERE id=:id AND cliente_id=:cliente_id'
        . ($bloquear ? ' FOR UPDATE' : ''));
    $stmt->execute(['id'=>$id, 'cliente_id'=>$clienteId]);
    return $stmt->fetch();
}

function clienteRelacionadoDados(array $entrada, array $config, string $tipo): array
{
    $dados = [];
    foreach ($config['campos'] as $campo) {
        $dados[$campo] = clienteTexto($entrada, $campo);
    }
    $dados['ativo'] = clienteTexto($entrada, 'ativo') === '1' ? 1 : 0;
    if ($tipo === 'endereco') {
        $dados['principal'] = clienteTexto($entrada, 'principal') === '1' ? 1 : 0;
    }
    return $dados;
}

function clienteRelacionadoValidar(array $entrada, array $dados, array $config, string $tipo): array
{
    $erros = [];
    foreach ($dados as $campo=>$valor) {
        if (isset($entrada[$campo]) && !is_string($entrada[$campo])) {
            $erros[] = 'Formato inválido para ' . str_replace('_', ' ', $campo) . '.';
        }
    }
    foreach (['ativo', 'principal'] as $campo) {
        if (isset($dados[$campo], $entrada[$campo]) && $entrada[$campo] !== '1') {
            $erros[] = 'Situação inválida.';
        }
    }
    foreach ($config['limites'] as $campo=>$limite) {
        if (mb_strlen($dados[$campo], 'UTF-8') > $limite) {
            $erros[] = 'O campo ' . str_replace('_', ' ', $campo) . ' excede ' . $limite . ' caracteres.';
        }
    }
    if (strlen($dados['observacao']) > 65535) {
        $erros[] = 'A observação é muito longa.';
    }
    if ($dados['email'] !== '' && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if ($tipo === 'endereco') {
        if ($dados['logradouro'] === '') {
            $erros[] = 'Logradouro é obrigatório.';
        }
        if (!preg_match('/^[0-9]{5}-?[0-9]{3}$/D', $dados['cep'])) {
            $erros[] = 'Informe um CEP com oito dígitos.';
        }
        if (clienteId($dados['tipo_endereco_id']) === null || clienteId($dados['bairro_id']) === null) {
            $erros[] = 'Selecione o tipo de endereço e o bairro.';
        }
    } else {
        if ($dados['nome'] === '') {
            $erros[] = 'Nome do contato é obrigatório.';
        }
        if (clienteId($dados['departamento_id']) === null) {
            $erros[] = 'Selecione o departamento.';
        }
    }
    return $erros;
}

function clienteRelacionadoReferencias(PDO $pdo, array $dados, string $tipo, ?array $antes): void
{
    if ($tipo === 'contato') {
        $stmt = $pdo->prepare('SELECT id FROM departamentos_clientes WHERE id=:id FOR SHARE');
        $stmt->execute(['id'=>$dados['departamento_id']]);
        if (!$stmt->fetchColumn()) {
            throw new DomainException('Departamento não encontrado.');
        }
        return;
    }
    $stmt = $pdo->prepare('SELECT id FROM tipos_endereco WHERE id=:id FOR SHARE');
    $stmt->execute(['id'=>$dados['tipo_endereco_id']]);
    if (!$stmt->fetchColumn()) {
        throw new DomainException('Tipo de endereço não encontrado.');
    }
    $bairro = clienteEnderecoLocalidade($pdo, (int)$dados['bairro_id']);
    $mantendoHistorico = $antes && (int)$antes['bairro_id'] === (int)$dados['bairro_id']
        && ((int)$antes['ativo'] === 1 || (int)$dados['ativo'] === 0);
    if (!$bairro || (!$mantendoHistorico && !$bairro['localidade_ativa'])) {
        throw new DomainException('Selecione um bairro ativo, de uma cidade e um estado ativos.');
    }
}

function clienteEnderecoLocalidade(PDO $pdo, int $bairroId): array|false
{
    $stmt = $pdo->prepare('SELECT b.id, (b.ativo=1 AND c.ativo=1 AND e.ativo=1) AS localidade_ativa
        FROM bairros b JOIN cidades c ON c.id=b.cidade_id JOIN estados e ON e.id=c.estado_id
        WHERE b.id=:id FOR SHARE');
    $stmt->execute(['id'=>$bairroId]);
    return $stmt->fetch();
}

function clienteEnderecoTrocarPrincipal(PDO $pdo, int $clienteId, int $id): ?array
{
    // O chamador bloqueia o cliente antes: serializa gravações concorrentes dos filhos.
    $stmt = $pdo->prepare('SELECT id, principal FROM enderecos_clientes
        WHERE cliente_id=:cliente_id AND ativo=1 AND principal=1 AND id<>:id FOR UPDATE');
    $stmt->execute(['cliente_id'=>$clienteId, 'id'=>$id]);
    $anterior = $stmt->fetch();
    if ($anterior) {
        $pdo->prepare('UPDATE enderecos_clientes SET principal=0 WHERE id=:id AND cliente_id=:cliente_id')
            ->execute(['id'=>$anterior['id'], 'cliente_id'=>$clienteId]);
    }
    return $anterior ?: null;
}
