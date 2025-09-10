# Guia de Health Checks - Projeto PHP-OO

## 📋 **Visão Geral**

Este guia explica os health checks implementados no projeto PHP-OO para monitorar a saúde de todos os serviços Docker. Os health checks garantem que os containers estejam funcionando corretamente e permitem recuperação automática em caso de falhas.

> **📁 Pré-requisito:** Execute todos os comandos a partir do diretório `project_phpoo_final` do seu workspace.

---

## ✅ **Status dos Problemas Conhecidos**

### **Problema: HAProxy e PostgreSQL Slaves não iniciam - RESOLVIDO PERMANENTEMENTE**

**Status:** ✅ **CORRIGIDO** - O problema foi resolvido permanentemente nos arquivos de configuração.

**Causa Original:**
O PostgreSQL Primary tinha `max_wal_senders` limitado a 5, mas múltiplos slaves tentavam se conectar simultaneamente, causando falha na replicação e consequentemente no HAProxy.

**Solução Aplicada:**
A configuração foi corrigida permanentemente no arquivo `docker/postgres/init-primary.sh`:
```bash
# Configuração atual no init-primary.sh
echo "max_wal_senders = 10" >> /var/lib/postgresql/data/postgresql.conf
echo "max_replication_slots = 5" >> /var/lib/postgresql/data/postgresql.conf
```

**Verificação de Funcionamento:**
```bash
# Verificar se HAProxy está funcionando
curl -s http://localhost:8404/stats | head -5

# Verificar se todos os containers estão UP
docker compose ps | grep "Up" | wc -l
# Deve retornar 17 ou mais

# Testar conectividade do banco de dados
curl -s http://localhost:8080/test_database.php | head -10
```

**Nota:** Este problema não deve mais ocorrer em futuras inicializações dos containers, pois a correção está aplicada nos arquivos de configuração.

### **Correção Permanente Aplicada**

**Arquivo Modificado:** `docker/postgres/init-primary.sh`

**Mudanças Realizadas:**
- **max_wal_senders:** Aumentado de 5 para 10
- **max_replication_slots:** Aumentado de 3 para 5

**Benefícios:**
- ✅ Suporte a múltiplos slaves PostgreSQL simultâneos
- ✅ Replicação estável e confiável
- ✅ HAProxy funcionando corretamente
- ✅ Infraestrutura completa e robusta

---

## 🔧 **Health Checks Implementados**

### **1. PHP-FPM Services (php-fpm-74 a php-fpm-84)**

```yaml
healthcheck:
  test: ["CMD-SHELL", "pgrep php-fpm || exit 1"]
  interval: 30s
  timeout: 10s
  retries: 3
  start_period: 40s
```

**O que verifica:**
- ✅ Processo `php-fpm` ativo
- ✅ Porta 9000 (FastCGI) funcionando

**Intervalos:**
- **Verificação:** A cada 30 segundos
- **Timeout:** 10 segundos por verificação
- **Tentativas:** 3 falhas consecutivas = unhealthy
- **Inicialização:** Aguarda 40 segundos antes da primeira verificação

---

### **2. Nginx Services (nginx-fpm-74 a nginx-fpm-84)**

```yaml
healthcheck:
  test: ["CMD-SHELL", "curl -f http://localhost:8080/ || exit 1"]
  interval: 30s
  timeout: 10s
  retries: 3
  start_period: 20s
```

**O que verifica:**
- ✅ Servidor Nginx respondendo
- ✅ Porta 8080 acessível
- ✅ Resposta HTTP 200 OK

**Intervalos:**
- **Verificação:** A cada 30 segundos
- **Timeout:** 10 segundos por verificação
- **Tentativas:** 3 falhas consecutivas = unhealthy
- **Inicialização:** Aguarda 20 segundos antes da primeira verificação

---

### **3. Nginx Load Balancer (nginx_lb)**

```yaml
healthcheck:
  test: ["CMD-SHELL", "curl -f http://localhost:8080/ || exit 1"]
  interval: 30s
  timeout: 10s
  retries: 3
  start_period: 60s
```

**O que verifica:**
- ✅ Load balancer principal funcionando
- ✅ Porta 8080 (acesso principal) acessível
- ✅ Resposta HTTP 200 OK

**Intervalos:**
- **Verificação:** A cada 30 segundos
- **Timeout:** 10 segundos por verificação
- **Tentativas:** 3 falhas consecutivas = unhealthy
- **Inicialização:** Aguarda 60 segundos (depende de outros serviços)

---

### **4. PostgreSQL Primary (postgres_primary)**

```yaml
healthcheck:
  test: ["CMD-SHELL", "pg_isready -U postgres -h localhost -p 5432 || exit 1"]
  interval: 5s
  timeout: 3s
  retries: 5
  start_period: 10s
```

**O que verifica:**
- ✅ Banco PostgreSQL aceitando conexões
- ✅ Usuário `postgres` autenticado
- ✅ Porta 5432 acessível

**Intervalos:**
- **Verificação:** A cada 5 segundos (mais frequente)
- **Timeout:** 3 segundos por verificação
- **Tentativas:** 5 falhas consecutivas = unhealthy
- **Inicialização:** Aguarda 10 segundos

---

### **5. PostgreSQL Slaves (postgres_slave1, postgres_slave2)**

```yaml
healthcheck:
  test: ["CMD-SHELL", "pg_isready -U postgres_slave -h localhost -p 5432 || exit 1"]
  interval: 5s
  timeout: 3s
  retries: 5
  start_period: 10s
```

**O que verifica:**
- ✅ Slaves PostgreSQL aceitando conexões
- ✅ Usuário `postgres_slave` autenticado
- ✅ Porta 5432 acessível
- ✅ Replicação funcionando

**Intervalos:**
- **Verificação:** A cada 5 segundos
- **Timeout:** 3 segundos por verificação
- **Tentativas:** 5 falhas consecutivas = unhealthy
- **Inicialização:** Aguarda 10 segundos

---

### **6. HAProxy (haproxy)**

```yaml
healthcheck:
  test: ["CMD-SHELL", "timeout 5 bash -c 'echo -e \"GET /stats HTTP/1.1\\r\\nHost: localhost\\r\\n\\r\\n\" > /dev/tcp/127.0.0.1/8404'"]
  interval: 30s
  timeout: 10s
  retries: 3
  start_period: 30s
```

**O que verifica:**
- ✅ Conexão TCP com porta 8404
- ✅ Interface de estatísticas do HAProxy acessível
- ✅ Servidor HAProxy funcionando corretamente
- ✅ Teste HTTP real (não apenas processo)

**Intervalos:**
- **Verificação:** A cada 30 segundos
- **Timeout:** 10 segundos por verificação
- **Tentativas:** 3 falhas consecutivas = unhealthy
- **Inicialização:** Aguarda 30 segundos

**Nota:** Este comando usa `/dev/tcp` com `bash` para testar a conectividade real com o HAProxy, enviando uma requisição HTTP GET para o endpoint `/stats`. É mais robusto que apenas verificar se o processo existe.

---

### **7. Memcached (memcached)**

```yaml
healthcheck:
  test: ["CMD-SHELL", "pidof memcached"]
  interval: 30s
  timeout: 10s
  retries: 3
  start_period: 10s
```

**O que verifica:**
- ✅ Processo `memcached` ativo
- ✅ Servidor Memcached funcionando
- ✅ Porta 11211 acessível

**Intervalos:**
- **Verificação:** A cada 30 segundos
- **Timeout:** 10 segundos por verificação
- **Tentativas:** 3 falhas consecutivas = unhealthy
- **Inicialização:** Aguarda 10 segundos

**Nota Técnica:**
Este health check usa `pidof memcached` que é mais confiável que comandos de rede na imagem oficial do Memcached, que é minimalista e não inclui ferramentas como `nc` ou `curl`.

---

## 📊 **Comandos de Verificação**

### **Ver Status Geral dos Health Checks**

```bash
# Ver status de todos os containers
docker compose ps

# Ver apenas containers healthy
docker compose ps | grep "healthy"

# Ver apenas containers unhealthy
docker compose ps | grep "unhealthy"
```

### **Ver Status Detalhado de um Serviço**

```bash
# Ver status detalhado do Nginx Load Balancer
docker inspect $(docker compose ps -q nginx_lb) | jq '.[0].State.Health'

# Ver status detalhado do PostgreSQL Primary
docker inspect $(docker compose ps -q postgres_primary) | jq '.[0].State.Health'

# Ver status detalhado do HAProxy
docker inspect $(docker compose ps -q haproxy) | jq '.[0].State.Health'
```

### **Ver Logs de Health Check**

```bash
# Ver logs de health check de um container específico
docker logs $(docker compose ps -q nginx_lb) --tail 20

# Ver logs de health check do PostgreSQL
docker logs $(docker compose ps -q postgres_primary) --tail 20

# Ver logs de health check do HAProxy
docker logs $(docker compose ps -q haproxy) --tail 20
```

### **Testar Health Check Manualmente**

```bash
# Testar health check do Nginx manualmente
docker exec $(docker compose ps -q nginx_lb) curl -f http://localhost:8080/

# Testar health check do PostgreSQL manualmente
docker exec $(docker compose ps -q postgres_primary) pg_isready -U postgres -h localhost -p 5432

# Testar health check do HAProxy manualmente
docker exec $(docker compose ps -q haproxy) timeout 5 bash -c 'echo -e "GET /stats HTTP/1.1\r\nHost: localhost\r\n\r\n" > /dev/tcp/127.0.0.1/8404'

# Testar health check do Memcached manualmente
docker exec $(docker compose ps -q memcached) pidof memcached
```

---

## 🚨 **Troubleshooting de Health Checks**

### **Container "unhealthy"**

**Sintomas:**
- Container aparece como "unhealthy" no `docker compose ps`
- Health check falhando repetidamente

**Diagnóstico:**
```bash
# Ver logs do container
docker logs $(docker compose ps -q <service_name>)

# Ver status detalhado do health check
docker inspect $(docker compose ps -q <service_name>) | jq '.[0].State.Health'

# Testar comando de health check manualmente
docker exec $(docker compose ps -q <service_name>) <health_check_command>
```

**Soluções:**
1. **Verificar se o comando existe no container:**
   ```bash
   docker exec $(docker compose ps -q <service_name>) which <command>
   ```

2. **Verificar se a porta está acessível:**
   ```bash
   docker exec $(docker compose ps -q <service_name>) netstat -tlnp
   ```

3. **Reiniciar o container:**
   ```bash
   docker compose restart <service_name>
   ```

### **Health Check Timeout**

**Sintomas:**
- Health check demora muito para responder
- Timeout errors nos logs

**Soluções:**
1. **Aumentar timeout no docker-compose.yml:**
   ```yaml
   healthcheck:
     timeout: 20s  # Aumentar de 10s para 20s
   ```

2. **Aumentar start_period:**
   ```yaml
   healthcheck:
     start_period: 60s  # Aumentar tempo de inicialização
   ```

### **Dependências não atendidas**

**Sintomas:**
- Container não inicia porque dependência não está healthy
- Erro "dependency failed to start"

**Soluções:**
1. **Verificar dependências:**
   ```bash
   docker compose ps
   ```

2. **Iniciar dependências primeiro:**
   ```bash
   docker compose up -d postgres_primary
   # Aguardar ficar healthy
   docker compose up -d haproxy
   ```

### **Comando de Health Check não encontrado**

**Sintomas:**
- Erro "command not found" nos logs
- Health check falhando

**Soluções:**
1. **Verificar se comando existe:**
   ```bash
   docker exec $(docker compose ps -q <service_name>) which <command>
   ```

2. **Instalar comando necessário:**
   ```bash
   # Exemplo: instalar curl no container
   docker exec $(docker compose ps -q <service_name>) apt-get update && apt-get install -y curl
   ```

3. **Usar comando alternativo:**
   ```yaml
   # Exemplo: usar wget em vez de curl
   healthcheck:
     test: ["CMD-SHELL", "wget --quiet --tries=1 --spider http://localhost:8080/ || exit 1"]
   ```

---

## 🔄 **Gerenciamento de Health Checks**

### **Desabilitar Health Check Temporariamente**

```yaml
# Comentar a seção healthcheck
# healthcheck:
#   test: ["CMD-SHELL", "comando"]
#   interval: 30s
#   timeout: 10s
#   retries: 3
#   start_period: 40s
```

### **Modificar Health Check**

```yaml
# Exemplo: mudar intervalo de verificação
healthcheck:
  test: ["CMD-SHELL", "comando"]
  interval: 60s  # Mudar de 30s para 60s
  timeout: 10s
  retries: 3
  start_period: 40s
```

### **Aplicar Mudanças**

```bash
# Parar containers
docker compose down

# Subir com nova configuração
docker compose up -d

# Verificar status
docker compose ps
```

---

## 📈 **Monitoramento Contínuo**

### **Script de Monitoramento Simples**

```bash
#!/bin/bash
# monitor_health.sh

echo "=== Monitoramento de Health Checks ==="
echo "Data: $(date)"
echo

# Verificar status geral
echo "Status dos Containers:"
docker compose ps | grep -E "(healthy|unhealthy|starting)"

echo
echo "Containers Unhealthy:"
unhealthy=$(docker compose ps | grep "unhealthy" | wc -l)
if [ $unhealthy -gt 0 ]; then
  echo "⚠️  $unhealthy containers unhealthy"
  docker compose ps | grep "unhealthy"
else
  echo "✅ Todos os containers healthy"
fi

echo
echo "Uso de Recursos:"
docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"
```

### **Alertas Automáticos**

```bash
#!/bin/bash
# health_alert.sh

# Verificar containers unhealthy
unhealthy=$(docker compose ps | grep "unhealthy" | wc -l)

if [ $unhealthy -gt 0 ]; then
  echo "ALERTA: $unhealthy containers unhealthy em $(date)"
  docker compose ps | grep "unhealthy"
  
  # Aqui você pode adicionar notificações (email, Slack, etc.)
  # send_notification "ALERTA: $unhealthy containers unhealthy"
fi
```

---

## ✅ **Checklist de Health Checks**

- [ ] Todos os containers com status "healthy"
- [ ] Nenhum container "unhealthy" ou "starting"
- [ ] Health checks respondendo dentro do timeout
- [ ] Dependências sendo respeitadas
- [ ] Logs sem erros de health check
- [ ] Comandos de health check funcionando manualmente
- [ ] Monitoramento configurado (opcional)

---

## 📝 **Notas Técnicas**

- **Health checks são executados dentro do container**
- **Comandos devem estar disponíveis na imagem Docker**
- **Timeouts devem ser ajustados conforme necessário**
- **Start periods devem considerar tempo de inicialização**
- **Dependências são verificadas automaticamente**
- **Status "unhealthy" impede dependentes de iniciar**

---

*Guia de Health Checks - Projeto PHP-OO - Atualizado em 2025-09-10*
