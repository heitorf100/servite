<?php
// public/api/avaliacao.php
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
            $where[] = 'a.id = ?';
            $params[] = (int)$_GET['id'];
        }
        if (isset($_GET['agendamento_id'])) {
            $where[] = 'a.agendamento_id = ?';
            $params[] = (int)$_GET['agendamento_id'];
        }

        $sql = "
            SELECT a.*, 
                   u.nome AS cliente_nome,
                   pr.usuario_id AS prestador_usuario_id,
                   pu.nome AS prestador_nome
            FROM avaliacao a
            LEFT JOIN agendamento ag ON a.agendamento_id = ag.id
            LEFT JOIN usuario u ON ag.cliente_id = u.id
            LEFT JOIN prestador pr ON ag.prestador_id = pr.id
            LEFT JOIN usuario pu ON pr.usuario_id = pu.id
        ";

        if (count($where) > 0) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $sql .= " ORDER BY a.data_avaliacao DESC LIMIT $limit";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json($stmt->fetchAll());
    }

    // ================= POST =================
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json(['error'=>'JSON inválido'],400);

        foreach (['agendamento_id','nota'] as $f) {
            if (!isset($data[$f]) || $data[$f] === '') json(['error'=>"Campo obrigatório faltando: $f"],400);
        }

        $nota = (int)$data['nota'];
        if ($nota < 0 || $nota > 10) json(['error'=>'Nota deve ser entre 0 e 10'],400);

        $stmt = $pdo->prepare("SELECT 1 FROM agendamento WHERE id=?");
        $stmt->execute([(int)$data['agendamento_id']]);
        if (!$stmt->fetch()) json(['error'=>'Agendamento não encontrado'],400);

        $comentario = isset($data['comentario']) ? htmlspecialchars($data['comentario'], ENT_QUOTES, 'UTF-8') : null;
        $data_avaliacao = $data['data_avaliacao'] ?? date('Y-m-d');

        $stmt = $pdo->prepare("
            INSERT INTO avaliacao (agendamento_id, nota, comentario, data_avaliacao)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)$data['agendamento_id'],
            $nota,
            $comentario,
            $data_avaliacao
        ]);

        json(['success'=>true,'id'=>$pdo->lastInsertId()],201);
    }

    // ================= PUT =================
    if ($method === 'PUT') {
        if (!isset($_GET['id'])) json(['error'=>'ID obrigatório (param id)'],400);
        $id = (int)$_GET['id'];
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json(['error'=>'JSON inválido'],400);

        $fields = [];
        $values = [];

        foreach (['nota','comentario','data_avaliacao'] as $f) {
            if (isset($data[$f])) {
                $fields[] = "$f = ?";
                $values[] = $f==='nota' ? (int)$data[$f] : $data[$f];
            }
        }

        if (empty($fields)) json(['error'=>'Nada para atualizar'],400);

        $values[] = $id;
        $stmt = $pdo->prepare("UPDATE avaliacao SET ".implode(', ',$fields)." WHERE id=?");
        $stmt->execute($values);
        json(['success'=>true,'updated'=>$stmt->rowCount()]);
    }

    // ================= DELETE =================
    if ($method === 'DELETE') {
        if (!isset($_GET['id'])) json(['error'=>'ID obrigatório (param id)'],400);
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM avaliacao WHERE id=?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) json(['error'=>'Avaliação não encontrada'],404);
        json(['success'=>true,'deleted'=>$stmt->rowCount()]);
    }

    json(['error'=>'Método não permitido'],405);

} catch (PDOException $e) {
    json(['error'=>'DB error: '.$e->getMessage()],500);
}
