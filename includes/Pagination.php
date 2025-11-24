<?php
/**
 * Pagination Component
 * Sistema de paginação reutilizável para listas grandes
 */

class Pagination {
    private $total;
    private $perPage;
    private $currentPage;
    private $totalPages;
    private $baseUrl;
    
    /**
     * Construtor da classe Pagination
     * 
     * @param int $total Total de registros
     * @param int $perPage Itens por página (padrão: 50)
     * @param int $currentPage Página atual (padrão: 1)
     * @param string $baseUrl URL base para links (padrão: página atual)
     */
    public function __construct($total, $perPage = 50, $currentPage = 1, $baseUrl = null) {
        $this->total = max(0, (int)$total);
        $this->perPage = max(1, (int)$perPage);
        $this->currentPage = max(1, (int)$currentPage);
        $this->totalPages = $this->total > 0 ? (int)ceil($this->total / $this->perPage) : 1;
        
        // Garantir que currentPage não exceda totalPages
        if ($this->currentPage > $this->totalPages) {
            $this->currentPage = $this->totalPages;
        }
        
        // URL base (remove parâmetro 'page' se existir)
        $this->baseUrl = $baseUrl ?? strtok($_SERVER['REQUEST_URI'], '?');
    }
    
    /**
     * Retorna o offset para SQL LIMIT
     * 
     * @return int Offset calculado
     */
    public function getOffset() {
        return ($this->currentPage - 1) * $this->perPage;
    }
    
    /**
     * Retorna o limite para SQL LIMIT
     * 
     * @return int Limite por página
     */
    public function getLimit() {
        return $this->perPage;
    }
    
    /**
     * Retorna a página atual
     * 
     * @return int Página atual
     */
    public function getCurrentPage() {
        return $this->currentPage;
    }
    
    /**
     * Retorna o total de páginas
     * 
     * @return int Total de páginas
     */
    public function getTotalPages() {
        return $this->totalPages;
    }
    
    /**
     * Retorna o total de registros
     * 
     * @return int Total de registros
     */
    public function getTotal() {
        return $this->total;
    }
    
    /**
     * Verifica se há página anterior
     * 
     * @return bool True se há página anterior
     */
    public function hasPrevious() {
        return $this->currentPage > 1;
    }
    
    /**
     * Verifica se há próxima página
     * 
     * @return bool True se há próxima página
     */
    public function hasNext() {
        return $this->currentPage < $this->totalPages;
    }
    
    /**
     * Retorna o número da página anterior
     * 
     * @return int|null Número da página anterior ou null
     */
    public function getPreviousPage() {
        return $this->hasPrevious() ? $this->currentPage - 1 : null;
    }
    
    /**
     * Retorna o número da próxima página
     * 
     * @return int|null Número da próxima página ou null
     */
    public function getNextPage() {
        return $this->hasNext() ? $this->currentPage + 1 : null;
    }
    
    /**
     * Gera URL para uma página específica
     * 
     * @param int $page Número da página
     * @return string URL completa
     */
    private function getPageUrl($page) {
        $params = $_GET;
        $params['page'] = $page;
        return $this->baseUrl . '?' . http_build_query($params);
    }
    
    /**
     * Retorna array de páginas para exibição (com ellipsis)
     * 
     * @param int $adjacents Páginas adjacentes à atual (padrão: 2)
     * @return array Array de páginas [número => label]
     */
    public function getPageRange($adjacents = 2) {
        $pages = [];
        
        if ($this->totalPages <= 7) {
            // Mostrar todas as páginas se forem poucas
            for ($i = 1; $i <= $this->totalPages; $i++) {
                $pages[$i] = $i;
            }
        } else {
            // Sempre mostrar primeira página
            $pages[1] = 1;
            
            // Calcular range de páginas ao redor da atual
            $start = max(2, $this->currentPage - $adjacents);
            $end = min($this->totalPages - 1, $this->currentPage + $adjacents);
            
            // Adicionar ellipsis após primeira página se necessário
            if ($start > 2) {
                $pages['start_ellipsis'] = '...';
            }
            
            // Adicionar páginas do range
            for ($i = $start; $i <= $end; $i++) {
                $pages[$i] = $i;
            }
            
            // Adicionar ellipsis antes da última página se necessário
            if ($end < $this->totalPages - 1) {
                $pages['end_ellipsis'] = '...';
            }
            
            // Sempre mostrar última página
            $pages[$this->totalPages] = $this->totalPages;
        }
        
        return $pages;
    }
    
    /**
     * Renderiza HTML do paginador (Bootstrap 5)
     * 
     * @param string $size Tamanho: 'sm', 'lg' ou '' (padrão: '')
     * @param string $alignment Alinhamento: 'start', 'center', 'end' (padrão: 'center')
     * @return string HTML do paginador
     */
    public function render($size = '', $alignment = 'center') {
        if ($this->totalPages <= 1) {
            return ''; // Não mostrar paginador se só há 1 página
        }
        
        $sizeClass = $size ? "pagination-{$size}" : '';
        $alignClass = "justify-content-{$alignment}";
        
        $html = '<nav aria-label="Navegação de páginas">';
        $html .= '<ul class="pagination ' . $sizeClass . ' ' . $alignClass . ' mb-0">';
        
        // Botão "Anterior"
        if ($this->hasPrevious()) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->getPageUrl($this->getPreviousPage()) . '" aria-label="Anterior">';
            $html .= '<span aria-hidden="true">&laquo;</span>';
            $html .= '</a></li>';
        } else {
            $html .= '<li class="page-item disabled">';
            $html .= '<span class="page-link" aria-hidden="true">&laquo;</span>';
            $html .= '</li>';
        }
        
        // Páginas numeradas
        foreach ($this->getPageRange() as $pageNum => $pageLabel) {
            if (is_string($pageNum)) {
                // Ellipsis
                $html .= '<li class="page-item disabled">';
                $html .= '<span class="page-link">...</span>';
                $html .= '</li>';
            } else {
                $isActive = $pageNum == $this->currentPage;
                $html .= '<li class="page-item ' . ($isActive ? 'active' : '') . '">';
                
                if ($isActive) {
                    $html .= '<span class="page-link">' . $pageLabel . '</span>';
                } else {
                    $html .= '<a class="page-link" href="' . $this->getPageUrl($pageNum) . '">' . $pageLabel . '</a>';
                }
                
                $html .= '</li>';
            }
        }
        
        // Botão "Próximo"
        if ($this->hasNext()) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->getPageUrl($this->getNextPage()) . '" aria-label="Próximo">';
            $html .= '<span aria-hidden="true">&raquo;</span>';
            $html .= '</a></li>';
        } else {
            $html .= '<li class="page-item disabled">';
            $html .= '<span class="page-link" aria-hidden="true">&raquo;</span>';
            $html .= '</li>';
        }
        
        $html .= '</ul></nav>';
        
        return $html;
    }
    
    /**
     * Renderiza informações de exibição (ex: "Mostrando 1-50 de 234")
     * 
     * @return string HTML com informações
     */
    public function renderInfo() {
        if ($this->total == 0) {
            return '<p class="text-muted mb-0">Nenhum registro encontrado</p>';
        }
        
        $start = $this->getOffset() + 1;
        $end = min($this->getOffset() + $this->perPage, $this->total);
        
        return '<p class="text-muted mb-0">Mostrando ' . $start . '-' . $end . ' de ' . $this->total . ' registros</p>';
    }
    
    /**
     * Renderiza paginador completo com informações
     * 
     * @param string $size Tamanho do paginador
     * @return string HTML completo
     */
    public function renderComplete($size = '') {
        if ($this->total == 0) {
            return $this->renderInfo();
        }
        
        $html = '<div class="d-flex justify-content-between align-items-center flex-wrap gap-3">';
        $html .= '<div>' . $this->renderInfo() . '</div>';
        $html .= '<div>' . $this->render($size) . '</div>';
        $html .= '</div>';
        
        return $html;
    }
}
