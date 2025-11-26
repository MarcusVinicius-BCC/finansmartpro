# 🔐 2FA - AUTENTICAÇÃO DE DOIS FATORES
## FinanSmart Pro - Implementação Pós-Lançamento

---

## 📋 VISÃO GERAL

A autenticação de dois fatores (2FA) adiciona uma camada extra de segurança, exigindo:
1. **Senha** (algo que você sabe)
2. **Código TOTP** (algo que você possui - celular)

**Status**: ⏳ Planejado para implementação pós-lançamento  
**Prioridade**: Alta (implementar em 30 dias após lançamento)

---

## 🎯 FUNCIONALIDADES

### 1. Google Authenticator / Authy
- ✅ Código de 6 dígitos renovado a cada 30 segundos
- ✅ Funciona offline
- ✅ Padrão TOTP (RFC 6238)
- ✅ QR Code para configuração fácil

### 2. Backup Codes
- 10 códigos de backup únicos
- Usar quando perder acesso ao celular
- Regenerar após uso

### 3. Ativação Opcional
- Usuário escolhe ativar ou não
- Email de notificação ao ativar
- Processo de desativação seguro

---

## 📦 DEPENDÊNCIAS

### Composer (PHP)

```bash
# Instalar biblioteca TOTP
composer require spomky-labs/otphp
composer require endroid/qr-code
```

### Alternativa (manual):

```bash
# Biblioteca minimal (sem composer)
# Download: https://github.com/RobThree/TwoFactorAuth
# Copiar para: includes/TwoFactorAuth/
```

---

## 🗄️ ALTERAÇÕES NO BANCO DE DADOS

```sql
-- Adicionar campos na tabela usuarios
ALTER TABLE usuarios ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE usuarios ADD COLUMN two_factor_secret VARCHAR(32) NULL;
ALTER TABLE usuarios ADD COLUMN two_factor_recovery_codes TEXT NULL;
ALTER TABLE usuarios ADD COLUMN two_factor_activated_at DATETIME NULL;

-- Criar índice
CREATE INDEX idx_two_factor ON usuarios(two_factor_enabled);

-- Tabela de logs de 2FA
CREATE TABLE two_factor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    action VARCHAR(50) NOT NULL, -- 'enabled', 'disabled', 'verified', 'failed', 'recovery_used'
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE INDEX idx_2fa_logs_user ON two_factor_logs(id_usuario, created_at);
```

---

## 💻 IMPLEMENTAÇÃO

### Arquivo: includes/TwoFactorAuth.php

```php
<?php
/**
 * Sistema 2FA - FinanSmart Pro
 * Google Authenticator / Authy
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OTPHP\TOTP;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class TwoFactorAuth {
    
    /**
     * Gerar secret para novo usuário
     */
    public static function generateSecret() {
        $totp = TOTP::create();
        return $totp->getSecret();
    }
    
    /**
     * Gerar QR Code
     */
    public static function generateQRCode($email, $secret) {
        $totp = TOTP::create($secret);
        $totp->setLabel($email);
        $totp->setIssuer('FinanSmart Pro');
        
        $uri = $totp->getProvisioningUri();
        
        $qrCode = QrCode::create($uri)
            ->setSize(300)
            ->setMargin(10);
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        return $result->getDataUri();
    }
    
    /**
     * Verificar código TOTP
     */
    public static function verifyCode($secret, $code) {
        $totp = TOTP::create($secret);
        
        // Verificar com janela de ±1 período (30s)
        return $totp->verify($code, null, 1);
    }
    
    /**
     * Gerar códigos de recuperação
     */
    public static function generateRecoveryCodes($count = 10) {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(bin2hex(random_bytes(4))); // 8 caracteres
            $codes[] = substr($code, 0, 4) . '-' . substr($code, 4, 4);
        }
        
        return $codes;
    }
    
    /**
     * Ativar 2FA para usuário
     */
    public static function enable($userId, $pdo) {
        // Gerar secret
        $secret = self::generateSecret();
        
        // Gerar recovery codes
        $recoveryCodes = self::generateRecoveryCodes();
        $encodedCodes = json_encode($recoveryCodes);
        
        // Salvar no banco
        $sql = "UPDATE usuarios 
                SET two_factor_secret = ?, 
                    two_factor_recovery_codes = ?,
                    two_factor_enabled = FALSE
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$secret, $encodedCodes, $userId]);
        
        // Log
        self::logAction($userId, 'setup_initiated', $pdo);
        
        return [
            'secret' => $secret,
            'recovery_codes' => $recoveryCodes
        ];
    }
    
    /**
     * Confirmar ativação após verificar código
     */
    public static function confirmActivation($userId, $code, $pdo) {
        // Buscar secret
        $sql = "SELECT two_factor_secret FROM usuarios WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $secret = $stmt->fetchColumn();
        
        if (!$secret) {
            return ['success' => false, 'error' => 'Secret não encontrado'];
        }
        
        // Verificar código
        if (self::verifyCode($secret, $code)) {
            // Ativar 2FA
            $sql = "UPDATE usuarios 
                    SET two_factor_enabled = TRUE,
                        two_factor_activated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            
            // Log
            self::logAction($userId, 'enabled', $pdo);
            
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Código inválido'];
    }
    
    /**
     * Desativar 2FA
     */
    public static function disable($userId, $password, $pdo) {
        // Verificar senha
        $sql = "SELECT senha FROM usuarios WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $hashedPassword = $stmt->fetchColumn();
        
        if (!password_verify($password, $hashedPassword)) {
            return ['success' => false, 'error' => 'Senha incorreta'];
        }
        
        // Desativar
        $sql = "UPDATE usuarios 
                SET two_factor_enabled = FALSE,
                    two_factor_secret = NULL,
                    two_factor_recovery_codes = NULL,
                    two_factor_activated_at = NULL
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        
        // Log
        self::logAction($userId, 'disabled', $pdo);
        
        return ['success' => true];
    }
    
    /**
     * Usar recovery code
     */
    public static function useRecoveryCode($userId, $code, $pdo) {
        $sql = "SELECT two_factor_recovery_codes FROM usuarios WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $encodedCodes = $stmt->fetchColumn();
        
        if (!$encodedCodes) {
            return false;
        }
        
        $codes = json_decode($encodedCodes, true);
        
        // Buscar código
        $key = array_search($code, $codes);
        
        if ($key !== false) {
            // Remover código usado
            unset($codes[$key]);
            $codes = array_values($codes);
            
            // Atualizar banco
            $sql = "UPDATE usuarios SET two_factor_recovery_codes = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([json_encode($codes), $userId]);
            
            // Log
            self::logAction($userId, 'recovery_used', $pdo);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Regenerar recovery codes
     */
    public static function regenerateRecoveryCodes($userId, $pdo) {
        $newCodes = self::generateRecoveryCodes();
        
        $sql = "UPDATE usuarios SET two_factor_recovery_codes = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([json_encode($newCodes), $userId]);
        
        // Log
        self::logAction($userId, 'recovery_regenerated', $pdo);
        
        return $newCodes;
    }
    
    /**
     * Log de ações
     */
    private static function logAction($userId, $action, $pdo) {
        $sql = "INSERT INTO two_factor_logs (id_usuario, action, ip_address, user_agent) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $action,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}
```

---

## 🎨 INTERFACE DO USUÁRIO

### Página: configuracoes_2fa.php

```php
<?php
require_once 'includes/db.php';
require_once 'includes/TwoFactorAuth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Buscar status 2FA
$sql = "SELECT two_factor_enabled FROM usuarios WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$twoFactorEnabled = $stmt->fetchColumn();

// Ativar 2FA
if (isset($_POST['enable_2fa'])) {
    $result = TwoFactorAuth::enable($user_id, $pdo);
    $_SESSION['2fa_setup'] = $result;
    header('Location: configuracoes_2fa.php?step=verify');
    exit;
}

// Verificar código
if (isset($_POST['verify_code'])) {
    $code = $_POST['code'];
    $result = TwoFactorAuth::confirmActivation($user_id, $code, $pdo);
    
    if ($result['success']) {
        unset($_SESSION['2fa_setup']);
        header('Location: configuracoes_2fa.php?success=ativado');
    } else {
        header('Location: configuracoes_2fa.php?error=codigo_invalido');
    }
    exit;
}

// Desativar 2FA
if (isset($_POST['disable_2fa'])) {
    $password = $_POST['password'];
    $result = TwoFactorAuth::disable($user_id, $password, $pdo);
    
    if ($result['success']) {
        header('Location: configuracoes_2fa.php?success=desativado');
    } else {
        header('Location: configuracoes_2fa.php?error=senha_incorreta');
    }
    exit;
}

require_once 'includes/header.php';
?>

<!-- Interface aqui -->

<?php require_once 'includes/footer.php'; ?>
```

---

## 🔄 MODIFICAR LOGIN

### Arquivo: login.php

```php
// Após validar senha
if ($twoFactorEnabled) {
    // Redirecionar para tela de 2FA
    $_SESSION['2fa_user_id'] = $user_id;
    header('Location: verify_2fa.php');
    exit;
} else {
    // Login normal
    $_SESSION['user_id'] = $user_id;
    header('Location: dashboard.php');
    exit;
}
```

### Arquivo: verify_2fa.php

```php
<?php
require_once 'includes/db.php';
require_once 'includes/TwoFactorAuth.php';

if (!isset($_SESSION['2fa_user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['2fa_user_id'];

// Verificar código
if (isset($_POST['code'])) {
    $code = $_POST['code'];
    
    // Buscar secret
    $sql = "SELECT two_factor_secret FROM usuarios WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $secret = $stmt->fetchColumn();
    
    if (TwoFactorAuth::verifyCode($secret, $code)) {
        // Login bem-sucedido
        unset($_SESSION['2fa_user_id']);
        $_SESSION['user_id'] = $userId;
        
        TwoFactorAuth::logAction($userId, 'verified', $pdo);
        
        header('Location: dashboard.php');
        exit;
    } else {
        // Tentar recovery code
        if (TwoFactorAuth::useRecoveryCode($userId, $code, $pdo)) {
            unset($_SESSION['2fa_user_id']);
            $_SESSION['user_id'] = $userId;
            header('Location: dashboard.php?warning=recovery_usado');
            exit;
        }
        
        TwoFactorAuth::logAction($userId, 'failed', $pdo);
        $error = 'Código inválido';
    }
}
?>

<!-- Interface de verificação -->
```

---

## 📧 NOTIFICAÇÕES

### Email ao ativar 2FA

```php
// Enviar email
$emailService = new EmailService();
$emailService->sendEmail(
    $userEmail,
    'Autenticação de Dois Fatores Ativada',
    "
    <h2>2FA Ativado com Sucesso!</h2>
    <p>A autenticação de dois fatores foi ativada na sua conta.</p>
    <p><strong>Códigos de recuperação:</strong></p>
    <ul>
        " . implode('', array_map(fn($c) => "<li>$c</li>", $recoveryCodes)) . "
    </ul>
    <p><strong>IMPORTANTE:</strong> Guarde estes códigos em local seguro!</p>
    <p>Se você não fez esta alteração, entre em contato imediatamente.</p>
    "
);
```

---

## ✅ CHECKLIST DE IMPLEMENTAÇÃO

### Fase 1: Preparação
- [ ] Instalar dependências (Composer)
- [ ] Executar SQL de alteração do banco
- [ ] Criar `includes/TwoFactorAuth.php`
- [ ] Testar geração de QR Code

### Fase 2: Interface
- [ ] Criar `configuracoes_2fa.php`
- [ ] Criar `verify_2fa.php`
- [ ] Adicionar link no menu de configurações
- [ ] Design responsivo

### Fase 3: Integração
- [ ] Modificar `login.php`
- [ ] Adicionar verificação em rotas protegidas
- [ ] Implementar recovery codes
- [ ] Email de notificação

### Fase 4: Testes
- [ ] Testar ativação completa
- [ ] Testar login com 2FA
- [ ] Testar recovery codes
- [ ] Testar desativação
- [ ] Testar em mobile

### Fase 5: Documentação
- [ ] Manual do usuário
- [ ] FAQ sobre 2FA
- [ ] Vídeo tutorial

---

## 🧪 TESTES

### Teste 1: Ativação

1. Login normal
2. Ir em Configurações → 2FA
3. Clicar "Ativar 2FA"
4. Escanear QR Code no Google Authenticator
5. Inserir código de 6 dígitos
6. Verificar ativação

### Teste 2: Login com 2FA

1. Logout
2. Login com email/senha
3. Inserir código do app
4. Verificar acesso

### Teste 3: Recovery Code

1. Login com email/senha
2. Usar recovery code ao invés do app
3. Verificar que código foi invalidado

---

## 📊 TIMELINE

- **Semana 1**: Implementação backend (TwoFactorAuth.php)
- **Semana 2**: Interface (configuracoes_2fa.php, verify_2fa.php)
- **Semana 3**: Integração e testes
- **Semana 4**: Lançamento gradual (beta para 10% dos usuários)

---

## 🎯 MÉTRICAS DE SUCESSO

- **Meta**: 30% dos usuários ativos com 2FA em 90 dias
- **Taxa de ativação**: Monitorar semanalmente
- **Suporte**: Menos de 5% de chamados relacionados a 2FA
- **Segurança**: 0 contas comprometidas com 2FA ativo

---

**Status**: 📝 Documentado e pronto para implementação  
**Dependência**: Lançamento estável do sistema  
**Prioridade**: Alta (30 dias pós-lançamento)
