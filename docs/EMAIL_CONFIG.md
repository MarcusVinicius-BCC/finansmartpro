# Configuração de Email (SMTP)

Para que o sistema de recuperação de senha funcione corretamente, você precisa configurar o SMTP em `includes/EmailService.php`.

## Opções de Configuração

### 1. Gmail (Recomendado para Testes)

```php
$this->mailer->Host = 'smtp.gmail.com';
$this->mailer->Username = 'seu-email@gmail.com';
$this->mailer->Password = 'sua-senha-de-app'; // Não use a senha normal!
$this->mailer->Port = 587;
```

**Importante:** Você precisa gerar uma "Senha de App" no Gmail:
1. Acesse https://myaccount.google.com/security
2. Ative a verificação em duas etapas
3. Vá em "Senhas de app" e gere uma senha
4. Use essa senha no código

### 2. Mailtrap (Recomendado para Desenvolvimento)

```php
$this->mailer->Host = 'sandbox.smtp.mailtrap.io';
$this->mailer->Username = 'seu-username-mailtrap';
$this->mailer->Password = 'sua-senha-mailtrap';
$this->mailer->Port = 2525;
```

Crie uma conta gratuita em https://mailtrap.io e pegue as credenciais.

### 3. Usando Variáveis de Ambiente (.env)

Adicione no arquivo `.env`:

```
SMTP_HOST=smtp.gmail.com
SMTP_USERNAME=seu-email@gmail.com
SMTP_PASSWORD=sua-senha-de-app
SMTP_PORT=587
MAIL_FROM_ADDRESS=noreply@finansmart.com
MAIL_FROM_NAME=FinanSmart Pro
```

O EmailService.php já está configurado para usar essas variáveis automaticamente com `getenv()`.

## Testando o Sistema

1. Configure o SMTP em `includes/EmailService.php`
2. Acesse `forgot_password.php`
3. Digite um email cadastrado
4. Verifique se recebeu o email
5. Clique no link de recuperação
6. Defina uma nova senha

## Requisitos de Senha

- Mínimo de 8 caracteres
- Pelo menos uma letra maiúscula
- Pelo menos uma letra minúscula
- Pelo menos um número

## Segurança

- Tokens expiram em 1 hora
- Tokens são únicos (64 caracteres hex)
- Senhas são hash com `password_hash()`
- CSRF protection em todos os formulários
- Logs de segurança registram tentativas

## Troubleshooting

**Email não está sendo enviado:**
- Verifique as credenciais SMTP
- Verifique se a porta está aberta
- Confira os logs de erro do PHP

**Token inválido/expirado:**
- Tokens expiram em 1 hora
- Certifique-se de usar o link mais recente

**Debug do PHPMailer:**
Adicione esta linha em `EmailService.php` para ver erros detalhados:

```php
$this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
```
