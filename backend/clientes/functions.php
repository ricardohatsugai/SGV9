<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/validators.php';

/** Compatibilidade com o banco atual e com instalações dos init-scripts. */
function estruturaClientes(PDO $pdo): array
{
    $colunas = $pdo->query('SHOW COLUMNS FROM clientes')->fetchAll(PDO::FETCH_COLUMN);
    $legado = in_array('datacad', $colunas, true);
    $campos = [
        'data_cadastro' => $legado ? 'datacad' : 'data_cadastro',
        'nome_fantasia' => $legado ? 'nomefantasia' : 'nome_fantasia',
        'razao_social' => $legado ? 'razaosocial' : 'razao_social',
        'inscricao_estadual' => $legado ? 'inscr_est' : 'inscricao_estadual',
        'inscricao_municipal' => $legado ? 'inscr_mun' : 'inscricao_municipal',
        'subcategoria_id' => $legado ? 'subcategorias_id' : 'subcategoria_id',
        'limite_credito' => $legado ? 'credito' : 'limite_credito',
        'observacao' => $legado ? 'obs' : 'observacao',
        'cnpj' => 'cnpj', 'telefone' => 'telefone', 'email' => 'email', 'ativo' => 'ativo',
    ];
    foreach (array_merge(['id', 'empresa_id'], array_values($campos)) as $coluna) {
        if (!in_array($coluna, $colunas, true)) {
            throw new RuntimeException('Estrutura de clientes incompatível.');
        }
    }
    return [
        'campos' => $campos,
        'categorias' => $legado ? 'categorias' : 'categorias_clientes',
        'subcategorias' => $legado ? 'subcategorias' : 'subcategorias_clientes',
        'email_max' => $legado ? 100 : 150,
    ];
}

function entradaCliente(array $entrada, string $campo, string $padrao = ''): string
{
    $valor = $entrada[$campo] ?? $padrao;
    if (!is_string($valor)) {
        throw new InvalidArgumentException('Dados inválidos no formulário.');
    }
    return trim($valor);
}

function idCliente(string $valor, bool $permitirZero = false): int
{
    if (!preg_match('/^(0|[1-9][0-9]{0,9})$/D', $valor)
        || (int) $valor > 4294967295 || (!$permitirZero && (int) $valor === 0)) {
        throw new InvalidArgumentException('Identificador inválido.');
    }
    return (int) $valor;
}

function carregarCliente(PDO $pdo, array $estrutura, int $id, int $empresaId, bool $bloquear = false): ?array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM clientes WHERE id = :id AND empresa_id = :empresa_id'
        . ($bloquear ? ' FOR UPDATE' : '')
    );
    $stmt->execute(['id' => $id, 'empresa_id' => $empresaId]);
    $cliente = $stmt->fetch();
    if (!$cliente) {
        return null;
    }
    foreach ($estrutura['campos'] as $campo => $coluna) {
        $cliente[$campo] = $cliente[$coluna];
    }
    return $cliente;
}

function versaoCliente(array $cliente): string
{
    return hash('sha256', json_encode($cliente, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
}

function validarDadosCliente(array $entrada, array $estrutura): array
{
    $limites = [
        'nome_fantasia' => 200, 'razao_social' => 200, 'inscricao_estadual' => 20,
        'inscricao_municipal' => 20, 'telefone' => 20, 'email' => $estrutura['email_max'],
    ];
    $dados = [];
    foreach ($limites as $campo => $limite) {
        $dados[$campo] = entradaCliente($entrada, $campo);
        if (mb_strlen($dados[$campo], 'UTF-8') > $limite) {
            throw new InvalidArgumentException('Um dos campos excede o tamanho permitido.');
        }
    }
    if ($dados['nome_fantasia'] === '' || $dados['razao_social'] === '') {
        throw new InvalidArgumentException('Nome fantasia e razão social são obrigatórios.');
    }
    $cnpj = entradaCliente($entrada, 'cnpj');
    if (!preg_match('/^[0-9.\/-]+$/D', $cnpj) || !validarCnpj($cnpj)) {
        throw new InvalidArgumentException('O CNPJ informado é inválido.');
    }
    $dados['cnpj'] = formatarCnpj($cnpj);
    if ($dados['email'] !== '' && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Informe um e-mail válido.');
    }
    $dados['data_cadastro'] = entradaCliente($entrada, 'data_cadastro');
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $dados['data_cadastro']);
    if (!$data || $data->format('Y-m-d') !== $dados['data_cadastro'] || (int) $data->format('Y') < 1000) {
        throw new InvalidArgumentException('Informe uma data de cadastro válida.');
    }
    $dados['subcategoria_id'] = idCliente(entradaCliente($entrada, 'subcategoria_id'));
    $credito = entradaCliente($entrada, 'limite_credito', '0');
    if (!preg_match('/^[0-9]{1,15}([.,][0-9]{1,4})?$/D', $credito)) {
        throw new InvalidArgumentException('Informe um limite de crédito não negativo, com até quatro casas decimais e sem separador de milhar.');
    }
    $partes = explode('.', str_replace(',', '.', $credito));
    $dados['limite_credito'] = (ltrim($partes[0], '0') ?: '0') . '.' . str_pad($partes[1] ?? '', 4, '0');
    $dados['observacao'] = entradaCliente($entrada, 'observacao');
    if (strlen($dados['observacao']) > 65535) {
        throw new InvalidArgumentException('A observação excede o tamanho permitido.');
    }
    $ativo = entradaCliente($entrada, 'ativo', '0');
    if (!in_array($ativo, ['0', '1'], true)) {
        throw new InvalidArgumentException('Situação inválida para o cliente.');
    }
    $dados['ativo'] = (int) $ativo;
    return $dados;
}

function registrarLogCliente(PDO $pdo, int $empresaId, int $usuarioId, int $id, string $acao, ?array $antes, array $depois): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO logs_usuarios
         (empresa_id, usuario_id, acao, entidade, registro_id, dados_anteriores, dados_novos, endereco_ip, user_agent)
         VALUES (:empresa_id, :usuario_id, :acao, :entidade, :registro_id, :antes, :depois, :ip, :user_agent)'
    );
    $stmt->execute([
        'empresa_id' => $empresaId, 'usuario_id' => $usuarioId, 'acao' => $acao,
        'entidade' => 'clientes', 'registro_id' => (string) $id,
        'antes' => $antes === null ? null : json_encode($antes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        'depois' => json_encode($depois, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500, 'UTF-8'),
    ]);
}

function salvarCliente(PDO $pdo, array $estrutura, array $entrada, int $empresaId, int $usuarioId): int
{
    $id = idCliente(entradaCliente($entrada, 'id', '0'), true);
    $dados = validarDadosCliente($entrada, $estrutura);
    $pdo->beginTransaction();
    try {
        $anterior = $id ? carregarCliente($pdo, $estrutura, $id, $empresaId, true) : null;
        if ($id && !$anterior) {
            throw new InvalidArgumentException('Cliente não encontrado.');
        }
        if ($anterior && !hash_equals(versaoCliente($anterior), entradaCliente($entrada, 'versao'))) {
            throw new InvalidArgumentException('Este cliente foi alterado em outra sessão. Reabra o cadastro antes de salvar.');
        }
        $subcategoria = $pdo->prepare('SELECT id FROM `' . $estrutura['subcategorias'] . '` WHERE id = :id');
        $subcategoria->execute(['id' => $dados['subcategoria_id']]);
        if (!$subcategoria->fetchColumn()) {
            throw new InvalidArgumentException('Selecione uma classificação existente para o cliente.');
        }
        $duplicidade = $pdo->prepare(
            "SELECT id FROM clientes WHERE empresa_id = :empresa_id AND id <> :id
             AND REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', '') = :documento"
        );
        $duplicidade->execute(['empresa_id' => $empresaId, 'id' => $id, 'documento' => somenteDigitos($dados['cnpj'])]);
        if ($duplicidade->fetchColumn()) {
            throw new InvalidArgumentException('Já existe um cliente com esse CNPJ nesta empresa.');
        }
        // Nomes SQL vêm exclusivamente do mapa interno de schemas conhecidos.
        $colunas = [];
        $valores = [];
        $atribuicoes = [];
        foreach ($estrutura['campos'] as $campo => $coluna) {
            $colunas[] = '`' . $coluna . '`';
            $valores[] = ':' . $campo;
            $atribuicoes[] = '`' . $coluna . '` = :' . $campo;
        }
        $dados['empresa_id'] = $empresaId;
        if ($id) {
            $sql = 'UPDATE clientes SET ' . implode(', ', $atribuicoes) . ' WHERE id = :id AND empresa_id = :empresa_id';
            $dados['id'] = $id;
        } else {
            $sql = 'INSERT INTO clientes (empresa_id, ' . implode(', ', $colunas)
                . ') VALUES (:empresa_id, ' . implode(', ', $valores) . ')';
        }
        $pdo->prepare($sql)->execute($dados);
        $novo = !$id;
        $id = $id ?: (int) $pdo->lastInsertId();
        registrarLogCliente($pdo, $empresaId, $usuarioId, $id, $novo ? 'CLIENTE_CRIADO' : 'CLIENTE_ATUALIZADO', $anterior, $dados);
        $pdo->commit();
        return $id;
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }
}

function alterarStatusCliente(PDO $pdo, array $estrutura, array $entrada, int $empresaId, int $usuarioId): void
{
    $id = idCliente(entradaCliente($entrada, 'id'));
    $ativo = entradaCliente($entrada, 'ativo');
    if (!in_array($ativo, ['0', '1'], true)) {
        throw new InvalidArgumentException('Situação inválida para o cliente.');
    }
    $pdo->beginTransaction();
    try {
        $cliente = carregarCliente($pdo, $estrutura, $id, $empresaId, true);
        if (!$cliente) {
            throw new InvalidArgumentException('Cliente não encontrado.');
        }
        if (!hash_equals(versaoCliente($cliente), entradaCliente($entrada, 'versao'))) {
            throw new InvalidArgumentException('Este cliente foi alterado em outra sessão. Atualize a listagem antes de continuar.');
        }
        if ((int) $cliente['ativo'] !== (int) $ativo) {
            $pdo->prepare('UPDATE clientes SET ativo = :ativo WHERE id = :id AND empresa_id = :empresa_id')
                ->execute(['id' => $id, 'ativo' => (int) $ativo, 'empresa_id' => $empresaId]);
            registrarLogCliente($pdo, $empresaId, $usuarioId, $id, $ativo === '1' ? 'CLIENTE_REATIVADO' : 'CLIENTE_INATIVADO', ['ativo' => (int) $cliente['ativo']], ['ativo' => (int) $ativo]);
        }
        $pdo->commit();
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }
}
