<?php
// api/prestador.php
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
        if (!isset($_GET['id']) && !isset($_GET['usuario_id'])) {
            json(['error'=>'Informe o id do prestador ou usuario_id'],400);
        }

        $whereField = isset($_GET['id']) ? 'p.id' : 'p.usuario_id';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_GET['usuario_id'];

        // Dados do prestador
        $stmt = $pdo->prepare("
            SELECT p.*, u.nome, u.email, u.telefone, u.tipo
            FROM prestador p
            JOIN usuario u ON p.usuario_id = u.id
            WHERE $whereField = ?
        ");
        $stmt->execute([$id]);
        $prestador = $stmt->fetch();
        if (!$prestador) json(['error'=>'Prestador não encontrado'],404);

        // Serviços/produtos ofertados
        $stmt = $pdo->prepare("SELECT * FROM servico_produto WHERE prestador_id = ?");
        $stmt->execute([$prestador['id']]);
        $prestador['servicos'] = $stmt->fetchAll();

        // Agendamentos futuros (status pendente ou aceito)
        $stmt = $pdo->prepare("
            SELECT a.*, s.titulo AS servico_titulo, u.nome AS cliente_nome
            FROM agendamento a
            JOIN servico_produto s ON a.servico_id = s.id
            JOIN usuario u ON a.cliente_id = u.id
            WHERE a.prestador_id = ? AND a.status IN ('em análise','aceito')
            ORDER BY a.data_agendamento, a.hora_agendamento
        ");
        $stmt->execute([$prestador['id']]);
        $prestador['agendamentos_futuros'] = $stmt->fetchAll();

        json($prestador);
    }

    // ================= POST =================
    // Futuro: criar prestador
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json(['error'=>'JSON inválido'],400);

        foreach (['usuario_id','descricao','tipo_servico','valor'] as $f) {
            if (empty($data[$f])) json(['error'=>"Campo obrigatório faltando: $f"],400);
        }

        // Verifica se usuário existe
        $stmt = $pdo->prepare("SELECT 1 FROM usuario WHERE id=?");
        $stmt->execute([(int)$data['usuario_id']]);
        if (!$stmt->fetch()) json(['error'=>'Usuário não encontrado'],400);

        $stmt = $pdo->prepare("
            INSERT INTO prestador (usuario_id, descricao, tipo_servico, valor, foto, nota_media)
            VALUES (?, ?, ?, ?, ?, 0.00)
        ");
        $stmt->execute([
            (int)$data['usuario_id'],
            htmlspecialchars($data['descricao'], ENT_QUOTES, 'UTF-8'),
            $data['tipo_servico'],
            number_format((float)$data['valor'],2,'.',''),
            $data['foto'] ?? null
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
        $allowed = ['descricao','tipo_servico','valor','foto'];

        foreach ($allowed as $f) {
            if (isset($data[$f])) {
                $fields[] = "$f = ?";
                if ($f==='valor') $values[] = number_format((float)$data[$f],2,'.','');
                elseif ($f==='descricao' || $f==='foto') $values[] = htmlspecialchars($data[$f], ENT_QUOTES, 'UTF-8');
                else $values[] = $data[$f];
            }
        }

        if (empty($fields)) json(['error'=>'Nada para atualizar'],400);

        $values[] = $id;
        $stmt = $pdo->prepare("UPDATE prestador SET ".implode(', ',$fields)." WHERE id=?");
        $stmt->execute($values);
        json(['success'=>true,'updated'=>$stmt->rowCount()]);
    }

    // ================= DELETE =================
    if ($method === 'DELETE') {
        if (!isset($_GET['id'])) json(['error'=>'ID obrigatório (param id)'],400);
        $id = (int)$_GET['id'];

        $stmt = $pdo->prepare("DELETE FROM prestador WHERE id=?");
        $stmt->execute([$id]);
        if ($stmt->rowCount()===0) json(['error'=>'Prestador não encontrado'],404);

        json(['success'=>true,'deleted'=>$stmt->rowCount()]);
    }

    json(['error'=>'Método não permitido'],405);

} catch (PDOException $e) {
    json(['error'=>'DB error: '.$e->getMessage()],500);
}
