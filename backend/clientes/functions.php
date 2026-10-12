<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/validators.php';

function clienteTexto(array $entrada, string $campo, string $padrao = ''): string
{
    return isset($entrada[$campo]) && is_string($entrada[$campo])
        ? trim($entrada[$campo]) : $padrao;
}

function clienteId(mixed $valor): ?int
{
    if (!is_string($valor) || !preg_match('/^[1-9][0-9]{0,9}$/D', $valor)) {
        return null;
    }
    $id = (int) $valor;
    return $id <= 4294967295 ? $id : null;
}

function clientePadrao(): array
{
    return [
        'id' => 0, 'subcategoria_id' => '', 'data_cadastro' => date('Y-m-d'),
        'nome_fantasia' => '', 'razao_social' => '', 'cnpj' => '',
        'inscricao_estadual' => '', 'inscricao_municipal' => '',
        'telefone' => '', 'email' => '', 'limite_credito' => '0.0000',
        'situacao_cadastral' => '', 'observacao' => '', 'ativo' => 1,
    ];
}

function clienteDados(array $entrada): array
{
    $dados = [];
    foreach (clientePadrao() as $campo => $padrao) {
        if ($campo !== 'id' && $campo !== 'ativo') {
            $dados[$campo] = clienteTexto($entrada, $campo);
        }
    }
    $dados['ativo'] = clienteTexto($entrada, 'ativo') === '1' ? 1 : 0;
    return $dados;
}

function clienteValidar(array $dados): array
{
    $erros = [];
    foreach (['nome_fantasia' => 'Nome fantasia', 'razao_social' => 'Razão social'] as $campo => $rotulo) {
        if ($dados[$campo] === '') {
            $erros[] = $rotulo . ' é obrigatório.';
        }
    }
    $limites = [
        'nome_fantasia' => 200, 'razao_social' => 200, 'cnpj' => 18,
        'inscricao_estadual' => 20, 'inscricao_municipal' => 20,
        'telefone' => 20, 'email' => 150, 'situacao_cadastral' => 50,
    ];
    foreach ($limites as $campo => $limite) {
        if (mb_strlen($dados[$campo], 'UTF-8') > $limite) {
            $erros[] = 'O campo ' . str_replace('_', ' ', $campo) . ' excede ' . $limite . ' caracteres.';
        }
    }
    if (strlen($dados['observacao']) > 65535) {
        $erros[] = 'A observação é muito longa.';
    }
    if (!preg_match('/^(?:[0-9]{14}|[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}-[0-9]{2})$/D', $dados['cnpj'])
        || !validarCnpj($dados['cnpj'])) {
        $erros[] = 'O CNPJ informado é inválido.';
    }
    if ($dados['email'] !== '' && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $dados['data_cadastro']);
    if (!$data || $data->format('Y-m-d') !== $dados['data_cadastro']
        || $dados['data_cadastro'] < '1000-01-01') {
        $erros[] = 'Informe uma data de cadastro válida.';
    }
    if (clienteId($dados['subcategoria_id']) === null) {
        $erros[] = 'Selecione uma subcategoria válida.';
    }
    if (!preg_match('/^[0-9]{1,15}(?:[.,][0-9]{1,4})?$/D', $dados['limite_credito'])) {
        $erros[] = 'Informe um limite de crédito não negativo, com até 15 dígitos inteiros e 4 casas decimais, sem separador de milhares.';
    }
    return $erros;
}

function clienteConsultar(PDO $pdo, int $id, int $empresaId, bool $bloquear = false): array|false
{
    $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = :id AND empresa_id = :empresa_id'
        . ($bloquear ? ' FOR UPDATE' : ''));
    $stmt->execute(['id' => $id, 'empresa_id' => $empresaId]);
    return $stmt->fetch();
}

function clienteAuditar(PDO $pdo, int $id, string $acao, ?array $antes, array $depois): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO logs_usuarios
         (empresa_id, usuario_id, acao, entidade, registro_id, dados_anteriores, dados_novos, endereco_ip, user_agent)
         VALUES (:empresa_id, :usuario_id, :acao, 'clientes', :registro_id, :antes, :depois, :ip, :user_agent)"
    );
    $stmt->execute([
        'empresa_id' => $_SESSION['empresa_id'], 'usuario_id' => $_SESSION['usuario_id'],
        'acao' => $acao, 'registro_id' => (string) $id,
        'antes' => $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        'depois' => json_encode($depois, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        'user_agent' => mb_strcut((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500, 'UTF-8'),
    ]);
}
