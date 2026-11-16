<?php

class User
{
    // Colunas que podem sair na API. O password_hash nunca está aqui.
    private const PUBLIC_COLUMNS = 'id, name, email, login, active, created_at';

    private PDO $pdo;

    // O construtor roda automaticamente no "new User"
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Consultas no banco de dados

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE deleted_at IS NULL');
        $users = [];
        foreach ($stmt->fetchAll() as $row) {
            $users[] = $this->mapRow($row);
        }
        return $users;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->mapRow($row) : null;
    }

    // Valida os dados do cadastro ($id = null) ou da edição ($id = id do usuário).
    // Devolve a mensagem do primeiro erro encontrado, ou null se estiver tudo certo.
    public function validate(array $data, ?int $id = null): ?string
    {
        if (mb_strlen($data['name']) < 3) {
            return 'O nome é obrigatório e deve ter pelo menos 3 caracteres';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'O email é obrigatório e deve ser no formato "email@email.com"';
        }

        if (mb_strlen($data['login']) < 3) {
            return 'O login é obrigatório e deve ter pelo menos 3 caracteres';
        }

        $isCreate = $id === null;

        // No cadastro a senha é obrigatória
        if ($isCreate && mb_strlen($data['password'] ?? '') < 8) {
            return 'A senha é obrigatória e deve ter pelo menos 8 caracteres';
        }

        // Na edição a senha é opcional, mas precisa ser válida caso seja preenchida
        if (!$isCreate && $data['password'] !== null && mb_strlen($data['password']) < 8) {
            return 'A senha deve ter pelo menos 8 caracteres';
        }

        if (!is_bool($data['active'])) {
            return 'O campo "active" deve ser booleano (true ou false)';
        }

        // Duplicidade: na edição, ignora o próprio usuário
        if ($this->emailExists($data['email'], $id ?? 0)) {
            return 'O email informado já está em uso, escolha outro.';
        }

        if ($this->loginExists($data['login'], $id ?? 0)) {
            return 'O login informado já está em uso, escolha outro.';
        }

        return null;
    }

    // Registro no banco de dados. Retorna o ID do usuário criado.

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, login, password_hash, active)
             VALUES (:name, :email, :login, :password_hash, :active)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'login' => $data['login'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'active' => (int) $data['active'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE users SET name = :name, email = :email, login = :login, active = :active';
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'login' => $data['login'],
            'active' => (int) $data['active'],
        ];

        // Só altera a senha se uma nova foi enviada
        if ($data['password'] !== null) {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = :id';
        $this->pdo->prepare($sql)->execute($params);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // Métodos privados para checar duplicidade de email e login, ignorando o próprio usuário em caso de edição
    private function emailExists(string $email, int $ignoreId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id != :ignoreId');
        $stmt->execute(['email' => $email, 'ignoreId' => $ignoreId]);
        return $stmt->fetchColumn() > 0;
    }

    private function loginExists(string $login, int $ignoreId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE login = :login AND id != :ignoreId');
        $stmt->execute(['login' => $login, 'ignoreId' => $ignoreId]);
        return $stmt->fetchColumn() > 0;
    }

    // Converte o fomato do active para booleano e o created_at para ISO 8601.
    private function mapRow(array $row): array
    {
        $row['active'] = (bool) $row['active'];
        $row['created_at'] = str_replace(' ', 'T', $row['created_at']) . 'Z';
        return $row;
    }
}
