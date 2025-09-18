# 🚀 GUIA DO DESENVOLVEDOR - CONFIGURAÇÃO E AMBIENTE
**Aprenda PHP-OO com Clean Architecture usando Docker**

## 🎯 OBJETIVO DO APRENDIZADO
Este guia ensina como configurar e desenvolver uma API PHP seguindo:
- **Clean Architecture** (Arquitetura Limpa)
- **Domain-Driven Design** (DDD)  
- **Dependency Injection** (Injeção de Dependência)
- **Repository Pattern** e outros Design Patterns
- **Desenvolvimento em Docker** (ambiente isolado)

---

## 🐳 CONFIGURAÇÃO DO AMBIENTE DOCKER

### **1. Pré-requisitos**
```bash
# Verificar se Docker está instalado
docker --version
docker compose --version

# Verificar se Git está configurado
git config --global user.name "Seu Nome"
git config --global user.email "seu@email.com"
```

### **2. Clonar e Configurar Projeto**
```bash
# 1. Clonar repositório
git clone <url-do-repositorio>
cd project_phpoo_final

# 2. Verificar estrutura inicial
ls -la
# Deve mostrar: docker-compose.yml, Dockerfile, src/, config/, etc.

# 3. Copiar arquivo de configuração
cp .env.example .env
# Editar .env se necessário
```

### **3. Subir Ambiente Docker**
```bash
# 1. Construir e subir containers
docker compose up -d --build

# 2. Verificar containers rodando
docker compose ps
# Deve mostrar: nginx, php-fpm-74, postgres_primary, postgres_slave1, postgres_slave2, memcached

# 3. Testar acesso
curl http://localhost:8080/
# Deve retornar JSON com informações da API
```

### **4. Instalar Dependências PHP**
```bash
# 1. Instalar Composer packages
docker compose run --rm php-cli-74 composer install

# 2. Verificar autoload
docker compose run --rm php-cli-74 composer dump-autoload

# 3. Testar PHP
docker compose run --rm php-cli-74 php --version
# Deve mostrar: PHP 7.4.x
```

---

## 📁 ENTENDENDO A ESTRUTURA DO PROJETO

### **Arquitetura Clean Architecture**
```
src/
├── Domain/                    # 🧠 CAMADA DE DOMÍNIO (Regras de Negócio)
│   ├── Common/               # Componentes compartilhados
│   │   ├── Entities/         # Entidades base
│   │   ├── Repositories/     # Interfaces de repositórios
│   │   ├── Services/         # Interfaces de serviços
│   │   └── Exceptions/       # Exceções de domínio
│   ├── Security/             # Domínio de Segurança (Usuários, Auth)
│   │   ├── Entities/         # UserEntity
│   │   ├── Repositories/     # UserRepository
│   │   ├── Services/         # UserService, AuthService
│   │   └── Validators/       # Validadores de dados
│   └── System/               # Domínio do Sistema
│       └── Services/         # SystemService
├── Application/              # 🌐 CAMADA DE APLICAÇÃO (Use Cases)
│   ├── Common/              # Componentes compartilhados
│   │   ├── Controllers/     # Controller base
│   │   ├── DTOs/           # Data Transfer Objects
│   │   ├── Http/           # HTTP handlers, middlewares
│   │   └── Exceptions/     # Exceções de aplicação
│   └── Modules/            # Módulos da aplicação
│       ├── Auth/           # Módulo de autenticação
│       ├── Security/       # Módulo de usuários
│       └── System/         # Módulo do sistema
└── Common/                 # 🔧 CAMADA DE INFRAESTRUTURA
    └── Database/           # Gerenciamento de banco de dados
```

### **Conceitos Importantes**

#### **🧠 Domain Layer (Camada de Domínio)**
- **Entities**: Objetos de negócio (ex: User, Product)
- **Services**: Lógica de negócio complexa
- **Repositories**: Interfaces para acesso a dados
- **Validators**: Regras de validação de domínio

#### **🌐 Application Layer (Camada de Aplicação)**  
- **Controllers**: Recebem requests HTTP
- **DTOs**: Transferem dados entre camadas
- **Use Cases**: Orquestram operações de domínio
- **Validation Services**: Validam dados de entrada

#### **🔧 Infrastructure Layer (Camada de Infraestrutura)**
- **Database**: Conexões e configurações
- **External APIs**: Integrações externas
- **Cache**: Sistema de cache
- **File System**: Manipulação de arquivos

---

## 🛠️ COMANDOS DOCKER ESSENCIAIS

### **Desenvolvimento Diário**
```bash
# 1. Subir ambiente
docker compose up -d

# 2. Ver logs em tempo real
docker compose logs -f php-fpm-74

# 3. Executar comandos PHP
docker compose run --rm php-cli-74 php script.php

# 4. Acessar container interativo
docker compose exec php-fpm-74 bash

# 5. Parar ambiente
docker compose down
```

### **Debugging e Testes**
```bash
# 1. Testar endpoint específico
curl -X GET http://localhost:8080/api/system/info

# 2. Ver logs de erro
docker compose logs nginx
docker compose logs php-fpm-74

# 3. Verificar banco de dados
docker compose exec postgres_primary psql -U postgres -d projeto_php

# 4. Limpar cache
docker compose run --rm php-cli-74 php -r "
use App\Domain\System\Services\Impl\SystemService;
// código para limpar cache
"
```

### **Manutenção**
```bash
# 1. Rebuild containers
docker compose up -d --build

# 2. Limpar volumes (CUIDADO: apaga dados)
docker compose down -v

# 3. Ver uso de recursos
docker stats

# 4. Limpar sistema Docker
docker system prune -f
```

---

## 📚 CONCEITOS DE APRENDIZADO

### **1. Dependency Injection (DI)**
```php
// ❌ Sem DI - Acoplamento forte
class UserController {
    public function __construct() {
        $this->userService = new UserService(); // Dependência hardcoded
    }
}

// ✅ Com DI - Baixo acoplamento  
class UserController {
    public function __construct(UserServiceInterface $userService) {
        $this->userService = $userService; // Dependência injetada
    }
}
```

### **2. Repository Pattern**
```php
// Interface (Domain)
interface UserRepositoryInterface {
    public function findById(int $id): ?UserEntity;
    public function save(UserEntity $user): UserEntity;
}

// Implementação (Infrastructure)
class UserRepository implements UserRepositoryInterface {
    public function findById(int $id): ?UserEntity {
        // Lógica de acesso ao banco
    }
}
```

### **3. DTO (Data Transfer Object)**
```php
// DTO para transferir dados entre camadas
class CreateUserRequestDTO {
    private string $name;
    private string $email;
    
    public function __construct(string $name, string $email) {
        $this->name = $name;
        $this->email = $email;
    }
    
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
}
```

---

## 🧪 TESTANDO A CONFIGURAÇÃO

### **1. Teste Básico de Conectividade**
```bash
# Verificar se API responde
curl http://localhost:8080/
# Esperado: JSON com informações da API

# Verificar endpoint de sistema
curl http://localhost:8080/api/system/info
# Esperado: JSON com informações do sistema
```

### **2. Teste de Banco de Dados**
```bash
# Testar Doctrine ORM
curl http://localhost:8080/api/system/doctrine-test
# Esperado: JSON confirmando conexão com PostgreSQL
```

### **3. Teste de Endpoints**
```bash
# Listar usuários
curl http://localhost:8080/api/security/users

# Criar usuário (exemplo)
curl -X POST http://localhost:8080/api/security/users \
  -H "Content-Type: application/json" \
  -d '{"name":"João","email":"joao@teste.com","password":"123456","role":"user"}'
```

---

## 🎯 PRÓXIMOS PASSOS

### **Para Iniciantes**
1. 📖 Ler sobre **Clean Architecture**
2. 🔍 Explorar a estrutura de diretórios
3. 🧪 Testar todos os endpoints existentes
4. 📝 Modificar um endpoint simples

### **Para Intermediários**  
1. 🏗️ Criar um novo módulo (ex: Products)
2. 🔧 Implementar CRUD completo
3. 🧪 Adicionar validações customizadas
4. 📊 Implementar paginação

### **Para Avançados**
1. 🚀 Adicionar autenticação JWT
2. 📈 Implementar cache Redis
3. 🔍 Adicionar logging avançado
4. 🧪 Criar testes automatizados

---

## ❗ SOLUÇÃO DE PROBLEMAS COMUNS

### **Erro: "Connection refused"**
```bash
# Verificar se containers estão rodando
docker compose ps

# Restart containers
docker compose restart
```

### **Erro: "Composer not found"**
```bash
# Instalar Composer no container
docker compose run --rm php-cli-74 composer install
```

### **Erro: "Database connection failed"**
```bash
# Verificar PostgreSQL
docker compose logs postgres_primary

# Testar conexão manual
docker compose exec postgres_primary psql -U postgres
```

**🚀 AMBIENTE CONFIGURADO! Agora você está pronto para desenvolver!**

**➡️ PRÓXIMO**: [Guia de Desenvolvimento](DEVELOPER_GUIDE_DEVELOPMENT.md)
