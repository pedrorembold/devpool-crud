# Gestão de Usuários

Aplicação full-stack de gestão de usuários. Permite cadastrar, listar, editar e excluir usuários por meio de uma API REST e de uma interface web.

## Tecnologias

**Backend**:  
- PHP 8 (sem framework); 
- Código simples e explícito. O servidor embutido do PHP dispensa configurar Apache ou Nginx.

**Banco de dados**: 
- SQLite; 
- O banco é um único arquivo, sem servidor para instalar.

**Frontend**:
- Vue 3 (via CDN) + HTML + CSS;
- Reatividade do Vue sem etapa de build e sem precisar de Node.js.

## Pré-requisitos

- **PHP 8.1 ou superior**, com as extensões **`pdo_sqlite`** e **`mbstring`** ativadas no `php.ini`
  - Para conferir: `php -m` deve listar `pdo_sqlite` e `mbstring`
- **Git**
- **Conexão com a internet**, para o navegador carregar o Vue pelo CDN

## Como executar

### 1. Clonar o repositório

```bash
git clone https://github.com/pedrorembold/devpool-crud.git
cd devpool-crud
```

### 2. Criar o banco de dados

```bash
php backend/database/init.php
```

O script executa o `backend/database/schema.sql` e cria o arquivo `backend/database/database.sqlite`. Ele pode ser executado novamente sem perder dados (a tabela só é criada se ainda não existir).

### 3. Iniciar a API (backend)

Em um terminal, na raiz do projeto:

```bash
php -S localhost:8000 backend/public/index.php
```

A API fica disponível em `http://localhost:8000`. Mantenha este terminal aberto.

### 4. Iniciar o frontend

Em **outro** terminal, na raiz do projeto:

```bash
php -S localhost:5173 -t frontend
```
Acesse **http://localhost:5173** no navegador.

## Estrutura do projeto

```
devpool-crud/
│   .gitignore
│   README.md
│   
├───backend
│   ├───database/                      
│   │       init.php                  # Cria o banco a partir do schema
│   │       schema.sql                # Estrutura da tabela users
│   ├───public/
│   │       index.php                 # Ponto de entrada: CORS, roteamento e tratamento de erros
│   └───src/
│       │   Database.php              # Conexão com o SQLite (PDO)
│       ├───Controllers/
│       │       UserController.php    # Recebe a requisição e devolve a resposta HTTP
│       └───Models/
│               User.php              # Regras de negócio e acesso ao banco
├───exemplos/
│       criar-user.json               # JSON usado no exemplo de cURL para criação do usuário
│       editar-user.json              # JSON usado no exemplo de cURL para edição do usuário
└───frontend/
        app.js                        # Lógica da interface (Vue)
        index.html                    # Estrutura da página
        style.css                     # Estilos
```

O backend segue o padrão **MVC**: o **Controller** cuida do HTTP, o **Model** concentra as regras de negócio e o acesso ao banco, e a **View** é o frontend em Vue, que consome o JSON da API. O frontend nunca acessa o banco diretamente, toda operação passa pela API.

## Endpoints

| Método | Rota | Descrição | Sucesso | Erros |
|---|---|---|---|---|
| `GET` | `/users` | Lista os usuários | `200` | — |
| `GET` | `/users/{id}` | Detalha um usuário | `200` | `404` |
| `POST` | `/users` | Cadastra um usuário | `201` | `400` |
| `PUT` | `/users/{id}` | Edita um usuário | `200` | `400`, `404` |
| `DELETE` | `/users/{id}` | Exclui um usuário (soft delete) | `204` | `404` |

A senha é armazenada com hash **bcrypt** e nunca é devolvida pela API.

## Exemplos de chamadas (cURL)

> **Se for rodar no PowerShell:** use `curl.exe` no lugar de `curl`.
> Os exemplos que enviam JSON usam os arquivos da pasta `exemplos/`, o que evita problemas com aspas no terminal.

**Listar usuários**

```bash
curl -i http://localhost:8000/users
```

**Buscar um usuário específico**

```bash
curl -i http://localhost:8000/users/1
```

**Cadastrar um usuário**

```bash
curl -i -X POST http://localhost:8000/users -H "Content-Type: application/json" -d "@exemplos/criar-user.json"
```

Corpo enviado (`exemplos/criar-user.json`):

```json
{
  "name": "Ana Souza",
  "email": "ana@email.com",
  "login": "anasouza",
  "password": "senha1234",
  "active": true
}
```

Resposta (`201 Created`):

```json
{
  "id": 1,
  "name": "Ana Souza",
  "email": "ana@email.com",
  "login": "anasouza",
  "active": true,
  "created_at": "2026-11-19T11:30:00Z"
}
```

**Editar um usuário**

```bash
curl -i -X PUT http://localhost:8000/users/1 -H "Content-Type: application/json" -d "@exemplos/editar-user.json"
```
Corpo enviado (`exemplos/editar-user.json`):

​```json
{
  "name": "Ana Souza",
  "email": "ana@email.com",
  "login": "anasouzaeditado"
}
​```

Na edição, `password` e `active` são opcionais: se não forem enviados, os valores atuais são mantidos.

**Excluir um usuário**

```bash
curl -i -X DELETE http://localhost:8000/users/1
```

**Exemplo de erro de validação** (`400 Bad Request`):

```json
{
  "error": "O nome é obrigatório e deve ter pelo menos 3 caracteres"
}
```

## Decisões técnicas

- **Exclusão lógica (soft delete):** a exclusão preenche a coluna `deleted_at` em vez de apagar o registro, o que mantém o histórico e permite recuperar o usuário. Não foi usado o campo `active` para isso porque ele representa a situação operacional do usuário: um usuário inativo continua existindo, um excluído não. O e-mail e o login de um usuário excluído continuam reservados para que a recuperação não gere conflito.
- **Datas em UTC:** a API devolve as datas no formato ISO 8601 em UTC (ex.: `2026-11-19T11:30:00Z`). O frontend converte para o fuso horário de quem está usando.
- **Validações no backend:** todas as regras ficam no Model. O frontend exibe as mensagens de erro devolvidas pela API.
- **Segurança:** consultas com prepared statements para proteção contra SQL injection, senha com hash bcrypt, campo de senha fora de todas as respostas, versão do PHP oculta nos cabeçalhos e erros inesperados respondidos com uma mensagem genérica (`500`), sem expor detalhes internos.
- **CORS:** a API libera o acesso de outras origens e responde às requisições de preflight (`OPTIONS`), pois o frontend e a API rodam em portas diferentes.