<?php

require __DIR__ . '/../src/Database.php';

// CORS: permite que o frontend acesse a API em outra porta ou domínio.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responde toda requisição com JSON
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));
$resource = $parts[0];   // retona "users"
$id = $parts[1] ?? null;         // retorna o valor do id informado na requisição, ou null se não houver

// Preflight do navegador: responde sem conteúdo para requisições OPTIONS (CORS)
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// GET /users: lista todos os usuários
if ($method === 'GET' && $resource === 'users' && $id === null) {
    $pdo = Database::getConnection();
    $stmt = $pdo->query('SELECT id, name, email, login, active, created_at FROM users');
    $users = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode($users, JSON_UNESCAPED_UNICODE);
    exit;
}

// GET /users/{id}: busca um usuário específico pelo ID
if ($method === 'GET' && $resource === 'users' && $id !== null) {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT id, name, email, login, active, created_at FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if ($user) {
        http_response_code(200);
        echo json_encode($user, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Nenhuma rota corresponde: retorna 404
http_response_code(404);
echo json_encode(['error' => 'Rota não encontrada'], JSON_UNESCAPED_UNICODE);