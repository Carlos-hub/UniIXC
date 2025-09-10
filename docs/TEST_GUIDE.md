# Guia de Testes de Funcionamento - Projeto PHP-OO

## ? **Vis�o Geral**

Este guia apresenta um **passo a passo sequencial** para testar o funcionamento completo da infraestrutura do projeto PHP-OO, desde a inicializa��o dos containers at� a valida��o final de todos os servi�os.

> **? Pr�-requisito:** Execute todos os comandos a partir do diret�rio `project_phpoo_final` do seu workspace.

---

## ? **PASSO A PASSO COMPLETO DE TESTES**

### **PASSO 1: Inicializa��o da Infraestrutura**

#### 1.1 Subir os Containers
```bash
# Subir todos os containers
docker compose up -d
```

**?? Tempo esperado:** 2-3 minutos para inicializa��o completa

#### 1.2 Verificar Status dos Containers
```bash
# Verificar se todos os containers est�o UP
docker compose ps
```

**? Resultado Esperado:**
- Todos os containers com status "Up"
- Nenhum container com status "Exited" ou "Restarting"

**? Verifica��o R�pida:**
```bash
# Contar containers UP
docker compose ps | grep "Up" | wc -l
# Deve retornar 18 (containers ativos)
# Nota: Containers php-cli saem automaticamente ap�s execu��o
```

---

### **PASSO 2: Verifica��o de Portas e Conectividade**

#### 2.1 Verificar Portas Expostas
```bash
# Verificar se as portas est�o abertas
netstat -tlnp | grep -E "(8080|8404|11211)"
```

**? Portas Esperadas:**
- `8080`: Nginx Load Balancer (acesso principal)
- `8404`: HAProxy Stats (monitoramento)
- `11211`: Memcached (cache de sess�es)

#### 2.2 Teste de Conectividade B�sica
```bash
# Testar se o servidor web est� respondendo
curl -I http://localhost:8080/
```

**? Resultado Esperado:**
```http
HTTP/1.1 200 OK
Server: nginx/1.29.1
Content-Type: text/html; charset=UTF-8
```

---

### **PASSO 3: Teste do Endpoint Principal**

#### 3.1 Teste B�sico do index.php
```bash
# Fazer requisi��o completa
curl -v http://localhost:8080/
```

**? Resultado Esperado:**
```html
Hello World!<br />PHP Version: [vers�o]<br />Session ID: [id]<br />Valor salvo na sess�o: [n�mero]
```

#### 3.2 Verificar Headers HTTP
```bash
# Verificar headers de resposta
curl -I http://localhost:8080/
```

**? Headers Esperados:**
- `HTTP/1.1 200 OK`
- `Server: nginx/1.29.1`
- `Content-Type: text/html; charset=UTF-8`
- `Set-Cookie: PHPSESSID=[id]; path=/`

#### 3.3 Teste de Sess�es (Memcached)
```bash
# Primeira requisi��o
echo "=== Primeira Requisi��o ==="
curl -s http://localhost:8080/ | grep -E "(Session ID|Valor salvo)"

# Segunda requisi��o (deve manter sess�o)
echo "=== Segunda Requisi��o ==="
curl -s http://localhost:8080/ | grep -E "(Session ID|Valor salvo)"
```

**? Resultado Esperado:**
- Session ID pode ser diferente (load balancing)
- Valor da sess�o deve ser persistido
- Memcached funcionando corretamente

---

### **PASSO 4: Teste do Endpoint de Banco de Dados**

#### 4.1 Teste Completo do test_database.php
```bash
# Fazer requisi��o completa
curl -v http://localhost:8080/test_database.php
```

**? Resultado Esperado:**
```html
<h1>Teste PostgreSQL com PDO</h1>
<h3>Primary Database</h3>
? Conectado (Primary)<br>
? Escrita OK<br>
? Leitura OK ([X] registros)<br>

<h3>HAProxy Database</h3>
? Conectado (Primary)<br>
?? Escrita bloqueada (read-only)<br>
? Leitura OK ([X] registros)<br>

<p>PHP [vers�o] | PDO PostgreSQL: OK | [timestamp]</p>
```

#### 4.2 Verifica��o R�pida de Banco
```bash
# Verificar apenas os status de conex�o
curl -s http://localhost:8080/test_database.php | grep -E "(Conectado|Escrita|Leitura|Erro)"
```

**? Resultado Esperado:**
- ? Conectado (Primary) - PostgreSQL Primary funcionando
- ? Escrita OK - Opera��es de INSERT funcionando
- ? Leitura OK - Opera��es de SELECT funcionando
- ?? Escrita bloqueada (read-only) - HAProxy redirecionando para slave

---

### **PASSO 5: Teste de Load Balancing**

#### 5.1 Verificar Distribui��o de Vers�es PHP
```bash
# Fazer 10 requisi��es para ver distribui��o
echo "=== Teste de Load Balancing ==="
for i in {1..10}; do
  echo -n "Requisi��o $i: "
  curl -s http://localhost:8080/ | grep "PHP Version" | sed 's/<br \/>//'
  sleep 0.5
done
```

**? Resultado Esperado:**
- Diferentes vers�es PHP aparecendo (7.4, 8.0, 8.1, 8.2, 8.3, 8.4)
- Distribui��o relativamente uniforme

#### 5.2 Teste de Performance
```bash
# Teste de carga simples
echo "=== Teste de Performance ==="
time for i in {1..20}; do curl -s http://localhost:8080/ > /dev/null; done
```

**? Resultado Esperado:**
- Tempo total < 10 segundos para 20 requisi��es
- Sem erros de timeout

---

### **PASSO 6: Verifica��o de Servi�os de Banco**

#### 6.1 Teste de PostgreSQL Primary
```bash
# Testar conex�o direta com Primary
echo "=== Teste PostgreSQL Primary ==="
docker exec $(docker compose ps -q postgres_primary) psql -U postgres -d phpoo_app -c "SELECT version();" 2>/dev/null | head -1
```

#### 6.2 Teste de PostgreSQL Slaves
```bash
# Testar conex�o com Slaves
echo "=== Teste PostgreSQL Slaves ==="
docker exec $(docker compose ps -q postgres_slave1) psql -U postgres_slave -d phpoo_app -c "SELECT pg_is_in_recovery();" 2>/dev/null
docker exec $(docker compose ps -q postgres_slave2) psql -U postgres_slave -d phpoo_app -c "SELECT pg_is_in_recovery();" 2>/dev/null
```

**? Resultado Esperado:**
- Primary: `f` (false - n�o � replica)
- Slaves: `t` (true - s�o replicas)

#### 6.3 Teste de HAProxy Stats
```bash
# Verificar interface de estat�sticas do HAProxy
echo "=== Teste HAProxy Stats ==="
curl -s http://localhost:8404/stats | head -5
```

**? Resultado Esperado:**
- Interface de estat�sticas acess�vel
- Dados de conex�es vis�veis

---

### **PASSO 7: Teste de Cache (Memcached)**

#### 7.1 Verificar Status do Memcached
```bash
# Verificar se Memcached est� funcionando
echo "=== Teste Memcached ==="
docker exec $(docker compose ps -q memcached) memcached-tool localhost:11211 stats 2>/dev/null | head -3
```

**? Resultado Esperado:**
- Estat�sticas do Memcached vis�veis
- Servi�o funcionando

---

### **PASSO 8: Valida��o Final**

#### 8.1 Checklist Completo
```bash
echo "=== VALIDA��O FINAL ==="
echo "1. Containers UP:"
docker compose ps | grep "Up" | wc -l
echo "2. Porta 8080 (Nginx):"
curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/
echo "3. Porta 8404 (HAProxy):"
curl -s -o /dev/null -w "%{http_code}" http://localhost:8404/stats
echo "4. Endpoint principal:"
curl -s http://localhost:8080/ | grep -q "Hello World" && echo "OK" || echo "ERRO"
echo "5. Endpoint banco:"
curl -s http://localhost:8080/test_database.php | grep -q "Conectado" && echo "OK" || echo "ERRO"
```

**? Resultado Esperado:**
- 24 containers UP
- HTTP 200 para ambas as portas
- "OK" para ambos os endpoints

#### 8.2 Resumo de Status
```bash
echo "=== RESUMO DE STATUS ==="
echo "? Infraestrutura: $(docker compose ps | grep "Up" | wc -l)/24 containers"
echo "? Web Server: $(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/)"
echo "? Database: $(curl -s http://localhost:8080/test_database.php | grep -c "?") conex�es OK"
echo "? Load Balancing: $(curl -s http://localhost:8080/ | grep -o "PHP Version: [0-9.]*" | head -1)"
```

---

## ? **Health Checks dos Servi�os**

> **? Guia Completo:** Para informa��es detalhadas sobre health checks, consulte o arquivo `docs/HEALTH_CHECKS_GUIDE.md`

### **Status R�pido dos Health Checks**

```bash
# Ver status de todos os containers
docker compose ps

# Ver apenas containers healthy
docker compose ps | grep "healthy"

# Ver apenas containers unhealthy
docker compose ps | grep "unhealthy"
```

### **Verifica��o R�pida**

```bash
# Verificar se todos os servi�os principais est�o healthy
echo "=== Status dos Health Checks ==="
echo "Nginx LB: $(docker inspect $(docker compose ps -q nginx_lb) | jq -r '.[0].State.Health.Status')"
echo "PostgreSQL Primary: $(docker inspect $(docker compose ps -q postgres_primary) | jq -r '.[0].State.Health.Status')"
echo "HAProxy: $(docker inspect $(docker compose ps -q haproxy) | jq -r '.[0].State.Health.Status')"
echo "Memcached: $(docker inspect $(docker compose ps -q memcached) | jq -r '.[0].State.Health.Status')"
```

**? Resultado Esperado:**
- Todos os status devem mostrar "healthy"
- Nenhum status deve mostrar "unhealthy" ou "starting"

---

## ? **Solu��o de Problemas Comuns**

### Problema: HAProxy n�o consegue resolver hostnames
**Sintoma:** `could not translate host name "postgres_slave1" to address`
**Solu��o:**
```bash
docker compose restart postgres_slave1
docker compose restart haproxy
```

### Problema: Containers n�o iniciam
**Sintoma:** Containers com status "Exited"
**Solu��o:**
```bash
docker compose down
docker compose up -d
```

### Problema: Porta 8080 n�o responde
**Sintoma:** `Connection refused` na porta 8080
**Solu��o:**
```bash
docker compose restart nginx_lb
```

---

## ? **Checklist R�pido**

- [ ] `docker compose up -d` executado
- [ ] Todos os containers UP (24/24)
- [ ] Porta 8080 respondendo
- [ ] `curl http://localhost:8080/` retorna "Hello World"
- [ ] `curl http://localhost:8080/test_database.php` retorna "Conectado"
- [ ] Load balancing funcionando (diferentes vers�es PHP)
- [ ] Sess�es persistindo (Memcached)

---

## ? **Notas T�cnicas**

- **Tempo total de teste:** ~5 minutos
- **Containers:** 24 servi�os (PHP multi-vers�o, PostgreSQL, Nginx, HAProxy, Memcached)
- **Portas:** 8080 (web), 8404 (stats), 11211 (cache)
- **PHP:** Vers�es 7.4 a 8.4 com load balancing
- **Banco:** PostgreSQL com replica��o master-slave
- **Cache:** Memcached para sess�es PHP
- **Compatibilidade:** Funciona em qualquer ambiente (caminhos relativos)

---

*Guia atualizado em 2025-09-10 - Infraestrutura testada e funcionando*
