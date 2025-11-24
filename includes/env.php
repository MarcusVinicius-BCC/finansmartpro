<?php
/**
 * Loader de Variáveis de Ambiente
 */

class Env {
    private static $vars = [];
    private static $loaded = false;
    
    /**
     * Carregar arquivo .env
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }
        
        $path = $path ?? __DIR__ . '/../.env';
        
        if (!file_exists($path)) {
            $path = __DIR__ . '/../.env.example';
        }
        
        if (!file_exists($path)) {
            throw new Exception('.env file not found');
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse variável
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remover aspas
                $value = trim($value, '"\'');
                
                self::$vars[$name] = $value;
                
                // Definir como variável de ambiente
                if (!getenv($name)) {
                    putenv("$name=$value");
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Obter variável
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        // Tentar pegar de variável de ambiente primeiro
        $value = getenv($key);
        
        if ($value !== false) {
            return $value;
        }
        
        return self::$vars[$key] ?? $default;
    }
    
    /**
     * Verificar se variável existe
     */
    public static function has($key) {
        if (!self::$loaded) {
            self::load();
        }
        
        return isset(self::$vars[$key]) || getenv($key) !== false;
    }
    
    /**
     * Definir variável
     */
    public static function set($key, $value) {
        self::$vars[$key] = $value;
        putenv("$key=$value");
    }
}
