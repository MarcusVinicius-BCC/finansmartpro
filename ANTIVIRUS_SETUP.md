# 🛡️ INSTALAÇÃO DO ANTIVÍRUS SCANNER
## FinanSmart Pro - Sistema de Proteção contra Malware

---

## 📋 SOBRE O SISTEMA

O **AntivirusScanner** protege seu sistema contra uploads maliciosos através de:

1. **ClamAV** - Antivírus open-source profissional (recomendado)
2. **Windows Defender** - Antivírus nativo do Windows
3. **Validação Manual** - Fallback quando não há antivírus instalado

---

## ✅ O QUE JÁ ESTÁ FUNCIONANDO

### Validação Manual (Ativa por padrão)
Mesmo sem antivírus instalado, o sistema já protege contra:
- ✅ **Assinatura de arquivo inválida** (magic bytes)
- ✅ **Scripts PHP/JS embutidos** em imagens
- ✅ **Tags perigosas**: `<?php`, `<script>`, `eval()`, `base64_decode()`
- ✅ **Comandos maliciosos**: `exec()`, `system()`, `shell_exec()`
- ✅ **Null bytes** (técnica de bypass)
- ✅ **Arquivos suspeitos** (tamanhos anormais)

**Status**: 🟢 PROTEÇÃO BÁSICA ATIVA

---

## 🚀 INSTALAÇÃO DO CLAMAV (RECOMENDADO)

### 🐧 Linux (Ubuntu/Debian)

```bash
# 1. Instalar ClamAV
sudo apt update
sudo apt install clamav clamav-daemon -y

# 2. Atualizar definições de vírus
sudo freshclam

# 3. Iniciar serviço
sudo systemctl start clamav-daemon
sudo systemctl enable clamav-daemon

# 4. Verificar se está rodando
sudo systemctl status clamav-daemon

# 5. Testar conexão
echo "PING" | nc -U /var/run/clamav/clamd.ctl
# Deve retornar: PONG
```

**Configuração PHP**:
```php
// No arquivo includes/AntivirusScanner.php (já configurado)
private static $clamavSocket = '/var/run/clamav/clamd.ctl';
```

---

### 🪟 Windows

```powershell
# 1. Baixar ClamAV
# https://www.clamav.net/downloads

# 2. Instalar em: C:\Program Files\ClamAV

# 3. Criar arquivo de configuração
# C:\Program Files\ClamAV\clamd.conf

# Conteúdo:
TCPSocket 3310
TCPAddr 127.0.0.1
LogFile C:\ClamAV\Logs\clamd.log
DatabaseDirectory C:\ClamAV\Database

# 4. Atualizar definições
cd "C:\Program Files\ClamAV"
.\freshclam.exe

# 5. Iniciar daemon
.\clamd.exe

# 6. Testar (em outro PowerShell)
Test-NetConnection -ComputerName localhost -Port 3310
# Deve mostrar: TcpTestSucceeded : True
```

**Configuração PHP**:
```php
// No arquivo includes/AntivirusScanner.php
AntivirusScanner::configureClamAV('localhost', 3310);
```

---

### 🍎 macOS

```bash
# 1. Instalar via Homebrew
brew install clamav

# 2. Copiar configurações
cd /usr/local/etc/clamav/
cp freshclam.conf.sample freshclam.conf
cp clamd.conf.sample clamd.conf

# 3. Editar clamd.conf (remover linha "Example")
sed -i '' '/Example/d' clamd.conf
sed -i '' '/Example/d' freshclam.conf

# 4. Atualizar definições
freshclam

# 5. Iniciar serviço
clamd

# 6. Verificar
echo "PING" | nc localhost 3310
# Deve retornar: PONG
```

---

## 🔧 CONFIGURAÇÃO AVANÇADA

### Usar ClamAV via TCP (mais compatível)

```php
// Em qualquer arquivo PHP antes de usar o scanner
require_once 'includes/AntivirusScanner.php';

// Configurar host e porta customizados
AntivirusScanner::configureClamAV('192.168.1.100', 3310);
```

### Usar ClamAV via Socket Unix (mais rápido)

```php
AntivirusScanner::configureClamAV(
    host: 'localhost',
    port: 3310,
    socket: '/var/run/clamav/clamd.ctl'
);
```

---

## 🧪 TESTAR O SISTEMA

### Teste 1: Verificar qual scanner está ativo

```php
<?php
require_once 'includes/AntivirusScanner.php';

$status = AntivirusScanner::getScannerStatus();

echo "Scanner: " . $status['scanner'] . "\n";
echo "Disponível: " . ($status['available'] ? 'SIM' : 'NÃO') . "\n";
echo "Descrição: " . $status['description'] . "\n";
?>
```

**Resultado esperado**:
```
Scanner: clamav_tcp
Disponível: SIM
Descrição: ClamAV via TCP
```

### Teste 2: Scan de arquivo limpo

```php
<?php
require_once 'includes/AntivirusScanner.php';

// Criar arquivo de teste limpo
file_put_contents('test_clean.txt', 'Este é um arquivo limpo');

$result = AntivirusScanner::scanFile('test_clean.txt');

print_r($result);
unlink('test_clean.txt');
?>
```

**Resultado esperado**:
```
Array
(
    [safe] => 1
    [scanner] => ClamAV TCP
    [scan_time] => 15.23ms
    [file_size] => 25
    [file_name] => test_clean.txt
)
```

### Teste 3: Arquivo de teste EICAR (padrão de antivírus)

```php
<?php
require_once 'includes/AntivirusScanner.php';

// String EICAR (arquivo de teste de antivírus - NÃO É VÍRUS REAL)
$eicar = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

file_put_contents('eicar.txt', $eicar);

$result = AntivirusScanner::scanFile('eicar.txt');

print_r($result);
unlink('eicar.txt');
?>
```

**Resultado esperado**:
```
Array
(
    [safe] => 
    [threat] => Eicar-Signature
    [scanner] => ClamAV TCP
    [scan_time] => 12.45ms
    [file_size] => 68
    [file_name] => eicar.txt
)
```

---

## 📊 LOGS E MONITORAMENTO

### Ver logs de scan

```bash
# Logs de antivírus
tail -f logs/antivirus_2025-11-23.log

# Logs de segurança
tail -f logs/security_2025-11-23.log
```

**Exemplo de log**:
```json
{"timestamp":"2025-11-23 14:30:15","file":"documento.pdf","size":125847,"result":"CLEAN","scanner":"ClamAV TCP","threat":null,"scan_time":"18.32ms","ip":"192.168.1.100"}
{"timestamp":"2025-11-23 14:31:22","file":"malware.exe","size":5420,"result":"THREAT","scanner":"ClamAV TCP","threat":"Win.Trojan.Agent","scan_time":"25.67ms","ip":"192.168.1.101"}
```

---

## ⚡ PERFORMANCE

### Tempos médios de scan

| Scanner | Tempo | Precisão |
|---------|-------|----------|
| ClamAV Socket | 10-20ms | ⭐⭐⭐⭐⭐ |
| ClamAV TCP | 15-30ms | ⭐⭐⭐⭐⭐ |
| Windows Defender | 100-300ms | ⭐⭐⭐⭐ |
| Validação Manual | 5-10ms | ⭐⭐⭐ |

### Otimização para produção

```bash
# Aumentar memória do ClamAV (clamd.conf)
MaxThreads 10
MaxConnectionQueueLength 100

# Atualizar definições diariamente (cron)
0 2 * * * /usr/bin/freshclam --quiet
```

---

## 🔒 SEGURANÇA EM PRODUÇÃO

### Checklist de ativação

- [ ] **ClamAV instalado e rodando**
  ```bash
  sudo systemctl status clamav-daemon
  ```

- [ ] **Definições atualizadas** (menos de 24h)
  ```bash
  sudo freshclam
  ```

- [ ] **Logs configurados**
  ```bash
  ls -lh logs/antivirus_*.log
  ```

- [ ] **Permissões corretas**
  ```bash
  chmod 755 uploads/anexos/
  ```

- [ ] **Testar upload** via interface web

- [ ] **Monitorar logs** por 48h

---

## 🛠️ TROUBLESHOOTING

### Erro: "Socket connection refused"

**Causa**: ClamAV não está rodando

**Solução**:
```bash
sudo systemctl start clamav-daemon
sudo systemctl status clamav-daemon
```

### Erro: "Definições de vírus desatualizadas"

**Solução**:
```bash
sudo freshclam
```

### Windows: Erro "Port 3310 already in use"

**Solução**:
```powershell
# Verificar o que está usando a porta
netstat -ano | findstr :3310

# Matar processo (substituir PID)
taskkill /PID <número> /F
```

### Validação manual muito restritiva

**Ajuste**: Editar `AntivirusScanner.php`:
```php
// Linha 206: Remover verificações específicas
// Exemplo: permitir PDFs pequenos
if ($extension === 'pdf' && $size < 100) { // Era 1024
```

---

## 📞 SUPORTE

### Recursos úteis

- **ClamAV Docs**: https://docs.clamav.net/
- **Testar online**: https://www.virustotal.com/
- **Logs do sistema**: `logs/antivirus_*.log`

### Comandos de diagnóstico

```bash
# Status do ClamAV
clamdscan --version

# Scan manual de arquivo
clamdscan arquivo.pdf

# Ver estatísticas
clamconf -n
```

---

## ✅ RESUMO

1. ✅ **AntivirusScanner.php** criado
2. ✅ **Validação manual ativa** (proteção básica)
3. ⏳ **Instalar ClamAV** (proteção profissional)
4. ⏳ **Testar com EICAR** (validar funcionamento)
5. ⏳ **Monitorar logs** (acompanhar scans)

**Status atual**: 🟡 PROTEÇÃO BÁSICA ATIVA  
**Próximo passo**: Instalar ClamAV para proteção completa

---

**Criado em**: 23/11/2025  
**Versão**: 1.0  
**Sistema**: FinanSmart Pro
