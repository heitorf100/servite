<?php
// api/config.php
header('Content-Type: application/json; charset=utf-8');

// Caminho do arquivo seguro de configuração (fora do htdocs)
$configFile = 'C:/configs/servite/config.php';

// Verifica se o arquivo existe
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Config file not found']);
    exit;
}

// Carrega as configurações
$cfg = include $configFile;

// Valida se retornou um array
if (!is_array($cfg)) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid config format']);
    exit;
}

// Extrai as variáveis de conexão
$DB_HOST = $cfg['DB_HOST'] ?? '127.0.0.1';
$DB_NAME = $cfg['DB_NAME'] ?? '';
$DB_USER = $cfg['DB_USER'] ?? '';
$DB_PASS = $cfg['DB_PASS'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);

    // Mostra erro real só em ambiente local (debug)
    if ($_SERVER['SERVER_NAME'] === 'servite.local') {
        echo json_encode(['error' => $e->getMessage()]);
    } else {
        echo json_encode(['error' => 'DB connect error']);
    }
    exit;
}
