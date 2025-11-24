# 📋 GUIA RÁPIDO DE COMANDOS
## FinanSmart Pro - Segurança e Manutenção

---

## 🚀 INÍCIO RÁPIDO

### Windows (PowerShell/CMD)

```powershell
# Abrir menu interativo
.\security_tools.bat

# Ou executar comandos diretos:
php test_antivirus.php
php minify_assets.php
```

### Linux/macOS (Terminal)

```bash
# Dar permissão de execução
chmod +x security_tools.sh

# Abrir menu interativo
./security_tools.sh

# Ou executar comandos diretos:
php test_antivirus.php
php minify_assets.php
```

---

## 🧪 TESTES

### Testar Antivírus
```bash
php test_antivirus.php
```

**Resultado esperado**:
```
✅ Scanner ativo: Validação Manual
✅ Arquivo limpo: LIMPO
✅ Script malicioso: BLOQUEADO
```

### Testar Conexão com Banco
```bash
php test_connection.php
```

---

## 📊 LOGS

### Ver logs do dia (Linux/macOS)
```bash
# Logs de segurança
tail -f logs/security_$(date +%Y-%m-%d).log

# Logs de antivírus
tail -f logs/antivirus_$(date +%Y-%m-%d).log

# Formatar JSON bonito
cat logs/antivirus_$(date +%Y-%m-%d).log | jq .
```

### Ver logs do dia (Windows)
```powershell
# Logs de segurança
Get-Content "logs\security_$(Get-Date -Format 'yyyy-MM-dd').log" -Tail 20

# Logs de antivírus
Get-Content "logs\antivirus_$(Get-Date -Format 'yyyy-MM-dd').log" -Tail 20
```

### Estatísticas de logs
```bash
# Contar scans de hoje (Linux)
wc -l logs/antivirus_$(date +%Y-%m-%d).log

# Contar ameaças detectadas
grep '"result":"THREAT"' logs/antivirus_*.log | wc -l
```

---

## 🗜️ MINIFICAÇÃO

### Minificar CSS e JS
```bash
php minify_assets.php
```

**O que faz**:
- Processa todos os `.css` → `.min.css`
- Processa todos os `.js` → `.min.js`
- Remove comentários e espaços
- Mostra economia de bytes

**Depois de executar**:
1. Atualizar `includes/header.php`
2. Trocar `style.css` por `style.min.css`
3. Trocar `main.js` por `main.min.js`
4. Testar todas as páginas

---

## 🗄️ BANCO DE DADOS

### Otimizar com Índices
```bash
# Linux/macOS
mysql -u root -p finansmart < database_indexes.sql

# Windows
mysql -u root -p finansmart < database_indexes.sql
```

**O que faz**:
- Cria 50+ índices
- ANALYZE TABLE (atualiza estatísticas)
- OPTIMIZE TABLE (desfragmenta)
- Melhora performance em 10-100x

### Backup Manual
```bash
# Linux/macOS
mysqldump -u root -p finansmart > backups/backup_$(date +%Y-%m-%d_%H-%M-%S).sql

# Windows (PowerShell)
mysqldump -u root -p finansmart > "backups\backup_$(Get-Date -Format 'yyyy-MM-dd_HH-mm-ss').sql"
```

### Restaurar Backup
```bash
mysql -u root -p finansmart < backups/backup_2025-11-23_14-30-00.sql
```

---

## 🔒 PERMISSÕES (Linux/macOS)

### Configurar Permissões Corretas
```bash
# Pastas: 755 (rwxr-xr-x)
find . -type d -exec chmod 755 {} \;

# Arquivos PHP: 644 (rw-r--r--)
find . -type f -name "*.php" -exec chmod 644 {} \;

# .env: 600 (rw-------)
chmod 600 .env

# Pastas de upload/cache: 775
chmod 775 uploads/ cache/ logs/ backups/
```

### Verificar Permissões
```bash
ls -la .env
# Deve mostrar: -rw------- (600)

ls -ld uploads/
# Deve mostrar: drwxrwxr-x (775)
```

---

## 🦠 ANTIVÍRUS

### Status do Scanner
```bash
php -r "
require_once 'includes/AntivirusScanner.php';
\$status = AntivirusScanner::getScannerStatus();
print_r(\$status);
"
```

### Instalar ClamAV (Linux)
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install clamav clamav-daemon -y

# Atualizar definições
sudo freshclam

# Iniciar serviço
sudo systemctl start clamav-daemon
sudo systemctl enable clamav-daemon

# Verificar status
sudo systemctl status clamav-daemon
```

### Instalar ClamAV (Windows)
1. Download: https://www.clamav.net/downloads
2. Instalar em `C:\Program Files\ClamAV`
3. Criar `clamd.conf`:
   ```
   TCPSocket 3310
   TCPAddr 127.0.0.1
   ```
4. Atualizar: `freshclam.exe`
5. Iniciar: `clamd.exe`

---

## 🧹 MANUTENÇÃO

### Limpar Logs Antigos
```bash
# Deletar logs com mais de 30 dias (Linux)
find logs/ -name "*.log" -type f -mtime +30 -delete

# Deletar logs com mais de 30 dias (Windows)
forfiles /P logs /S /M *.log /D -30 /C "cmd /c del @path"
```

### Limpar Cache
```bash
# Deletar cache de moedas antigo
rm cache/rates.json

# Sistema regerará automaticamente
```

### Limpar Sessões Antigas (se usar file-based)
```bash
# Linux
find /var/lib/php/sessions -type f -mtime +7 -delete

# Ou configurar no php.ini:
# session.gc_maxlifetime = 1440 (24 minutos)
```

---

## 🔐 HTTPS

### Obter Certificado SSL (Let's Encrypt)

```bash
# Linux - Certbot
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d seudominio.com

# Renovação automática
sudo certbot renew --dry-run
```

### Testar HTTPS
```bash
# Verificar redirecionamento
curl -I http://seudominio.com
# Deve retornar: Location: https://seudominio.com

# Verificar headers de segurança
curl -I https://seudominio.com | grep -E "Strict-Transport|X-Frame|Content-Security"
```

### Verificar Qualidade SSL
- SSL Labs: https://www.ssllabs.com/ssltest/
- Meta: Nota A+

---

## 📈 MONITORAMENTO

### Monitorar Ameaças em Tempo Real
```bash
# Linux
tail -f logs/antivirus_$(date +%Y-%m-%d).log | grep THREAT

# Alerta por email quando detectar ameaça
tail -f logs/antivirus_$(date +%Y-%m-%d).log | grep THREAT | while read line; do
    echo "$line" | mail -s "AMEAÇA DETECTADA!" admin@seudominio.com
done
```

### Dashboard de Logs
```bash
# Instalar multitail (Linux)
sudo apt install multitail

# Ver múltiplos logs
multitail logs/security_*.log logs/antivirus_*.log
```

---

## 🚨 TROUBLESHOOTING

### Erro: "Permission denied"
```bash
# Verificar owner
ls -la .env

# Corrigir owner
sudo chown www-data:www-data .env

# Ou para usuário atual
sudo chown $USER:$USER .env
```

### Erro: "ClamAV not found"
- Sistema usa validação manual automaticamente
- Instale ClamAV para proteção avançada
- Veja: `ANTIVIRUS_SETUP.md`

### Erro: "Cannot connect to database"
```bash
# Verificar MySQL rodando
sudo systemctl status mysql

# Testar conexão
php test_connection.php

# Ver .env
cat .env
```

### Upload não funciona
```bash
# Verificar permissões
ls -ld uploads/anexos/

# Criar se não existir
mkdir -p uploads/anexos
chmod 775 uploads/anexos
```

---

## 📚 DOCUMENTAÇÃO COMPLETA

### Arquivos de Referência
- `ANTIVIRUS_SETUP.md` - Instalação ClamAV
- `2FA_IMPLEMENTATION.md` - 2FA pós-lançamento
- `SECURITY_SUMMARY.md` - Visão geral de segurança
- `PRE_LAUNCH_CHECKLIST.md` - Checklist completo
- `IMPLEMENTATION_SUMMARY.md` - Resumo da implementação

### Scripts Úteis
- `security_tools.bat` - Menu Windows
- `security_tools.sh` - Menu Linux/macOS
- `test_antivirus.php` - Teste antivírus
- `test_connection.php` - Teste banco
- `minify_assets.php` - Minificação

---

## ⚡ COMANDOS MAIS USADOS

```bash
# 1. Testar sistema
php test_antivirus.php

# 2. Ver logs
tail -f logs/antivirus_$(date +%Y-%m-%d).log

# 3. Minificar
php minify_assets.php

# 4. Otimizar BD
mysql -u root -p finansmart < database_indexes.sql

# 5. Backup
mysqldump -u root -p finansmart > backup.sql

# 6. Permissões
chmod 600 .env
chmod 775 uploads/

# 7. Status scanner
php -r "require 'includes/AntivirusScanner.php'; print_r(AntivirusScanner::getScannerStatus());"
```

---

## 🎯 CHECKLIST PRÉ-PRODUÇÃO

```bash
# 1. Minificar
[ ] php minify_assets.php

# 2. Otimizar BD
[ ] mysql -u root -p finansmart < database_indexes.sql

# 3. HTTPS
[ ] Certificado SSL instalado
[ ] Redirecionamento funcionando

# 4. Testes
[ ] php test_antivirus.php ✅
[ ] php test_connection.php ✅
[ ] Upload de arquivo ✅

# 5. Segurança
[ ] .env com senha forte
[ ] Permissões corretas (600 para .env)
[ ] Logs funcionando

# 6. Backup
[ ] Backup inicial do banco
[ ] Backup de arquivos

# 7. Monitoramento
[ ] Verificar logs diariamente
[ ] Alertas configurados
```

---

**Última atualização**: 23/11/2025  
**Versão**: 1.0  
**Sistema**: FinanSmart Pro
