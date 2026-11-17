<?php

class UserController
{
    private User $userModel;

    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
    }

    // GET /users: 
    public function index(): void
    {
        $this->respond(200, $this->userModel->findAll());
    }

    // GET /users/{id}:
    public function show(int $id): void
    {
        $user = $this->userModel->findById($id);

        if (!$user) {
            $this->respond(404, ['error' => 'Usuário não encontrado']);
        }

        $this->respond(200, $user);
    }

    // POST /users:
    public function store(): void
    {
        $input = $this->readJson();
        $data = $this->extractUserData($input, true); // no cadastro, active padrão = true

        $error = $this->userModel->validate($data);
        if ($error) {
            $this->respond(400, ['error' => $error]);
        }

        $newId = $this->userModel->create($data);
        $this->respond(201, $this->userModel->findById($newId));
    }

    // PUT /users/{id}:
    public function update(int $id): void
    {
        $current = $this->userModel->findById($id);

        if (!$current) {
            $this->respond(404, ['error' => 'Usuário não encontrado']);
        }

        $input = $this->readJson();
        $data = $this->extractUserData($input, $current['active']);   // na edição, mantém o atual

        $error = $this->userModel->validate($data, $id);
        if ($error) {
            $this->respond(400, ['error' => $error]);
        }

        $this->userModel->update($id, $data);
        $this->respond(200, $this->userModel->findById($id));
    }

    // DELETE /users/{id}
    public function destroy(int $id): void
    {
        if (!$this->userModel->findById($id)) {
            $this->respond(404, ['error' => 'Usuário não encontrado']);
        }

        $this->userModel->softDelete($id);
        $this->respond(204);
    }

    // Lê o corpo da requisição. Se não for um JSON de objeto, responde 400.
    private function readJson(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            $this->respond(400, ['error' => 'JSON inválido']);
        }

        return $input;
    }

    private function extractUserData(array $input, bool $defaultActive): array
    {
        return [
            'name' => trim($input['name'] ?? ''),
            'email' => trim($input['email'] ?? ''),
            'login' => trim($input['login'] ?? ''),
            'password' => $input['password'] ?? null,
            'active' => $input['active'] ?? $defaultActive,
        ];
    }
    private function respond(int $status, ?array $body = null): never
    {
        http_response_code($status);

        if ($body !== null) {
            echo json_encode($body, JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
}
