<?php
// api/servicos.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

// pega o método HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Buscar serviço por ID
            $stmt = $pdo->prepare("SELECT * FROM servicos WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $servico = $stmt->fetch();
            echo json_encode($servico ?: ['error' => 'Serviço não encontrado']);
        } elseif (isset($_GET['usuario_id'])) {
            // Listar serviços de um cliente
            $stmt = $pdo->prepare("SELECT * FROM servicos WHERE usuario_id = ?");
            $stmt->execute([$_GET['usuario_id']]);
            echo json_encode($stmt->fetchAll());
        } elseif (isset($_GET['prestador_id'])) {
            // Listar serviços de um prestador
            $stmt = $pdo->prepare("SELECT * FROM servicos WHERE prestador_id = ?");
            $stmt->execute([$_GET['prestador_id']]);
            echo json_encode($stmt->fetchAll());
        } else {
            // Listar todos os serviços
            $stmt = $pdo->query("SELECT * FROM servicos");
            echo json_encode($stmt->fetchAll());
        }
        break;

    case 'POST':
        // Criar novo serviço
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['usuario_id'], $data['prestador_id'], $data['descricao'], $data['data_servico'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados incompletos']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO servicos (usuario_id, prestador_id, descricao, data_servico, status) 
                               VALUES (?, ?, ?, ?, 'pendente')");
        $stmt->execute([$data['usuario_id'], $data['prestador_id'], $data['descricao'], $data['data_servico']]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        // Atualizar serviço
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do serviço é obrigatório']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados inválidos']);
            exit;
        }

        $campos = [];
        $valores = [];
        foreach (['descricao', 'data_servico', 'status'] as $campo) {
            if (isset($data[$campo])) {
                $campos[] = "$campo = ?";
                $valores[] = $data[$campo];
            }
        }

        if (count($campos) > 0) {
            $valores[] = $_GET['id'];
            $stmt = $pdo->prepare("UPDATE servicos SET " . implode(', ', $campos) . " WHERE id = ?");
            $stmt->execute($valores);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Nada para atualizar']);
        }
        break;

    case 'DELETE':
        // Excluir serviço
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do serviço é obrigatório']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM servicos WHERE id = ?");
        $stmt->execute([$_GET['id']]);

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
        break;
}
