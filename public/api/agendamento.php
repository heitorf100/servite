<?php
// public/api/agendamento.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // ajuste em produção
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

        // filtros
        if (isset($_GET['id'])) {
            $where[] = 'a.id = ?';
            $params[] = (int)$_GET['id'];
        }
        if (isset($_GET['cliente_id'])) {
            $where[] = 'a.cliente_id = ?';
            $params[] = (int)$_GET['cliente_id'];
        }
        if (isset($_GET['prestador_id'])) {
            $where[] = 'a.prestador_id = ?';
            $params[] = (int)$_GET['prestador_id'];
        }
        if (isset($_GET['status'])) {
            $where[] = 'a.status = ?';
            $params[] = $_GET['status'];
        }
        if (isset($_GET['data_inicio'])) {
            $where[] = 'a.data_agendamento >= ?';
            $params[] = $_GET['data_inicio'];
        }
        if (isset($_GET['data_fim'])) {
            $where[] = 'a.data_agendamento <= ?';
            $params[] = $_GET['data_fim'];
        }

        $sql = "
            SELECT a.*, 
                   s.titulo AS servico_titulo, 
                   u.nome AS cliente_nome, 
                   pu.nome AS prestador_nome
            FROM agendamento a
            LEFT JOIN servico_produto s ON a.servico_id = s.id
            LEFT JOIN usuario u ON a.cliente_id = u.id
            LEFT JOIN prestador pr ON a.prestador_id = pr.id
            LEFT JOIN usuario pu ON pr.usuario_id = pu.id
        ";

        if (count($where) > 0) {
            $sql .= " WHERE ".implode(' AND ', $where);
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
        $sql .= " ORDER BY a.criado_em DESC LIMIT $limit";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json($stmt->fetchAll());
    }

    // ================= POST =================
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json(['error' => 'JSON inválido'],400);

        foreach (['endereco_id','servico_id','cliente_id','prestador_id','data_agendamento','hora_agendamento','valor'] as $f) {
            if (empty($data[$f])) json(['error'=>"Campo obrigatório faltando: $f"],400);
        }

        $checks = [
            ['SELECT 1 FROM endereco WHERE id = ?', $data['endereco_id'], 'Endereço não encontrado'],
            ['SELECT 1 FROM servico_produto WHERE id = ?', $data['servico_id'], 'Serviço/Produto não encontrado'],
            ['SELECT 1 FROM usuario WHERE id = ?', $data['cliente_id'], 'Cliente não encontrado'],
            ['SELECT 1 FROM prestador WHERE id = ?', $data['prestador_id'], 'Prestador não encontrado']
        ];
        foreach ($checks as [$sqlCheck,$val,$msg]) {
            $stmt = $pdo->prepare($sqlCheck);
            $stmt->execute([$val]);
            if (!$stmt->fetch()) json(['error'=>$msg],400);
        }

        $valor = (float)$data['valor'];
        $taxa = isset($data['taxa']) ? (float)$data['taxa'] : 0;
        if ($valor < 0 || $taxa < 0) json(['error'=>'Valor ou taxa inválidos'],400);

        $agendamentoDateTime = strtotime($data['data_agendamento'].' '.$data['hora_agendamento']);
        if ($agendamentoDateTime < time()) json(['error'=>'Data/Hora de agendamento inválida'],400);

        $stmt = $pdo->prepare("
            SELECT 1 FROM agendamento 
            WHERE prestador_id = ? AND data_agendamento = ? AND hora_agendamento = ? 
            AND status IN ('em análise','aceito','em execução')
        ");
        $stmt->execute([$data['prestador_id'], $data['data_agendamento'], $data['hora_agendamento']]);
        if ($stmt->fetch()) json(['error'=>'Prestador já possui agendamento nesse horário'],400);

        $stmt = $pdo->prepare("
            INSERT INTO agendamento 
            (status, data_agendamento, hora_agendamento, endereco_id, servico_id, cliente_id, prestador_id, valor, data_execucao, hora_execucao, taxa)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $data['status'] ?? 'em análise',
            $data['data_agendamento'],
            $data['hora_agendamento'],
            (int)$data['endereco_id'],
            (int)$data['servico_id'],
            (int)$data['cliente_id'],
            (int)$data['prestador_id'],
            number_format($valor,2,'.',''),
            $data['data_execucao'] ?? null,
            $data['hora_execucao'] ?? null,
            number_format($taxa,2,'.','')
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
        $allowed = ['status','data_execucao','hora_execucao','data_agendamento','hora_agendamento','endereco_id','valor','taxa'];

        foreach ($allowed as $f) {
            if (isset($data[$f])) {
                $fields[] = "$f = ?";
                if ($f==='endereco_id') $values[] = (int)$data[$f];
                elseif (in_array($f,['valor','taxa'])) $values[] = number_format((float)$data[$f],2,'.','');
                else $values[] = $data[$f];
            }
        }

        if (empty($fields)) json(['error'=>'Nada para atualizar'],400);

        $values[] = $id;
        $stmt = $pdo->prepare("UPDATE agendamento SET ".implode(', ',$fields)." WHERE id = ?");
        $stmt->execute($values);
        json(['success'=>true,'updated'=>$stmt->rowCount()]);
    }

    // ================= DELETE =================
    if ($method === 'DELETE') {
        if (!isset($_GET['id'])) json(['error'=>'ID obrigatório (param id)'],400);
        $id = (int)$_GET['id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM agendamento WHERE id=?");
            $stmt->execute([$id]);
            if ($stmt->rowCount()===0) json(['error'=>'Agendamento não encontrado'],404);
            json(['success'=>true,'deleted'=>$stmt->rowCount()]);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(),'foreign key')) {
                json(['error'=>'Não é possível excluir agendamento com relacionamentos'],400);
            }
            throw $e;
        }
    }

    json(['error'=>'Método não permitido'],405);

} catch (PDOException $e) {
    json(['error'=>'DB error: '.$e->getMessage()],500);
}
