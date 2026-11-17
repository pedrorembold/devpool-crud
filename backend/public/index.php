<?php

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/Models/User.php';
require __DIR__ . '/../src/Controllers/UserController.php';

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
// O id, se existir, precisa ser composto só de dígitos
if ($id !== null && !ctype_digit($id)) {
    http_response_code(404);
    echo json_encode(['error' => 'Rota não encontrada'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Preflight do navegador: responde sem conteúdo para requisições OPTIONS (CORS)
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$userController = new UserController(new User(Database::getConnection()));

if ($resource === 'users') {
    if ($method === 'GET' && $id === null)    $userController->index();
    if ($method === 'GET' && $id !== null)    $userController->show((int) $id);
    if ($method === 'POST' && $id === null)   $userController->store();
    if ($method === 'PUT' && $id !== null)    $userController->update((int) $id);
    if ($method === 'DELETE' && $id !== null) $userController->destroy((int) $id);
}


// Nenhuma rota corresponde: retorna 404
http_response_code(404);
echo json_encode(['error' => 'Rota não encontrada'], JSON_UNESCAPED_UNICODE);
