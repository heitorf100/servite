<?php
// public/api/mensagem.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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
        if (!isset($_GET['agendamento_id'])) {
            json(['error' => 'Parâmetro agendamento_id obrigatório'], 400);
        }

        $agendamento_id = (int)$_GET['agendamento_id'];
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

        $sql = "
            SELECT m.*, 
                   c.nome AS cliente_nome, 
                   p.usuario_id AS prestador_usuario_id, 
                   u.nome AS prestador_nome
            FROM mensagem m
            LEFT JOIN usuario c ON m.cliente_id = c.id
            LEFT JOIN prestador p ON m.prestador_id = p.id
            LEFT JOIN usuario u ON p.usuario_id = u.id
            WHERE m.agendamento_id = ?
            ORDER BY m.data_hora ASC
            LIMIT $limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$agendamento_id]);
        json($stmt->fetchAll());
    }

    // ================= POST =================
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json(['error'=>'JSON inválido'], 400);

        foreach (['agendamento_id','cliente_id','prestador_id','conteudo'] as $f) {
            if (empty($data[$f])) json(['error'=>"Campo obrigatório faltando: $f"],400);
        }

        // Limitar tamanho da mensagem
        if (strlen($data['conteudo']) > 1000) {
            json(['error'=>'Mensagem muito longa'],400);
        }

        // Verifica referências
        $checks = [
            ['SELECT 1 FROM agendamento WHERE id=?', $data['agendamento_id'], 'Agendamento não encontrado'],
            ['SELECT 1 FROM usuario WHERE id=?', $data['cliente_id'], 'Cliente não encontrado'],
            ['SELECT 1 FROM prestador WHERE id=?', $data['prestador_id'], 'Prestador não encontrado']
        ];
        foreach ($checks as [$sqlCheck,$val,$msg]) {
            $stmt = $pdo->prepare($sqlCheck);
            $stmt->execute([$val]);
            if (!$stmt->fetch()) json(['error'=>$msg],400);
        }

        $conteudo = htmlspecialchars($data['conteudo'], ENT_QUOTES, 'UTF-8');

        $stmt = $pdo->prepare("
            INSERT INTO mensagem (agendamento_id, cliente_id, prestador_id, conteudo)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)$data['agendamento_id'],
            (int)$data['cliente_id'],
            (int)$data['prestador_id'],
            $conteudo
        ]);

        json(['success'=>true,'id'=>$pdo->lastInsertId()],201);
    }

    // ================= DELETE =================
    if ($method === 'DELETE') {
        if (!isset($_GET['id'])) json(['error'=>'ID obrigatório (param id)'],400);
        $id = (int)$_GET['id'];

        $stmt = $pdo->prepare("DELETE FROM mensagem WHERE id=?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) json(['error'=>'Mensagem não encontrada'],404);

        json(['success'=>true,'deleted'=>$stmt->rowCount()]);
    }

    json(['error'=>'Método não permitido'],405);

} catch (PDOException $e) {
    json(['error'=>'DB error: '.$e->getMessage()],500);
}
