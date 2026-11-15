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

// POST /users: cria um novo usuário
if ($method === 'POST' && $resource === 'users' && $id === null) {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input === null) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Trata os inputs antes de validar para evitar espaços em branco e warnings do php por retorno null no JSON.
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $login = trim($input['login'] ?? '');
    $password = $input['password'] ?? '';
    $active = $input['active'] ?? true;


    if (mb_strlen($name) < 3) {
        http_response_code(400);
        echo json_encode(['error' => 'O nome é obrigatório e deve ter pelo menos 3 caracteres'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'O email é obrigatório e deve ser no formato "email@email.com"'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (mb_strlen($login) < 3) {
        http_response_code(400);
        echo json_encode(['error' => 'O login é obrigatório e deve ter pelo menos 3 caracteres'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (mb_strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'A senha é obrigatória e deve ter pelo menos 8 caracteres'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!is_bool($active)) {
        http_response_code(400);
        echo json_encode(['error' => 'O campo "active" deve ser booleano (true ou false)'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = Database::getConnection();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE login = :login');
    $stmt->execute(['login' => $login]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'O login informado já está em uso, escolha outro.'], JSON_UNESCAPED_UNICODE);
        exit;
    }


    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'O email informado já está em uso, escolha outro.'], JSON_UNESCAPED_UNICODE);
        exit;
    }


    $stmt = $pdo->prepare('INSERT INTO users (name, email, login, password_hash, active) VALUES (:name, :email, :login, :password, :active)');
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'login' => $login,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'active' => (int)$active
    ]);

    http_response_code(201);
    echo json_encode(['message' => 'Usuário criado com sucesso'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Nenhuma rota corresponde: retorna 404
http_response_code(404);
echo json_encode(['error' => 'Rota não encontrada'], JSON_UNESCAPED_UNICODE);