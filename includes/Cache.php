<?php
/**
 * Cache Component
 * Sistema de cache baseado em arquivos com TTL
 */

class Cache {
    private $cacheDir;
    private $defaultTTL;
    
    /**
     * Construtor da classe Cache
     * 
     * @param string $cacheDir Diretório de cache (padrão: cache/)
     * @param int $defaultTTL TTL padrão em segundos (padrão: 900 = 15min)
     */
    public function __construct($cacheDir = 'cache/', $defaultTTL = 900) {
        $this->cacheDir = rtrim($cacheDir, '/') . '/';
        $this->defaultTTL = $defaultTTL;
        
        // Criar diretório de cache se não existir
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Criar .htaccess para proteção
        $htaccessPath = $this->cacheDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Order Deny,Allow\nDeny from all");
        }
    }
    
    /**
     * Gera chave de cache (hash MD5)
     * 
     * @param string $key Chave original
     * @return string Hash MD5
     */
    private function getFilePath($key) {
        return $this->cacheDir . md5($key) . '.cache';
    }
    
    /**
     * Obtém valor do cache
     * 
     * @param string $key Chave de cache
     * @return mixed|null Valor ou null se expirado/inexistente
     */
    public function get($key) {
        $filePath = $this->getFilePath($key);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $content = file_get_contents($filePath);
        $data = @unserialize($content);
        
        if ($data === false || !is_array($data)) {
            // Cache corrompido, deletar
            @unlink($filePath);
            return null;
        }
        
        // Verificar expiração
        if (isset($data['expires']) && $data['expires'] < time()) {
            @unlink($filePath);
            return null;
        }
        
        return $data['value'] ?? null;
    }
    
    /**
     * Armazena valor no cache
     * 
     * @param string $key Chave de cache
     * @param mixed $value Valor a armazenar
     * @param int|null $ttl TTL em segundos (null = usar padrão)
     * @return bool True se sucesso
     */
    public function set($key, $value, $ttl = null) {
        $filePath = $this->getFilePath($key);
        $ttl = $ttl ?? $this->defaultTTL;
        
        $data = [
            'key' => $key,
            'value' => $value,
            'created' => time(),
            'expires' => time() + $ttl,
            'ttl' => $ttl
        ];
        
        $content = serialize($data);
        
        return file_put_contents($filePath, $content, LOCK_EX) !== false;
    }
    
    /**
     * Verifica se chave existe no cache (e não expirou)
     * 
     * @param string $key Chave de cache
     * @return bool True se existe e válido
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Remove item do cache
     * 
     * @param string $key Chave de cache
     * @return bool True se removido
     */
    public function delete($key) {
        $filePath = $this->getFilePath($key);
        
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        
        return false;
    }
    
    /**
     * Remove múltiplos itens por padrão de chave
     * 
     * @param string $pattern Padrão regex (ex: "^dashboard_")
     * @return int Quantidade removida
     */
    public function invalidatePattern($pattern) {
        $count = 0;
        $files = glob($this->cacheDir . '*.cache');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = @unserialize($content);
            
            if ($data && isset($data['key'])) {
                if (preg_match('/' . $pattern . '/', $data['key'])) {
                    if (@unlink($file)) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Limpa todo o cache
     * 
     * @return int Quantidade removida
     */
    public function flush() {
        $count = 0;
        $files = glob($this->cacheDir . '*.cache');
        
        foreach ($files as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Remove itens expirados (garbage collection)
     * 
     * @return int Quantidade removida
     */
    public function cleanExpired() {
        $count = 0;
        $files = glob($this->cacheDir . '*.cache');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = @unserialize($content);
            
            if ($data && isset($data['expires'])) {
                if ($data['expires'] < time()) {
                    if (@unlink($file)) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Obtém estatísticas do cache
     * 
     * @return array Estatísticas [total, size, oldest, newest]
     */
    public function getStats() {
        $files = glob($this->cacheDir . '*.cache');
        $totalSize = 0;
        $oldest = null;
        $newest = null;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $mtime = filemtime($file);
            
            if ($oldest === null || $mtime < $oldest) {
                $oldest = $mtime;
            }
            
            if ($newest === null || $mtime > $newest) {
                $newest = $mtime;
            }
        }
        
        return [
            'total_items' => count($files),
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'oldest' => $oldest ? date('Y-m-d H:i:s', $oldest) : null,
            'newest' => $newest ? date('Y-m-d H:i:s', $newest) : null
        ];
    }
    
    /**
     * Remember: Obtém do cache ou executa callback e armazena
     * 
     * @param string $key Chave de cache
     * @param callable $callback Função para obter valor
     * @param int|null $ttl TTL em segundos
     * @return mixed Valor do cache ou callback
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * RememberForever: Como remember mas sem expiração
     * 
     * @param string $key Chave de cache
     * @param callable $callback Função para obter valor
     * @return mixed Valor do cache ou callback
     */
    public function rememberForever($key, $callback) {
        return $this->remember($key, $callback, 31536000); // 1 ano
    }
    
    /**
     * Incrementa valor numérico no cache
     * 
     * @param string $key Chave de cache
     * @param int $value Valor a incrementar (padrão: 1)
     * @return int Novo valor
     */
    public function increment($key, $value = 1) {
        $current = $this->get($key) ?? 0;
        $new = $current + $value;
        $this->set($key, $new);
        return $new;
    }
    
    /**
     * Decrementa valor numérico no cache
     * 
     * @param string $key Chave de cache
     * @param int $value Valor a decrementar (padrão: 1)
     * @return int Novo valor
     */
    public function decrement($key, $value = 1) {
        return $this->increment($key, -$value);
    }
}
