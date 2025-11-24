<?php
/**
 * Sistema de Segurança - FinanSmart Pro
 * Proteção CSRF, Rate Limiting, Validação
 */

class Security {
    
    /**
     * Gera token CSRF
     */
    public static function generateCSRFToken() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Valida token CSRF
     */
    public static function validateCSRFToken($token) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * HTML do campo CSRF
     */
    public static function csrfField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Rate Limiting - Login
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $data = $_SESSION[$key];
        
        // Resetar se passou o tempo
        if (time() - $data['first_attempt'] > $timeWindow) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Verificar limite
        if ($data['attempts'] >= $maxAttempts) {
            $remainingTime = $timeWindow - (time() - $data['first_attempt']);
            return [
                'blocked' => true,
                'remaining_time' => ceil($remainingTime / 60)
            ];
        }
        
        $_SESSION[$key]['attempts']++;
        return true;
    }
    
    /**
     * Limpar rate limit após login bem-sucedido
     */
    public static function clearRateLimit($identifier) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($identifier);
        unset($_SESSION[$key]);
    }
    
    /**
     * Validar e sanitizar entrada
     */
    public static function sanitize($data, $type = 'string') {
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return self::sanitize($item, $type);
            }, $data);
        }
        
        switch ($type) {
            case 'email':
                return filter_var(trim($data), FILTER_SANITIZE_EMAIL);
            
            case 'int':
                return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            
            case 'float':
                return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            case 'url':
                return filter_var(trim($data), FILTER_SANITIZE_URL);
            
            case 'string':
            default:
                return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validar email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validar data
     */
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validar valor monetário
     */
    public static function validateMoney($value) {
        // Remove formatação
        $clean = str_replace(['R$', '.', ' '], '', $value);
        $clean = str_replace(',', '.', $clean);
        
        return is_numeric($clean) ? floatval($clean) : false;
    }
    
    /**
     * Gerar nome seguro para arquivo
     */
    public static function secureFilename($filename) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        
        // Sanitizar nome
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename);
        
        // Gerar nome único
        return uniqid() . '_' . substr(md5($basename), 0, 8) . '.' . strtolower($extension);
    }
    
    /**
     * Validar tipo de arquivo
     */
    public static function validateFileType($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Verificar extensão
        if (!in_array($extension, $allowedTypes)) {
            return ['valid' => false, 'error' => 'Tipo de arquivo não permitido'];
        }
        
        // Verificar MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'gif' => 'image/gif'
        ];
        
        if (!isset($allowedMimes[$extension]) || $mimeType !== $allowedMimes[$extension]) {
            return ['valid' => false, 'error' => 'Tipo MIME inválido'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Configurar sessões seguras
     */
    public static function configureSecureSessions() {
        if (session_status() == PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 1); // Requer HTTPS
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerar ID periodicamente
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Log de segurança
     */
    public static function logSecurityEvent($event, $details = []) {
        $logDir = __DIR__ . '/../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/security_' . date('Y-m-d') . '.log';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND);
    }
    
    /**
     * Verificar se requisição é HTTPS
     */
    public static function requireHTTPS() {
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            if (php_sapi_name() !== 'cli') {
                header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
    }
    
    /**
     * Escapar output para prevenir XSS
     */
    public static function escape($data) {
        if (is_array($data)) {
            return array_map([self::class, 'escape'], $data);
        }
        
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}
