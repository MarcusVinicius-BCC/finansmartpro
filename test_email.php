<?php
/**
 * Teste de Envio de Email via Brevo
 * Script para verificar se a configuração SMTP está funcionando
 */

require_once 'includes/env.php';
require_once 'includes/EmailService.php';

// Carregar variáveis de ambiente
Env::load();

// Somente permite acesso em desenvolvimento
if (Env::get('APP_ENV') === 'production') {
    // Permite acesso apenas se for localhost
    $isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
    if (!$isLocalhost) {
        die('Este script só pode ser executado em modo de desenvolvimento ou localhost.');
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Email - FinanSmart Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .config-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.6;
        }
        .config-info strong {
            color: #667eea;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .loading.active {
            display: block;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Teste de Email - Brevo/Sendinblue</h1>
        <p class="subtitle">Verifique se a configuração SMTP está funcionando corretamente</p>

        <div class="config-info">
            <strong>Configuração atual:</strong><br>
            Servidor: <?php echo Env::get('SMTP_HOST', 'não configurado'); ?><br>
            Porta: <?php echo Env::get('SMTP_PORT', 'não configurado'); ?><br>
            Usuário: <?php echo Env::get('SMTP_USERNAME', 'não configurado'); ?><br>
            Remetente: <?php echo Env::get('MAIL_FROM_ADDRESS', 'não configurado'); ?> 
            (<?php echo Env::get('MAIL_FROM_NAME', 'não configurado'); ?>)<br>
            Limite Brevo: 300 emails/dia (grátis para sempre)
        </div>

        <?php
        $message = '';
        $messageType = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
            $emailDestino = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            
            if (filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
                try {
                    $emailService = new EmailService();
                    
                    // Ativar debug para ver o erro exato
                    $emailService->setDebugMode(true);
                    
                    $assunto = '✅ Teste de Email - FinanSmart Pro';
                    $corpo = "
                        <h2 style='color: #667eea;'>Teste de Email Bem-Sucedido!</h2>
                        <p>Parabéns! Seu sistema de email está funcionando perfeitamente.</p>
                        
                        <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <strong>Detalhes da configuração:</strong><br>
                            • Servidor SMTP: Brevo (ex-Sendinblue)<br>
                            • Email enviado em: " . date('d/m/Y H:i:s') . "<br>
                            • Sistema: FinanSmart Pro<br>
                            • Status: ✅ Operacional
                        </div>
                        
                        <p style='color: #666; font-size: 13px;'>
                            Este é um email de teste automático. Você pode começar a usar a funcionalidade 
                            de recuperação de senha no seu sistema!
                        </p>
                        
                        <hr style='margin: 20px 0; border: none; border-top: 1px solid #e0e0e0;'>
                        
                        <p style='color: #999; font-size: 12px;'>
                            FinanSmart Pro - Sistema de Gestão Financeira Pessoal<br>
                            Este email foi enviado via Brevo - 300 emails/dia grátis
                        </p>
                    ";
                    
                    $resultado = $emailService->sendEmail($emailDestino, $assunto, $corpo);
                    
                    if ($resultado) {
                        $message = "
                            <strong>✅ Email enviado com sucesso!</strong><br><br>
                            Um email de teste foi enviado para <strong>$emailDestino</strong><br><br>
                            Verifique sua caixa de entrada (e também o spam, por precaução).<br><br>
                            <strong>Próximos passos:</strong><br>
                            1. Confirme o recebimento do email<br>
                            2. Teste a funcionalidade 'Esqueci minha senha' no login<br>
                            3. Sistema pronto para publicação! 🚀
                        ";
                        $messageType = 'success';
                    } else {
                        $message = "
                            <strong>❌ Erro ao enviar email</strong><br><br>
                            O email não pôde ser enviado. Possíveis causas:<br>
                            • Credenciais SMTP incorretas<br>
                            • Servidor Brevo temporariamente indisponível<br>
                            • Limite diário atingido (300 emails/dia)<br><br>
                            Verifique o arquivo .env e tente novamente.
                        ";
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = "
                        <strong>❌ Erro técnico:</strong><br><br>
                        " . htmlspecialchars($e->getMessage()) . "<br><br>
                        Verifique se todas as credenciais no .env estão corretas.
                    ";
                    $messageType = 'error';
                }
            } else {
                $message = "
                    <strong>⚠️ Email inválido</strong><br>
                    Por favor, insira um endereço de email válido.
                ";
                $messageType = 'error';
            }
        }
        ?>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="testForm">
            <div class="form-group">
                <label for="email">Email de destino para teste:</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="seu-email@exemplo.com"
                    required
                >
            </div>

            <button type="submit" id="btnSubmit">
                📨 Enviar Email de Teste
            </button>
        </form>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Enviando email...</p>
        </div>

        <div class="alert alert-info" style="margin-top: 20px;">
            <strong>💡 Dica:</strong> Após confirmar que o email funciona, você pode:<br>
            • Testar "Esqueci minha senha" no login<br>
            • Executar <code>prepare_production.bat</code> para criar pasta de produção<br>
            • Publicar no InfinityFree (hosting gratuito)<br>
            • Total investido: R$ 0,00 💰
        </div>
    </div>

    <script>
        document.getElementById('testForm').addEventListener('submit', function() {
            document.getElementById('btnSubmit').disabled = true;
            document.getElementById('loading').classList.add('active');
        });
    </script>
</body>
</html>
