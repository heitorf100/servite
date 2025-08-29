<?php
// api/users.php
// Requer api/config.php que já prepara $pdo
require_once __DIR__ . '/config.php';

// CORS simples para dev (remova/ajuste em produção)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

function json($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Ler entrada JSON (para POST/PUT)
$input = null;
if (in_array($method, ['POST', 'PUT'])) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if ($raw && $input === null) {
        json(['error' => 'JSON inválido'], 400);
    }
}

try {
    if ($method === 'POST') {
        // Criar usuário
        // Campos esperados: nome, cpf_cnpj, email, senha, telefone, tipo, banco, agencia, conta, tipo_conta
        $req = $input ?? [];
        // validações mínimas
        foreach (['nome','cpf_cnpj','email','senha','tipo'] as $f) {
            if (empty($req[$f])) json(['error'=>"Campo obrigatório faltando: $f"], 400);
        }
        // sanitização básica
        $nome = trim($req['nome']);
        $cpf = trim($req['cpf_cnpj']);
        $email = filter_var(trim($req['email']), FILTER_VALIDATE_EMAIL);
        if (!$email) json(['error'=>'E-mail inválido'], 400);
        $senha = $req['senha'];
        $telefone = $req['telefone'] ?? null;
        $tipo = in_array($req['tipo'], ['Cliente','Prestador']) ? $req['tipo'] : 'Cliente';
        $banco = $req['banco'] ?? null;
        $agencia = $req['agencia'] ?? null;
        $conta = $req['conta'] ?? null;
        $tipo_conta = $req['tipo_conta'] ?? null;

        // verificar duplicidade
        $stmt = $pdo->prepare("SELECT id FROM usuario WHERE cpf_cnpj = ? OR email = ?");
        $stmt->execute([$cpf, $email]);
        if ($stmt->fetch()) json(['error'=>'CPF/CNPJ ou e-mail já cadastrado'], 409);

        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO usuario (nome, cpf_cnpj, email, senha_hash, telefone, tipo, banco, agencia, conta, tipo_conta)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $cpf, $email, $senha_hash, $telefone, $tipo, $banco, $agencia, $conta, $tipo_conta]);

        json(['success' => true, 'id' => $pdo->lastInsertId()], 201);
    }

    if ($method === 'GET') {
        // GET ?id= or ?q= or list
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("SELECT id, nome, cpf_cnpj, email, telefone, tipo, banco, agencia, conta, tipo_conta, criado_at FROM usuario WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) json(['error'=>'Usuário não encontrado'], 404);
            json($row);
        }

        if (isset($_GET['q'])) {
            $q = '%' . str_replace(['%','_'],'\\$0',trim($_GET['q'])) . '%';
            $stmt = $pdo->prepare("SELECT id, nome, cpf_cnpj, email, telefone, tipo, criado_at FROM usuario
                                   WHERE nome LIKE ? OR email LIKE ? OR cpf_cnpj LIKE ? LIMIT 200");
            $stmt->execute([$q,$q,$q]);
            $rows = $stmt->fetchAll();
            json($rows);
        }

        // listar (limit)
        $stmt = $pdo->query("SELECT id, nome, email, tipo, criado_at FROM usuario ORDER BY criado_at DESC LIMIT 200");
        $rows = $stmt->fetchAll();
        json($rows);
    }

    if ($method === 'PUT') {
        // Atualizar. Espera ?id= ou id no JSON
        $id = $_GET['id'] ?? ($input['id'] ?? null);
        if (!$id) json(['error'=>'ID do usuário é necessário para atualizar (param id)'], 400);
        $id = (int)$id;

        // buscar existente
        $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) json(['error'=>'Usuário não encontrado'], 404);

        // Campos permitidos para atualização
        $nome = isset($input['nome']) ? trim($input['nome']) : $user['nome'];
        $cpf = isset($input['cpf_cnpj']) ? trim($input['cpf_cnpj']) : $user['cpf_cnpj'];
        $email = isset($input['email']) ? filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL) : $user['email'];
        if ($email === false) json(['error'=>'E-mail inválido'], 400);
        $telefone = $input['telefone'] ?? $user['telefone'];
        $tipo = isset($input['tipo']) && in_array($input['tipo'], ['Cliente','Prestador']) ? $input['tipo'] : $user['tipo'];
        $banco = $input['banco'] ?? $user['banco'];
        $agencia = $input['agencia'] ?? $user['agencia'];
        $conta = $input['conta'] ?? $user['conta'];
        $tipo_conta = $input['tipo_conta'] ?? $user['tipo_conta'];

        // se cpf/email mudou, checar duplicidade
        if ($cpf !== $user['cpf_cnpj'] || $email !== $user['email']) {
            $stmt = $pdo->prepare("SELECT id FROM usuario WHERE (cpf_cnpj = ? OR email = ?) AND id != ?");
            $stmt->execute([$cpf, $email, $id]);
            if ($stmt->fetch()) json(['error'=>'CPF/CNPJ ou e-mail já usado por outro registro'], 409);
        }

        // montar query; se enviar senha, atualizar também
        if (!empty($input['senha'])) {
            $senha_hash = password_hash($input['senha'], PASSWORD_DEFAULT);
            $sql = "UPDATE usuario SET nome=?, cpf_cnpj=?, email=?, senha_hash=?, telefone=?, tipo=?, banco=?, agencia=?, conta=?, tipo_conta=? WHERE id=?";
            $params = [$nome,$cpf,$email,$senha_hash,$telefone,$tipo,$banco,$agencia,$conta,$tipo_conta,$id];
        } else {
            $sql = "UPDATE usuario SET nome=?, cpf_cnpj=?, email=?, telefone=?, tipo=?, banco=?, agencia=?, conta=?, tipo_conta=? WHERE id=?";
            $params = [$nome,$cpf,$email,$telefone,$tipo,$banco,$agencia,$conta,$tipo_conta,$id];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json(['success'=>true]);
    }

    if ($method === 'DELETE') {
        // DELETE ?id=
        if (!isset($_GET['id'])) json(['error'=>'ID necessário para deletar'], 400);
        $id = (int)$_GET['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM usuario WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) json(['error'=>'Usuário não encontrado'], 404);
            json(['success'=>true]);
        } catch (PDOException $e) {
            // FK constraint? -> informar ao front
            if (strpos($e->getMessage(), 'foreign key') !== false || strpos($e->getMessage(), 'REFERENCES') !== false) {
                json(['error'=>'Não é possível excluir usuário com relacionamentos (ex.: agendamentos).'], 400);
            }
            json(['error'=>'Erro ao deletar: '.$e->getMessage()], 500);
        }
    }

    // Método não permitido
    json(['error'=>'Método não permitido'], 405);

} catch (PDOException $ex) {
    json(['error' => 'DB error: '.$ex->getMessage()], 500);
}
