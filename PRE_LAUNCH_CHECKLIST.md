# ✅ CHECKLIST DE PRÉ-PUBLICAÇÃO
## FinanSmart Pro - Sistema Financeiro

---

## 🚨 CRÍTICO (FAZER ANTES DE PUBLICAR)

### 1. ✅ Segurança HTTPS
- [ ] **Obter certificado SSL** (use Let's Encrypt - GRÁTIS)
  - Acesse: https://certbot.eff.org/
  - Ou use o painel do seu hosting (cPanel, Plesk, etc)
- [ ] **Ativar .htaccess** (já criado no projeto)
- [ ] **Testar redirecionamento HTTP → HTTPS**
- [ ] **Verificar headers de segurança** em https://securityheaders.com/

### 2. ✅ Otimização de Banco de Dados
- [ ] **Executar database_indexes.sql**
  ```bash
  # No phpMyAdmin ou terminal MySQL:
  mysql -u root -p finansmart < database_indexes.sql
  ```
- [ ] **Verificar índices criados**
  ```sql
  SHOW INDEX FROM lancamentos;
  SHOW INDEX FROM usuarios;
  ```
- [ ] **Fazer backup do banco** antes e depois

### 3. ✅ Minificação de Assets
- [ ] **Executar minify_assets.php**
  ```bash
  php minify_assets.php
  ```
- [ ] **Atualizar includes/header.php** para usar .min.css e .min.js
- [ ] **Testar todas as páginas** após minificação
- [ ] **Verificar console do navegador** (F12) por erros

---

## ⚠️ IMPORTANTE (SEGURANÇA ADICIONAL)

### 4. ✅ Antivírus Scan (IMPLEMENTADO)
- [x] **AntivirusScanner.php** criado
- [x] **Validação manual ativa** (proteção básica contra scripts maliciosos)
- [x] **Integrado em anexos.php** e importar.php
- [ ] **Instalar ClamAV** (proteção avançada - opcional)
  - Linux: `sudo apt install clamav clamav-daemon`
  - Windows: Download em https://www.clamav.net/
  - Veja: `ANTIVIRUS_SETUP.md`
- [x] **Logs de scan** em `logs/antivirus_*.log`
- [x] **Testar**: `php test_antivirus.php`

### 5. Arquivo .env
- [ ] **Verificar que .env está bloqueado** no .htaccess
- [ ] **Mudar senha do banco** para senha forte em produção
- [ ] **Nunca commitar .env** no Git (já está no .gitignore)

### 5. Permissões de Arquivos
- [ ] **Pastas com permissão 755**
  ```bash
  find . -type d -exec chmod 755 {} \;
  ```
- [ ] **Arquivos com permissão 644**
  ```bash
  find . -type f -exec chmod 644 {} \;
  ```
- [ ] **Pastas de upload com 775**
  ```bash
  chmod 775 uploads/ cache/ backups/ logs/
  ```

### 6. Configurações PHP
- [ ] **Desabilitar display_errors** em produção
  ```ini
  display_errors = Off
  error_reporting = E_ALL
  log_errors = On
  error_log = /path/to/logs/php_errors.log
  ```
- [ ] **Limitar upload_max_filesize** (já configurado: 10MB)
- [ ] **Configurar session.cookie_secure = On** (HTTPS)

### 7. Backup Automático
- [ ] **Configurar backup diário** do banco de dados
- [ ] **Configurar backup semanal** dos arquivos
- [ ] **Testar restauração** de backup

---

## 📊 PERFORMANCE (RECOMENDADO)

### 8. Cache
- [ ] **Ativar OPcache** no PHP
  ```ini
  opcache.enable=1
  opcache.memory_consumption=128
  opcache.max_accelerated_files=10000
  ```
- [ ] **Configurar cache de navegador** (já no .htaccess)

### 9. Monitoramento
- [ ] **Configurar logs de erro**
  - `logs/php_errors.log`
  - `logs/security_events.log`
- [ ] **Instalar Google Analytics** (opcional)
- [ ] **Configurar alertas** de erro (email, Slack, etc)

---

## 🔒 SEGURANÇA AVANÇADA (OPCIONAL - PÓS-LANÇAMENTO)

### 10. 2FA (Two-Factor Authentication)
- [ ] Implementar Google Authenticator
- [ ] Adicionar backup codes
- [ ] Testar login com 2FA

### 11. Rate Limiting
- [ ] Limitar tentativas de login (5 por minuto)
- [ ] Bloquear IPs suspeitos
- [ ] Implementar CAPTCHA em formulários

---

## 🧪 TESTES FINAIS

### 12. Testes de Funcionalidade
- [ ] **Cadastro de novo usuário**
- [ ] **Login e logout**
- [ ] **Todas as funcionalidades principais**:
  - [ ] Lançamentos (criar, editar, excluir)
  - [ ] Categorias
  - [ ] Metas
  - [ ] Investimentos
  - [ ] Orçamentos
  - [ ] Relatórios PDF/Excel
  - [ ] Dashboard
  - [ ] Gráficos
  - [ ] Calendário
  - [ ] Anexos
  - [ ] Família
  - [ ] Notificações

### 13. Testes de Responsividade
- [ ] **Desktop** (Chrome, Firefox, Safari)
- [ ] **Tablet** (iPad, Android)
- [ ] **Mobile** (iPhone, Android)

### 14. Testes de Segurança
- [ ] **SQL Injection** - tentar em formulários
- [ ] **XSS** - tentar inserir scripts
- [ ] **CSRF** - verificar tokens em ações
- [ ] **Uploads maliciosos** - tentar upload de .php

---

## 🚀 DEPLOY

### 15. Configuração do Servidor
- [ ] **PHP 8.0+** instalado
- [ ] **MySQL 8.0+** ou MariaDB 10.5+
- [ ] **mod_rewrite** habilitado (Apache)
- [ ] **Composer** instalado (para vendor/)
- [ ] **HTTPS/SSL** configurado

### 16. Transferência de Arquivos
- [ ] **Upload via FTP/SFTP** ou Git
- [ ] **Não enviar**: .git/, .env (criar novo no servidor)
- [ ] **Criar .env** no servidor com dados de produção
- [ ] **Importar database.sql**
- [ ] **Executar database_indexes.sql**

### 17. Configuração Final
- [ ] **Testar conexão com banco**
- [ ] **Verificar permissões de pastas**
- [ ] **Testar envio de email** (recuperação de senha)
- [ ] **Verificar cache de taxas** de moeda

---

## 📝 DOCUMENTAÇÃO

### 18. Documentação do Sistema
- [ ] **README.md** atualizado
- [ ] **Instruções de instalação**
- [ ] **Changelog** atualizado
- [ ] **Licença** definida

---

## ✅ COMANDOS RÁPIDOS

```bash
# 1. Minificar assets
php minify_assets.php

# 2. Otimizar banco de dados
mysql -u root -p finansmart < database_indexes.sql

# 3. Verificar permissões
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 775 uploads/ cache/ backups/ logs/

# 4. Backup do banco
mysqldump -u root -p finansmart > backup_$(date +%Y%m%d).sql

# 5. Testar HTTPS
curl -I https://seudominio.com
```

---

## 🎯 ORDEM DE EXECUÇÃO

1. ✅ Executar `minify_assets.php`
2. ✅ Executar `database_indexes.sql`
3. ✅ Configurar SSL/HTTPS no servidor
4. ✅ Ativar `.htaccess`
5. ✅ Testar todas as funcionalidades
6. ✅ Fazer backup completo
7. 🚀 PUBLICAR!

---

## 📞 SUPORTE PÓS-PUBLICAÇÃO

- Monitorar logs de erro diariamente
  - `logs/security_*.log`
  - `logs/antivirus_*.log`
  - `logs/php_errors.log`
- Fazer backup semanal
- Atualizar dependências mensalmente
- **Implementar 2FA em 30 dias** (ver `2FA_IMPLEMENTATION.md`)
- Avaliar CDN após 1000 usuários

---

## 📚 DOCUMENTAÇÃO ADICIONAL

- `ANTIVIRUS_SETUP.md` - Guia de instalação do ClamAV
- `2FA_IMPLEMENTATION.md` - Plano de implementação de 2FA
- `SECURITY.md` - Política de segurança
- `README.md` - Documentação geral

---

**Status**: [ ] Pronto para Produção
**Data**: ___/___/______
**Responsável**: _________________
