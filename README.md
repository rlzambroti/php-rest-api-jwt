# API REST Acadêmica — Engenharia de Software

Modelo de API REST para acadêmicos do curso de Engenharia de Software.
Implementa autenticação JWT e CRUD de clientes seguindo os padrões
**Clean Code**, **OOP** e **MVC**.

## Tecnologias

| Tecnologia | Versão | Papel |
|---|---|---|
| PHP | 8.1+ | Linguagem principal |
| Slim Framework | 4.x | Microframework para rotas/HTTP |
| PHP-DI | 3.x | Injeção de dependência |
| firebase/php-jwt | 6.x | Geração e validação de JWT |
| swagger-php | 4.x | Geração da documentação OpenAPI |
| MySQL | 5.7+ / 8.x | Banco de dados |

## Arquitetura MVC

```
app/
├── Controllers/   ← Recebe HTTP, delega ao Service, retorna resposta
│   ├── AuthController.php
│   └── ClienteController.php
├── Services/      ← Regras de negócio (validação, lógica)
│   ├── AuthService.php
│   └── ClienteService.php
├── Models/        ← Acesso ao banco de dados (queries)
│   ├── UsuarioModel.php
│   └── ClienteModel.php
├── Middleware/    ← Interceptadores de requisição
│   └── AuthMiddleware.php
└── Database/      ← Conexão com o banco (Singleton)
    └── Connection.php
```

**Fluxo de uma requisição:**
```
HTTP Request
    → Slim Router (routes/api.php)
    → Middleware (AuthMiddleware - verifica JWT)
    → Controller (valida entrada, chama Service)
    → Service (aplica regras de negócio, chama Model)
    → Model (executa query no banco)
    → Resposta JSON
```

## Instalação

### 1. Pré-requisitos
- PHP 8.1+
- Composer
- MySQL (XAMPP)
- Módulo `mod_rewrite` do Apache habilitado

### 2. Instalar dependências

```bash
cd D:/xampp/htdocs/a/v2
composer install
```

### 3. Configurar variáveis de ambiente

```bash
cp .env.example .env
# Edite o .env com suas configurações de banco de dados
```

### 4. Criar o banco de dados

Execute no MySQL:
```bash
mysql -u root -p < database/migration.sql
```

### 5. Popular com dados de teste

```bash
php database/seed.php
```

Isso cria:
- Usuário: `admin` / Senha: `123456`
- 4 clientes de exemplo

### 6. Gerar documentação OpenAPI

```bash
composer docs
# ou
php docs/generate.php
```

### 7. Acessar a API

- **Base URL:** `http://localhost/a/v2/public`
- **Documentação:** `http://localhost/a/v2/public/docs`

## Endpoints

### Autenticação (pública)

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/auth/login` | Login com usuário e senha |
| POST | `/auth/refresh` | Renova os tokens |

### Clientes (requer autenticação)

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/clientes` | Lista todos os clientes |
| POST | `/clientes` | Cria um novo cliente |
| GET | `/clientes/{id}` | Busca cliente por ID |
| PUT | `/clientes/{id}` | Atualiza um cliente |
| DELETE | `/clientes/{id}` | Remove um cliente |

## Fluxo de Autenticação JWT

```
┌──────────┐          ┌─────────────────────────┐
│  Cliente │          │        API              │
└────┬─────┘          └────────────┬────────────┘
     │                             │
     │  POST /auth/login           │
     │  { usuario, senha }         │
     │ ─────────────────────────▶  │
     │                             │
     │  { access_token (15min),    │
     │    refresh_token (7 dias) } │
     │ ◀─────────────────────────  │
     │                             │
     │  GET /clientes              │
     │  Authorization: Bearer ...  │
     │ ─────────────────────────▶  │
     │                             │
     │  [lista de clientes]        │
     │ ◀─────────────────────────  │
     │                             │
     │   (access token expirou)    │
     │                             │
     │  POST /auth/refresh         │
     │  { refresh_token }          │
     │ ─────────────────────────▶  │
     │                             │
     │  { novo access_token,       │
     │    novo refresh_token }     │
     │ ◀─────────────────────────  │
```

## Padrões de Resposta

### Sucesso
```json
{
    "status": "success",
    "data": { ... },
    "message": "Operação realizada com sucesso."
}
```

### Erro
```json
{
    "status": "error",
    "message": "Descrição do erro."
}
```

## Estrutura de Diretórios Completa

```
v2/
├── app/
│   ├── Controllers/
│   ├── Database/
│   ├── Middleware/
│   ├── Models/
│   └── Services/
├── config/
├── database/
├── docs/
├── public/          ← Document root do Apache
├── routes/
├── vendor/          ← Gerado pelo Composer (não versionar)
├── .env             ← Suas configurações (não versionar)
├── .env.example     ← Modelo de configuração
└── composer.json
```

## Segurança

- Senhas armazenadas com `bcrypt` (PHP `password_hash`)
- Tokens JWT assinados com HS256
- Access token de curta duração (15 min)
- Refresh Token Rotation — cada uso gera um novo refresh token
- Prepared Statements em todas as queries (proteção contra SQL Injection)
- Injeção de dependência — sem acoplamento direto entre classes
