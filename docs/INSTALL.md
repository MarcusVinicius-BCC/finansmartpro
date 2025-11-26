# 🚀 Guia de Instalação - FinanSmart Pro

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- Composer (para dependências)

## 🔧 Instalação Passo a Passo

### 1. Clonar o Repositório

```bash
git clone https://github.com/MarcusVinicius-BCC/finansmartpro.git
cd finansmartpro
```

### 2. Configurar Variáveis de Ambiente

```bash
# Copiar arquivo de exemplo
cp .env.example .env

# Editar .env com suas configurações
# IMPORTANTE: Configure a senha do banco de dados!
```

**Exemplo de .env:**
```env
DB_HOST=localhost
DB_NAME=finansmart
DB_USER=root
DB_PASS=sua-senha-aqui
```

### 3. Criar Banco de Dados

```bash
# Acessar MySQL
mysql -u root -p

# Criar banco
CREATE DATABASE finansmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Importar estrutura
mysql -u root -p finansmart < database.sql
```

### 4. Instalar Dependências

```bash
composer install
```

### 5. Configurar Permissões

```bash
# Linux/Mac
chmod -R 755 .
chmod -R 777 uploads/
chmod -R 777 backups/
chmod -R 777 logs/
chmod -R 777 cache/

# Windows (via PowerShell como Admin)
icacls uploads /grant Everyone:F /t
icacls backups /grant Everyone:F /t
icacls logs /grant Everyone:F /t
icacls cache /grant Everyone:F /t
```

### 6. Configurar Servidor Web

**Apache (.htaccess já configurado)**
```apache
# Certifique-se que mod_rewrite está habilitado
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Nginx (adicionar ao config)**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 7. Acessar o Sistema

```
http://localhost/finansmartpro
```

## ⚙️ Configurações Adicionais

### Email (Recuperação de Senha)

Edite `.env`:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=seu-email@gmail.com
SMTP_PASSWORD=sua-senha-de-app
```

**Gmail**: Gere uma "Senha de App" em https://myaccount.google.com/security

### Modo de Produção

```env
APP_ENV=production
APP_DEBUG=false
```

## 🔒 Segurança

✅ **Implementado:**
- CSRF protection em todos os formulários
- Senhas com hash BCRYPT
- Rate limiting
- Validação e sanitização de inputs
- Sessões seguras
- Logs de segurança

⚠️ **Recomendações para Produção:**
- Configure HTTPS (Let's Encrypt)
- Altere senha padrão do banco
- Configure backup automático
- Monitore logs de segurança

## 📊 Performance

✅ **Otimizações implementadas:**
- Cache de dashboard (15min)
- Cache de categorias (30min)
- Paginação em listas grandes
- Queries otimizadas

## 🐛 Troubleshooting

### Erro: "Access denied for user 'root'@'localhost'"
```bash
# Verifique se o .env existe e tem a senha correta
cat .env

# Teste a conexão MySQL
mysql -u root -p
```

### Erro: "Class 'PDO' not found"
```bash
# Habilite a extensão PDO no php.ini
extension=pdo_mysql
```

### Erro: "Permission denied" em uploads/logs/cache
```bash
# Ajuste permissões
chmod -R 777 uploads/ backups/ logs/ cache/
```

### Página em branco ou erro 500
```bash
# Verifique logs do PHP
tail -f /var/log/apache2/error.log

# Ou habilite display_errors temporariamente
ini_set('display_errors', 1);
```

## 📚 Documentação

- [SECURITY.md](SECURITY.md) - Guia de segurança
- [CHANGELOG.md](CHANGELOG.md) - Histórico de mudanças
- [EMAIL_CONFIG.md](EMAIL_CONFIG.md) - Configuração de email

## 🆘 Suporte

- Issues: https://github.com/MarcusVinicius-BCC/finansmartpro/issues
- Email: suporte@finansmart.com

## 📝 Licença

MIT License - veja [LICENSE](LICENSE) para detalhes.
