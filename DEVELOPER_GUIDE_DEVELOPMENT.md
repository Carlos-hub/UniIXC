# 💻 GUIA DO DESENVOLVEDOR - DESENVOLVIMENTO PRÁTICO
**Aprendendo a Desenvolver com Clean Architecture em PHP**

## 🎯 OBJETIVO
Ensinar como desenvolver funcionalidades seguindo Clean Architecture, com exemplos práticos e exercícios progressivos.

---

## 🏗️ ANATOMIA DE UM MÓDULO

### **Estrutura Completa de um Módulo**
```
Módulo "Security" (Usuários):

Domain/Security/                    # 🧠 REGRAS DE NEGÓCIO
├── Entities/Impl/UserEntity.php    # Entidade User
├── Repositories/                   # Interfaces de dados
│   ├── UserRepositoryInterface.php
│   └── Impl/UserRepository.php
├── Services/                       # Lógica de negócio
│   ├── UserServiceInterface.php
│   └── Impl/UserService.php
└── Validators/Impl/                # Validações de domínio
    └── UserDataValidator.php

Application/Modules/Security/       # 🌐 CASOS DE USO
├── Controllers/Impl/               # Recebe requests HTTP
│   └── UserController.php
├── Bootstrap/Impl/                 # Configuração DI
│   └── UserControllerDefinition.php
└── Validators/Impl/                # Validação de entrada
    └── UserValidationService.php

Application/Common/DTOs/User/       # 📦 TRANSFERÊNCIA DE DADOS
├── Impl/
│   ├── CreateUserRequestDTO.php    # Dados para criar usuário
│   └── UpdateUserRequestDTO.php    # Dados para atualizar
├── CreateUserRequestDTOInterface.php
└── UpdateUserRequestDTOInterface.php
```

---

## 🔧 DESENVOLVIMENTO PASSO A PASSO

### **PASSO 1: Criar Entidade (Domain)**

#### **1.1 Definir UserEntity**
```bash
# Arquivo: src/Domain/Security/Entities/Impl/UserEntity.php
docker compose run --rm php-cli-74 bash
# Dentro do container:
mkdir -p /opt/project/src/Domain/Security/Entities/Impl
```

```php
<?php
// UserEntity.php - Representa um usuário no sistema
declare(strict_types=1);

namespace App\Domain\Security\Entities\Impl;

use JsonSerializable;
use DateTime;

class UserEntity implements JsonSerializable
{
    private ?int $id = null;
    private string $name;
    private string $email;
    private string $password;
    private string $role;
    private string $status;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        string $name,
        string $email, 
        string $password,
        string $role = 'user'
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->role = $role;
        $this->status = 'active';
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    // Getters e Setters...
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    
    // JsonSerializable para API responses
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
```

**🎓 Conceitos Aprendidos:**
- **Entidade**: Objeto de negócio com identidade
- **Encapsulamento**: Propriedades privadas com getters/setters
- **JsonSerializable**: Interface para serialização automática
- **Type Hints**: Tipagem forte do PHP 7.4

---

### **PASSO 2: Criar Repository (Domain)**

#### **2.1 Interface do Repository**
```php
<?php
// UserRepositoryInterface.php - Contrato para acesso a dados
declare(strict_types=1);

namespace App\Domain\Security\Repositories;

use App\Domain\Security\Entities\Impl\UserEntity;

interface UserRepositoryInterface
{
    public function findById(int $id): ?UserEntity;
    public function findByEmail(string $email): ?UserEntity;
    public function save(UserEntity $user): UserEntity;
    public function update(UserEntity $user): UserEntity;
    public function delete(int $id): bool;
    public function findAll(): array;
}
```

#### **2.2 Implementação do Repository**
```php
<?php
// UserRepository.php - Implementação com Doctrine
declare(strict_types=1);

namespace App\Domain\Security\Repositories\Impl;

use App\Domain\Security\Entities\Impl\UserEntity;
use App\Domain\Security\Repositories\UserRepositoryInterface;
use Doctrine\DBAL\Connection;

final class UserRepository implements UserRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findById(int $id): ?UserEntity
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        $result = $this->connection->fetchAssociative($sql, [$id]);
        
        if (!$result) {
            return null;
        }
        
        return $this->mapToEntity($result);
    }

    public function save(UserEntity $user): UserEntity
    {
        $sql = 'INSERT INTO users (name, email, password, role, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id';
        
        $result = $this->connection->fetchAssociative($sql, [
            $user->getName(),
            $user->getEmail(),
            $user->getPassword(),
            $user->getRole(),
            $user->getStatus(),
            $user->getCreatedAt()->format('Y-m-d H:i:s'),
            $user->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);
        
        // Definir ID na entidade
        $user->setId($result['id']);
        return $user;
    }

    private function mapToEntity(array $data): UserEntity
    {
        // Mapear dados do banco para entidade
        $user = new UserEntity($data['name'], $data['email'], '', $data['role']);
        $user->setId($data['id']);
        // ... outros setters
        return $user;
    }
}
```

**🎓 Conceitos Aprendidos:**
- **Repository Pattern**: Abstrai acesso a dados
- **Dependency Injection**: Repository recebe Connection
- **Interface Segregation**: Interface específica para User
- **Data Mapping**: Conversão array → Entity

---

### **PASSO 3: Criar Service (Domain)**

#### **3.1 Service de Domínio**
```php
<?php
// UserService.php - Lógica de negócio
declare(strict_types=1);

namespace App\Domain\Security\Services\Impl;

use App\Domain\Security\Entities\Impl\UserEntity;
use App\Domain\Security\Repositories\UserRepositoryInterface;
use App\Domain\Security\Services\UserServiceInterface;
use App\Domain\Common\Exceptions\BusinessLogicException;

final class UserService implements UserServiceInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function createUser(string $name, string $email, string $password, string $role): UserEntity
    {
        // 1. Validar regras de negócio
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            throw new BusinessLogicException('Email já está em uso');
        }

        // 2. Criar entidade
        $user = new UserEntity($name, $email, $password, $role);

        // 3. Salvar no repositório
        return $this->userRepository->save($user);
    }

    public function getUserById(int $id): ?UserEntity
    {
        return $this->userRepository->findById($id);
    }

    public function updateUser(int $id, array $data): UserEntity
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new BusinessLogicException('Usuário não encontrado');
        }

        // Aplicar mudanças
        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        if (isset($data['status'])) {
            $user->setStatus($data['status']);
        }

        return $this->userRepository->update($user);
    }
}
```

**🎓 Conceitos Aprendidos:**
- **Service Layer**: Orquestra operações de domínio
- **Business Logic**: Regras como "email único"
- **Exception Handling**: Lança exceções específicas
- **Entity Lifecycle**: Create → Validate → Save

---

### **PASSO 4: Criar DTOs (Application)**

#### **4.1 DTO para Criação**
```php
<?php
// CreateUserRequestDTO.php - Dados para criar usuário
declare(strict_types=1);

namespace App\Application\Common\DTOs\User\Impl;

use App\Application\Common\DTOs\User\CreateUserRequestDTOInterface;

final class CreateUserRequestDTO implements CreateUserRequestDTOInterface
{
    private string $name;
    private string $email;
    private string $password;
    private string $role;

    public function __construct(string $name, string $email, string $password, string $role)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? '',
            $data['email'] ?? '',
            $data['password'] ?? '',
            $data['role'] ?? 'user'
        );
    }

    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getRole(): string { return $this->role; }
}
```

**🎓 Conceitos Aprendidos:**
- **DTO Pattern**: Transfere dados entre camadas
- **Static Factory**: `fromArray()` cria DTO de request
- **Immutable Objects**: Propriedades não mudam após criação
- **Data Validation**: Valores padrão e sanitização

---

### **PASSO 5: Criar Controller (Application)**

#### **5.1 Controller HTTP**
```php
<?php
// UserController.php - Recebe requests HTTP
declare(strict_types=1);

namespace App\Application\Modules\Security\Controllers\Impl;

use App\Application\Common\Controllers\Impl\BaseController;
use App\Domain\Security\Services\UserServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserController extends BaseController
{
    private UserServiceInterface $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            // 1. Validar dados de entrada
            $data = $request->getParsedBody();
            $createUserRequest = CreateUserRequestDTO::fromArray($data);

            // 2. Executar caso de uso
            $user = $this->userService->createUser(
                $createUserRequest->getName(),
                $createUserRequest->getEmail(),
                $createUserRequest->getPassword(),
                $createUserRequest->getRole()
            );

            // 3. Retornar resposta
            $apiResponse = $this->success($user, 'Usuário criado com sucesso');
            $response->getBody()->write($apiResponse->toJson());
            return $response->withHeader('Content-Type', 'application/json')
                          ->withStatus($apiResponse->getCode());

        } catch (ValidationException $e) {
            $apiResponse = $this->badRequest($e->getMessage());
            $response->getBody()->write($apiResponse->toJson());
            return $response->withHeader('Content-Type', 'application/json')
                          ->withStatus($apiResponse->getCode());
        }
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        try {
            $id = (int) ($args['id'] ?? $request->getAttribute('id'));
            $user = $this->userService->getUserById($id);

            if (!$user) {
                $apiResponse = $this->notFound('Usuário não encontrado');
                $response->getBody()->write($apiResponse->toJson());
                return $response->withHeader('Content-Type', 'application/json')
                              ->withStatus($apiResponse->getCode());
            }

            $apiResponse = $this->success($user);
            $response->getBody()->write($apiResponse->toJson());
            return $response->withHeader('Content-Type', 'application/json')
                          ->withStatus($apiResponse->getCode());

        } catch (Exception $e) {
            $apiResponse = $this->serverError('Erro interno: ' . $e->getMessage());
            $response->getBody()->write($apiResponse->toJson());
            return $response->withHeader('Content-Type', 'application/json')
                          ->withStatus($apiResponse->getCode());
        }
    }
}
```

**🎓 Conceitos Aprendidos:**
- **Controller Pattern**: Orquestra request/response
- **HTTP Status Codes**: 200, 400, 404, 500
- **Error Handling**: Try/catch com diferentes exceções
- **Response Formatting**: JSON padronizado

---

## 🧪 TESTANDO NO DOCKER

### **1. Testar Criação de Usuário**
```bash
# Criar usuário via API
curl -X POST http://localhost:8080/api/security/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@teste.com", 
    "password": "123456",
    "role": "user"
  }'

# Resposta esperada:
{
  "success": true,
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@teste.com",
    "role": "user",
    "status": "active",
    "created_at": "2024-01-15 10:30:00"
  },
  "message": "Usuário criado com sucesso"
}
```

### **2. Testar Busca de Usuário**
```bash
# Buscar usuário por ID
curl http://localhost:8080/api/security/users/1

# Listar todos os usuários
curl http://localhost:8080/api/security/users
```

### **3. Debug no Container**
```bash
# Acessar logs em tempo real
docker compose logs -f php-fpm-74

# Executar PHP interativo
docker compose run --rm php-cli-74 php -a

# Testar classe diretamente
docker compose run --rm php-cli-74 php -r "
require_once 'vendor/autoload.php';
use App\Domain\Security\Entities\Impl\UserEntity;
\$user = new UserEntity('Teste', 'teste@teste.com', '123456');
echo json_encode(\$user) . PHP_EOL;
"
```

---

## 🎯 EXERCÍCIOS PRÁTICOS

### **Exercício 1: Adicionar Campo**
**Objetivo**: Adicionar campo "telefone" ao usuário

1. **Modificar UserEntity**: Adicionar propriedade `$phone`
2. **Atualizar Repository**: Incluir phone no SQL
3. **Modificar DTO**: Adicionar getPhone()
4. **Testar**: Criar usuário com telefone

### **Exercício 2: Validação Customizada**
**Objetivo**: Validar formato de email

1. **Criar Validator**: `EmailValidator.php`
2. **Usar no Service**: Validar antes de salvar
3. **Testar**: Enviar email inválido
4. **Verificar**: Deve retornar erro 400

### **Exercício 3: Endpoint de Atualização**
**Objetivo**: Implementar PUT /users/{id}

1. **Criar UpdateUserRequestDTO**
2. **Implementar UserController::update()**
3. **Testar com curl**
4. **Validar resposta**

### **Exercício 4: Paginação**
**Objetivo**: Listar usuários com paginação

1. **Modificar Repository**: Adicionar LIMIT/OFFSET
2. **Criar PaginationDTO**
3. **Atualizar Controller::index()**
4. **Testar**: `GET /users?page=1&limit=10`

---

## 📚 CONCEITOS AVANÇADOS

### **1. Exception Handling**
```php
// Hierarquia de exceções
BusinessLogicException          # Regras de negócio
├── UserAlreadyExistsException  # Email duplicado
├── UserNotFoundException       # Usuário não encontrado
└── InvalidPasswordException    # Senha inválida

ValidationException             # Dados inválidos
├── InvalidEmailException       # Email malformado
└── RequiredFieldException      # Campo obrigatório
```

### **2. Dependency Injection Container**
```php
// config/container.php
return [
    UserRepositoryInterface::class => DI\autowire(UserRepository::class),
    UserServiceInterface::class => DI\autowire(UserService::class),
    UserController::class => DI\autowire(UserController::class),
];
```

### **3. Middleware Stack**
```php
// Pipeline de request
Request → CORS → Validation → Controller → Response
        ↓         ↓           ↓           ↓
      Headers   Validate    Execute    Format
               Input       Business    JSON
                          Logic
```

---

## 🚀 PRÓXIMOS PASSOS AVANÇADOS

### **Para Continuar Aprendendo:**
1. 🔐 **Autenticação JWT**: Implementar login com tokens
2. 📊 **Cache Redis**: Adicionar cache em endpoints
3. 🔍 **Logging**: Adicionar logs estruturados
4. 🧪 **Testes**: PHPUnit para testes automatizados
5. 📈 **Monitoring**: Health checks e métricas
6. 🔄 **Events**: Sistema de eventos de domínio
7. 🚀 **CQRS**: Separar commands de queries

**🎓 PARABÉNS! Você aprendeu os fundamentos de Clean Architecture em PHP!**

**➡️ Continue praticando criando novos módulos e funcionalidades!**
