<?php
// api/servico_produto.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php'; // assume $pdo criado aqui

function json($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // ================= GET =================
    if ($method === 'GET') {
        $params = [];
        $where = [];

        if (isset($_GET['id'])) {
            $where[] = 'id = ?';
            $params[] = (int)$_GET['id'];
        }
        if (isset($_GET['prestador_id'])) {
            $where[] = 'prestador_id = ?';
            $params[] = (int)$_GET['prestador_id'];
        }

        $sql = "SELECT * FROM servico_produto";
        if (count($where) > 0) $sql .= " WHERE " . implode(' AND ', $where);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();

        if (isset($_GET['id'])) {
            if (empty($result)) json(['error'=>'Serviço/produto não encontrado'],404);
            json($result[0]);
        } else {
            json($result);
        }
    }

    // ================= POST =================
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json(['error'=>'JSON inválido'],400);

        foreach (['prestador_id','tipo','titulo'] as $f) {
            if (empty($data[$f])) json(['error'=>"Campo obrigatório faltando: $f"],400);
        }

        $stmt = $pdo->prepare("
            INSERT INTO servico_produto (prestador_id, tipo, titulo, descricao, categoria_id, criado_em)
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            (int)$data['prestador_id'],
            $data['tipo'],
            $data['titulo'],
            $data['descricao'] ?? null,
            isset($data['categoria_id']) ? (int)$data['categoria_id'] : null
        ]);

        json(['success'=>true,'id'=>$pdo->lastInsertId()],201);
    }

    // ================= PUT =================
    if ($method === 'PUT') {
        if (!isset($_GET['id'])) json(['error'=>'ID necessário para atualização'],400);
        $id = (int)$_GET['id'];
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json(['error'=>'JSON inválido'],400);

        $fields = [];
        $values = [];
        $allowed = ['prestador_id','tipo','titulo','descricao','categoria_id'];

        foreach ($allowed as $f) {
            if (isset($data[$f])) {
                $fields[] = "$f = ?";
                if ($f==='prestador_id' || $f==='categoria_id') $values[] = (int)$data[$f];
                else $values[] = $data[$f];
            }
        }

        if (empty($fields)) json(['error'=>'Nada para atualizar'],400);

        $values[] = $id;
        $stmt = $pdo->prepare("UPDATE servico_produto SET ".implode(', ',$fields)." WHERE id=?");
        $stmt->execute($values);
        json(['success'=>true,'updated'=>$stmt->rowCount()]);
    }

    // ================= DELETE =================
    if ($method === 'DELETE') {
        if (!isset($_GET['id'])) json(['error'=>'ID necessário para exclusão'],400);
        $id = (int)$_GET['id'];

        $stmt = $pdo->prepare("DELETE FROM servico_produto WHERE id=?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) json(['error'=>'Serviço/produto não encontrado'],404);

        json(['success'=>true,'deleted'=>$stmt->rowCount()]);
    }

    json(['error'=>'Método não permitido'],405);

} catch (PDOException $e) {
    json(['error'=>'DB error: '.$e->getMessage()],500);
}
