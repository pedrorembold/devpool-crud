<?php

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/Models/User.php';

// CORS: permite que o frontend acesse a API em outra porta ou domínio.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responde toda requisição com JSON
header('Content-Type: application/json; charset=utf-8');

// oculta a versão do PHP nas respostas, por segurança.
header_remove('X-Powered-By');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));
$resource = $parts[0];   // retorna "users"
$id = $parts[1] ?? null;         // retorna o valor do id informado na requisição, ou null se não houver


// Preflight do navegador: responde sem conteúdo para requisições OPTIONS (CORS)
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$userModel = new User(Database::getConnection());

// GET /users: lista todos os usuários
if ($method === 'GET' && $resource === 'users' && $id === null) {

    http_response_code(200);
    echo json_encode($userModel->findAll(), JSON_UNESCAPED_UNICODE);
    exit;
}

// GET /users/{id}: busca um usuário específico pelo ID
if ($method === 'GET' && $resource === 'users' && $id !== null) {
    $user = $userModel->findById((int) $id);

    if ($user) {
        http_response_code(200);
        echo json_encode($user, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}


// =============== CONTINUAR DAQUI CORRIGINDO A PARTIR DO MODEL ==============


// POST /users: cria um novo usuário
if ($method === 'POST' && $resource === 'users' && $id === null) {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input === null) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Trata os inputs antes de validar para evitar espaços em branco e warnings do php por retorno null no JSON.
    $data = [
        'name' => trim($input['name'] ?? ''),
        'email' => trim($input['email'] ?? ''),
        'login' => trim($input['login'] ?? ''),
        'password' => $input['password'] ?? null,
        'active' => $input['active'] ?? true,
    ];

    $error = $userModel->validate($data);

    if ($error) {
        http_response_code(400);
        echo json_encode(['error' => $error], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $newId = $userModel->create($data);

    http_response_code(201);
    echo json_encode($userModel->findById($newId), JSON_UNESCAPED_UNICODE);
    exit;
}

// PUT /users/{id}: atualiza um usuário existente
if ($method === 'PUT' && $resource === 'users' && $id !== null) {
    $current = $userModel->findById((int) $id);

    if (!$current) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = [
        'name' => trim($input['name'] ?? ''),
        'email' => trim($input['email'] ?? ''),
        'login' => trim($input['login'] ?? ''),
        'password' => $input['password'] ?? null,
        'active' => $input['active'] ?? $current['active'],
    ];

    $error = $userModel->validate($data, (int) $id);

    if ($error) {
        http_response_code(400);
        echo json_encode(['error' => $error], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userModel->update((int) $id, $data);

    http_response_code(200);
    echo json_encode($userModel->findById((int) $id), JSON_UNESCAPED_UNICODE);
    exit;
}


// DELETE /users/{id}: marca um usuário como excluído
if ($method === 'DELETE' && $resource === 'users' && $id !== null) {
    if (!$userModel->findById((int) $id)) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userModel->softDelete((int) $id);

    http_response_code(204);
    exit;
}


// Nenhuma rota corresponde: retorna 404
http_response_code(404);
echo json_encode(['error' => 'Rota não encontrada'], JSON_UNESCAPED_UNICODE);
