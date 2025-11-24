<?php
/**
 * Serviço de Email usando PHPMailer
 * Envia emails transacionais (recuperação de senha, notificações)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/env.php';

// Carregar variáveis de ambiente
Env::load();

class EmailService {
    private $mailer;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // Configuração SMTP - usar classe Env
        $this->mailer->isSMTP();
        $this->mailer->Host = Env::get('SMTP_HOST', 'smtp.gmail.com');
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = Env::get('SMTP_USERNAME', 'seu-email@gmail.com');
        $this->mailer->Password = Env::get('SMTP_PASSWORD', 'sua-senha-app');
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = Env::get('SMTP_PORT', 587);
        $this->mailer->CharSet = 'UTF-8';
        
        $this->fromEmail = Env::get('MAIL_FROM_ADDRESS', 'noreply@finansmart.com');
        $this->fromName = Env::get('MAIL_FROM_NAME', 'FinanSmart Pro');
        
        // Configurações opcionais
        // $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER; // Para debug
    }
    
    /**
     * Ativa modo debug para diagnóstico
     */
    public function setDebugMode($enabled = true) {
        if ($enabled) {
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            $this->mailer->Debugoutput = function($str, $level) {
                echo "<pre style='background:#f8f9fa;padding:10px;border-left:3px solid #dc3545;margin:10px 0;font-size:12px;'>$str</pre>";
            };
        }
    }
    
    /**
     * Envia email de recuperação de senha
     * 
     * @param string $email Email do destinatário
     * @param string $token Token de recuperação
     * @param string $userName Nome do usuário
     * @return bool
     */
    public function enviarRecuperacaoSenha($email, $token, $userName = '') {
        try {
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            $this->mailer->addAddress($email);
            $this->mailer->isHTML(true);
            
            $this->mailer->Subject = 'Recuperação de Senha - FinanSmart Pro';
            
            // Link de recuperação
            $resetLink = $this->getBaseUrl() . "/reset_password.php?token=" . urlencode($token);
            
            // Template HTML
            $body = $this->getTemplateRecuperacao($userName, $resetLink);
            
            $this->mailer->Body = $body;
            $this->mailer->AltBody = "Olá" . ($userName ? " $userName" : "") . ",\n\n" .
                "Recebemos uma solicitação para redefinir sua senha.\n\n" .
                "Acesse o link abaixo para criar uma nova senha:\n" .
                "$resetLink\n\n" .
                "Este link expira em 1 hora.\n\n" .
                "Se você não solicitou esta alteração, ignore este email.\n\n" .
                "Atenciosamente,\nEquipe FinanSmart Pro";
            
            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Template HTML profissional para recuperação de senha
     */
    private function getTemplateRecuperacao($userName, $resetLink) {
        $greeting = $userName ? "Olá <strong>$userName</strong>" : "Olá";
        
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" style="width: 600px; border-collapse: collapse; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center; background: linear-gradient(135deg, #660dad 0%, #8e24c7 100%); border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;">
                                🔐 FinanSmart Pro
                            </h1>
                            <p style="margin: 10px 0 0; color: #f0e6ff; font-size: 14px;">
                                Sua Plataforma de Gestão Financeira
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; color: #333333; font-size: 16px; line-height: 1.6;">
                                $greeting,
                            </p>
                            
                            <p style="margin: 0 0 20px; color: #555555; font-size: 14px; line-height: 1.6;">
                                Recebemos uma solicitação para <strong>redefinir sua senha</strong> no FinanSmart Pro.
                            </p>
                            
                            <p style="margin: 0 0 30px; color: #555555; font-size: 14px; line-height: 1.6;">
                                Clique no botão abaixo para criar uma nova senha:
                            </p>
                            
                            <!-- Button -->
                            <table role="presentation" style="margin: 0 auto;">
                                <tr>
                                    <td style="border-radius: 4px; background: linear-gradient(135deg, #660dad 0%, #8e24c7 100%);">
                                        <a href="$resetLink" target="_blank" style="display: inline-block; padding: 16px 40px; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: bold;">
                                            Redefinir Senha
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 30px 0 20px; color: #777777; font-size: 13px; line-height: 1.6;">
                                Ou copie e cole o link abaixo no seu navegador:
                            </p>
                            
                            <p style="margin: 0 0 30px; padding: 15px; background-color: #f8f8f8; border-left: 4px solid #660dad; border-radius: 4px; font-size: 12px; color: #555555; word-break: break-all;">
                                $resetLink
                            </p>
                            
                            <!-- Warning -->
                            <div style="margin: 30px 0; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                                <p style="margin: 0; color: #856404; font-size: 13px; line-height: 1.6;">
                                    ⚠️ <strong>Importante:</strong> Este link expira em <strong>1 hora</strong> por questões de segurança.
                                </p>
                            </div>
                            
                            <p style="margin: 30px 0 0; color: #777777; font-size: 13px; line-height: 1.6;">
                                Se você <strong>não solicitou</strong> esta redefinição, por favor ignore este email. Sua senha permanecerá inalterada.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f8f9fa; border-radius: 0 0 8px 8px; text-align: center;">
                            <p style="margin: 0 0 10px; color: #666666; font-size: 12px;">
                                Atenciosamente,<br>
                                <strong>Equipe FinanSmart Pro</strong>
                            </p>
                            
                            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
                            
                            <p style="margin: 0; color: #999999; font-size: 11px; line-height: 1.5;">
                                © 2025 FinanSmart Pro. Todos os direitos reservados.<br>
                                Este é um email automático, por favor não responda.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
    
    /**
     * Obtem URL base do sistema
     */
    private function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        
        // Remover /includes se estiver no caminho
        $scriptPath = str_replace('/includes', '', $scriptPath);
        
        return $protocol . "://" . $host . $scriptPath;
    }
    
    /**
     * Envia email de confirmação de alteração de senha
     */
    public function enviarConfirmacaoAlteracao($email, $userName = '') {
        try {
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            $this->mailer->addAddress($email);
            $this->mailer->isHTML(true);
            
            $this->mailer->Subject = 'Senha Alterada com Sucesso - FinanSmart Pro';
            
            $greeting = $userName ? "Olá <strong>$userName</strong>" : "Olá";
            
            $body = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" style="width: 600px; border-collapse: collapse; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 40px; text-align: center;">
                            <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <span style="font-size: 40px; color: white;">✓</span>
                            </div>
                            
                            <h1 style="margin: 0 0 20px; color: #333333; font-size: 24px;">
                                Senha Alterada com Sucesso!
                            </h1>
                            
                            <p style="margin: 0 0 20px; color: #555555; font-size: 14px; line-height: 1.6;">
                                $greeting,
                            </p>
                            
                            <p style="margin: 0 0 20px; color: #555555; font-size: 14px; line-height: 1.6;">
                                Sua senha foi <strong>alterada com sucesso</strong> em <strong>{$this->getCurrentDateTime()}</strong>.
                            </p>
                            
                            <div style="margin: 30px 0; padding: 15px; background-color: #d1ecf1; border-left: 4px solid #0c5460; border-radius: 4px;">
                                <p style="margin: 0; color: #0c5460; font-size: 13px; line-height: 1.6;">
                                    🔒 Se você <strong>não realizou</strong> esta alteração, entre em contato conosco imediatamente.
                                </p>
                            </div>
                            
                            <p style="margin: 30px 0 0; color: #777777; font-size: 12px;">
                                Atenciosamente,<br>
                                <strong>Equipe FinanSmart Pro</strong>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
            
            $this->mailer->Body = $body;
            $this->mailer->AltBody = "Olá" . ($userName ? " $userName" : "") . ",\n\n" .
                "Sua senha foi alterada com sucesso em {$this->getCurrentDateTime()}.\n\n" .
                "Se você não realizou esta alteração, entre em contato conosco imediatamente.\n\n" .
                "Atenciosamente,\nEquipe FinanSmart Pro";
            
            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Retorna data/hora atual formatada
     */
    private function getCurrentDateTime() {
        return date('d/m/Y \à\s H:i');
    }
    
    /**
     * Envia email genérico (para testes e outras funcionalidades)
     * 
     * @param string $toEmail Email do destinatário
     * @param string $subject Assunto do email
     * @param string $htmlBody Corpo do email em HTML
     * @param string $altBody Corpo alternativo em texto simples (opcional)
     * @return bool
     */
    public function sendEmail($toEmail, $subject, $htmlBody, $altBody = '') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            $this->mailer->addAddress($toEmail);
            $this->mailer->isHTML(true);
            
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $altBody ?: strip_tags($htmlBody);
            
            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
}
?>
