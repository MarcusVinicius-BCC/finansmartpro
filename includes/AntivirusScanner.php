<?php
/**
 * Sistema de Scan Antivírus - FinanSmart Pro
 * Proteção contra malware em uploads
 * 
 * Suporta:
 * - ClamAV (Linux/Windows)
 * - Windows Defender (Windows)
 * - Validação de assinatura de arquivo
 * - Detecção de scripts maliciosos
 */

class AntivirusScanner {
    
    private static $scannerType = null;
    private static $clamavSocket = '/var/run/clamav/clamd.ctl'; // Linux
    private static $clamavHost = 'localhost';
    private static $clamavPort = 3310;
    
    /**
     * Detecta qual antivírus está disponível
     */
    private static function detectScanner() {
        if (self::$scannerType !== null) {
            return self::$scannerType;
        }
        
        // Tentar ClamAV via socket (Linux)
        if (file_exists(self::$clamavSocket)) {
            self::$scannerType = 'clamav_socket';
            return self::$scannerType;
        }
        
        // Tentar ClamAV via TCP
        if (@fsockopen(self::$clamavHost, self::$clamavPort, $errno, $errstr, 1)) {
            self::$scannerType = 'clamav_tcp';
            return self::$scannerType;
        }
        
        // Tentar Windows Defender (Windows)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('where MpCmdRun.exe 2>nul', $output, $return);
            if ($return === 0) {
                self::$scannerType = 'windows_defender';
                return self::$scannerType;
            }
        }
        
        // Fallback: validação manual
        self::$scannerType = 'manual';
        return self::$scannerType;
    }
    
    /**
     * Scan de arquivo usando ClamAV via Socket
     */
    private static function scanWithClamAVSocket($filePath) {
        $socket = @socket_create(AF_UNIX, SOCK_STREAM, 0);
        if ($socket === false) {
            return ['safe' => false, 'error' => 'Erro ao criar socket'];
        }
        
        if (@socket_connect($socket, self::$clamavSocket) === false) {
            socket_close($socket);
            return ['safe' => false, 'error' => 'Erro ao conectar ClamAV'];
        }
        
        // Enviar comando SCAN
        $command = "SCAN " . $filePath . "\n";
        socket_write($socket, $command, strlen($command));
        
        $response = '';
        while ($out = socket_read($socket, 2048)) {
            $response .= $out;
        }
        
        socket_close($socket);
        
        // Analisar resposta
        if (strpos($response, 'OK') !== false) {
            return ['safe' => true, 'scanner' => 'ClamAV Socket'];
        } else if (strpos($response, 'FOUND') !== false) {
            preg_match('/: (.+) FOUND/', $response, $matches);
            $threat = $matches[1] ?? 'Malware detectado';
            return ['safe' => false, 'threat' => $threat, 'scanner' => 'ClamAV Socket'];
        }
        
        return ['safe' => false, 'error' => 'Resposta inválida do ClamAV'];
    }
    
    /**
     * Scan de arquivo usando ClamAV via TCP
     */
    private static function scanWithClamAVTCP($filePath) {
        $socket = @fsockopen(self::$clamavHost, self::$clamavPort, $errno, $errstr, 5);
        if (!$socket) {
            return ['safe' => false, 'error' => "Erro ao conectar ClamAV: $errstr"];
        }
        
        // Enviar arquivo
        fwrite($socket, "nINSTREAM\n");
        
        $fileHandle = fopen($filePath, 'rb');
        while (!feof($fileHandle)) {
            $chunk = fread($fileHandle, 8192);
            $size = pack('N', strlen($chunk));
            fwrite($socket, $size);
            fwrite($socket, $chunk);
        }
        fclose($fileHandle);
        
        // Sinalizar fim
        fwrite($socket, pack('N', 0));
        
        // Ler resposta
        $response = fread($socket, 4096);
        fclose($socket);
        
        // Analisar resposta
        if (strpos($response, 'OK') !== false) {
            return ['safe' => true, 'scanner' => 'ClamAV TCP'];
        } else if (strpos($response, 'FOUND') !== false) {
            preg_match('/: (.+) FOUND/', $response, $matches);
            $threat = $matches[1] ?? 'Malware detectado';
            return ['safe' => false, 'threat' => $threat, 'scanner' => 'ClamAV TCP'];
        }
        
        return ['safe' => false, 'error' => 'Resposta inválida do ClamAV'];
    }
    
    /**
     * Scan de arquivo usando Windows Defender
     */
    private static function scanWithWindowsDefender($filePath) {
        $mpCmd = 'C:\\Program Files\\Windows Defender\\MpCmdRun.exe';
        
        // Comando de scan
        $command = sprintf('"%s" -Scan -ScanType 3 -File "%s" 2>&1', $mpCmd, $filePath);
        
        exec($command, $output, $return);
        
        // Código de retorno 0 = limpo, 2 = ameaça encontrada
        if ($return === 0) {
            return ['safe' => true, 'scanner' => 'Windows Defender'];
        } else if ($return === 2) {
            return [
                'safe' => false, 
                'threat' => 'Ameaça detectada pelo Windows Defender',
                'scanner' => 'Windows Defender'
            ];
        }
        
        return ['safe' => false, 'error' => 'Erro ao executar Windows Defender'];
    }
    
    /**
     * Validação manual de arquivo (fallback)
     */
    private static function manualValidation($filePath) {
        $result = [
            'safe' => true,
            'scanner' => 'Validação Manual',
            'checks' => []
        ];
        
        // 1. Verificar magic bytes (assinatura de arquivo)
        $magicBytes = self::checkMagicBytes($filePath);
        $result['checks'][] = $magicBytes;
        if (!$magicBytes['valid']) {
            $result['safe'] = false;
            $result['threat'] = 'Assinatura de arquivo inválida';
        }
        
        // 2. Detectar scripts PHP/JS maliciosos em arquivos de imagem
        $scriptCheck = self::detectEmbeddedScripts($filePath);
        $result['checks'][] = $scriptCheck;
        if (!$scriptCheck['valid']) {
            $result['safe'] = false;
            $result['threat'] = 'Script malicioso detectado';
        }
        
        // 3. Verificar tamanho suspeito
        $sizeCheck = self::checkSuspiciousSize($filePath);
        $result['checks'][] = $sizeCheck;
        if (!$sizeCheck['valid']) {
            $result['safe'] = false;
            $result['threat'] = 'Tamanho de arquivo suspeito';
        }
        
        // 4. Detectar null bytes
        $nullByteCheck = self::checkNullBytes($filePath);
        $result['checks'][] = $nullByteCheck;
        if (!$nullByteCheck['valid']) {
            $result['safe'] = false;
            $result['threat'] = 'Null bytes detectados (possível bypass)';
        }
        
        return $result;
    }
    
    /**
     * Verificar assinatura de arquivo (magic bytes)
     */
    private static function checkMagicBytes($filePath) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['valid' => false, 'check' => 'magic_bytes', 'error' => 'Arquivo não acessível'];
        }
        
        $handle = @fopen($filePath, 'rb');
        if ($handle === false) {
            return ['valid' => false, 'check' => 'magic_bytes', 'error' => 'Erro ao abrir arquivo'];
        }
        
        $header = fread($handle, 12);
        fclose($handle);
        
        if ($header === false || strlen($header) < 4) {
            return ['valid' => false, 'check' => 'magic_bytes', 'error' => 'Erro ao ler header'];
        }
        
        $signatures = [
            'jpg' => ["\xFF\xD8\xFF"],
            'png' => ["\x89\x50\x4E\x47"],
            'gif' => ["\x47\x49\x46\x38"],
            'pdf' => ["\x25\x50\x44\x46"],
            'zip' => ["\x50\x4B\x03\x04", "\x50\x4B\x05\x06"],
            'doc' => ["\xD0\xCF\x11\xE0"],
            'xlsx' => ["\x50\x4B\x03\x04"],
        ];
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (!isset($signatures[$extension])) {
            return ['valid' => true, 'check' => 'magic_bytes', 'note' => 'Extensão desconhecida'];
        }
        
        foreach ($signatures[$extension] as $signature) {
            if (strpos($header, $signature) === 0) {
                return ['valid' => true, 'check' => 'magic_bytes'];
            }
        }
        
        return ['valid' => false, 'check' => 'magic_bytes', 'error' => 'Assinatura não corresponde'];
    }
    
    /**
     * Detectar scripts embutidos (PHP, JS)
     */
    private static function detectEmbeddedScripts($filePath) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['valid' => false, 'check' => 'embedded_scripts', 'error' => 'Arquivo não acessível'];
        }
        
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return ['valid' => false, 'check' => 'embedded_scripts', 'error' => 'Erro ao ler arquivo'];
        }
        
        $dangerousPatterns = [
            '/<\?php/i',                    // PHP tags
            '/<script/i',                   // JavaScript
            '/eval\s*\(/i',                 // eval()
            '/base64_decode/i',             // base64_decode
            '/exec\s*\(/i',                 // exec()
            '/system\s*\(/i',               // system()
            '/passthru\s*\(/i',             // passthru()
            '/shell_exec/i',                // shell_exec()
            '/\$_GET\[/i',                  // $_GET
            '/\$_POST\[/i',                 // $_POST
            '/\$_REQUEST\[/i',              // $_REQUEST
            '/document\.cookie/i',          // Cookie stealing
            '/window\.location/i',          // Redirect
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return ['valid' => false, 'check' => 'embedded_scripts', 'pattern' => $pattern];
            }
        }
        
        return ['valid' => true, 'check' => 'embedded_scripts'];
    }
    
    /**
     * Verificar tamanho suspeito
     */
    private static function checkSuspiciousSize($filePath) {
        $size = filesize($filePath);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // PDFs muito pequenos são suspeitos
        if ($extension === 'pdf' && $size < 1024) {
            return ['valid' => false, 'check' => 'file_size', 'error' => 'PDF muito pequeno'];
        }
        
        // Imagens muito pequenas são suspeitas
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) && $size < 100) {
            return ['valid' => false, 'check' => 'file_size', 'error' => 'Imagem muito pequena'];
        }
        
        return ['valid' => true, 'check' => 'file_size'];
    }
    
    /**
     * Detectar null bytes (técnica de bypass)
     */
    private static function checkNullBytes($filePath) {
        $filename = basename($filePath);
        
        if (strpos($filename, "\0") !== false) {
            return ['valid' => false, 'check' => 'null_bytes', 'error' => 'Null byte no nome'];
        }
        
        return ['valid' => true, 'check' => 'null_bytes'];
    }
    
    /**
     * MÉTODO PRINCIPAL: Scan de arquivo
     */
    public static function scanFile($filePath) {
        if (!file_exists($filePath)) {
            return [
                'safe' => false,
                'error' => 'Arquivo não encontrado',
                'scanner' => 'none'
            ];
        }
        
        $scannerType = self::detectScanner();
        
        $startTime = microtime(true);
        
        switch ($scannerType) {
            case 'clamav_socket':
                $result = self::scanWithClamAVSocket($filePath);
                break;
                
            case 'clamav_tcp':
                $result = self::scanWithClamAVTCP($filePath);
                break;
                
            case 'windows_defender':
                $result = self::scanWithWindowsDefender($filePath);
                break;
                
            case 'manual':
            default:
                $result = self::manualValidation($filePath);
                break;
        }
        
        $result['scan_time'] = round((microtime(true) - $startTime) * 1000, 2) . 'ms';
        $result['file_size'] = filesize($filePath);
        $result['file_name'] = basename($filePath);
        
        // Log do scan
        self::logScan($filePath, $result);
        
        return $result;
    }
    
    /**
     * Log de scan
     */
    private static function logScan($filePath, $result) {
        $logDir = __DIR__ . '/../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/antivirus_' . date('Y-m-d') . '.log';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'file' => basename($filePath),
            'size' => filesize($filePath),
            'result' => $result['safe'] ? 'CLEAN' : 'THREAT',
            'scanner' => $result['scanner'] ?? 'unknown',
            'threat' => $result['threat'] ?? null,
            'scan_time' => $result['scan_time'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND);
    }
    
    /**
     * Configurar ClamAV customizado
     */
    public static function configureClamAV($host = 'localhost', $port = 3310, $socket = null) {
        self::$clamavHost = $host;
        self::$clamavPort = $port;
        
        if ($socket !== null) {
            self::$clamavSocket = $socket;
        }
        
        // Resetar detecção
        self::$scannerType = null;
    }
    
    /**
     * Obter status do scanner
     */
    public static function getScannerStatus() {
        $scanner = self::detectScanner();
        
        return [
            'scanner' => $scanner,
            'available' => $scanner !== 'manual',
            'description' => self::getScannerDescription($scanner)
        ];
    }
    
    /**
     * Descrição do scanner
     */
    private static function getScannerDescription($scanner) {
        $descriptions = [
            'clamav_socket' => 'ClamAV via Socket Unix (recomendado)',
            'clamav_tcp' => 'ClamAV via TCP',
            'windows_defender' => 'Windows Defender',
            'manual' => 'Validação Manual (sem antivírus instalado)'
        ];
        
        return $descriptions[$scanner] ?? 'Desconhecido';
    }
}
