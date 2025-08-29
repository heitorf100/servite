<?php
// C:\xampp\htdocs\Servite\api\teste.php

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    "status" => "ok",
    "mensagem" => "API funcionando em servite.local/api/teste.php"
]); 