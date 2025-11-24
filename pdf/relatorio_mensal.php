<?php
/**
 * Relatório Mensal em PDF
 * Gera PDF profissional com resumo financeiro mensal
 */

require_once '../includes/db.php';
require_once '../includes/currency.php';
require_once '../includes/security.php';
require_once '../vendor/fpdf/fpdf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Validar CSRF
if (!Security::validateCSRFToken($_GET['csrf_token'] ?? '')) {
    die('Token CSRF inválido');
}

$user_id = $_SESSION['user_id'];
$mes_ano = $_GET['mes_ano'] ?? date('Y-m');

class RelatorioMensalPDF extends FPDF {
    private $mesAno;
    private $userName;
    
    function __construct($mes_ano, $user_name) {
        parent::__construct();
        $this->mesAno = $mes_ano;
        $this->userName = $user_name;
    }
    
    // Cabeçalho
    function Header() {
        // Logo (se existir)
        $logoPath = '../assets/img/mockup.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 10, 6, 30);
        }
        
        // Título
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(102, 13, 173); // Roxo #660dad
        $this->Cell(0, 10, utf8_decode('FinanSmart Pro'), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 6, utf8_decode('Relatório Financeiro Mensal'), 0, 1, 'C');
        
        // Data do relatório
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, utf8_decode('Período: ') . date('m/Y', strtotime($this->mesAno . '-01')), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('Gerado em: ') . date('d/m/Y H:i'), 0, 1, 'C');
        
        // Linha separadora
        $this->Ln(3);
        $this->SetDrawColor(102, 13, 173);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }
    
    // Rodapé
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        
        // Linha separadora
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(0.2);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(2);
        
        // Número da página
        $this->Cell(0, 5, utf8_decode('Página ') . $this->PageNo() . ' | ' . $this->userName, 0, 0, 'C');
    }
    
    // Caixa de resumo colorida
    function ResumoBox($titulo, $valor, $cor) {
        $this->SetFillColor($cor[0], $cor[1], $cor[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        
        $this->Cell(60, 8, utf8_decode($titulo), 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(60, 8, $valor, 1, 1, 'R', true);
        $this->SetTextColor(0, 0, 0);
    }
    
    // Tabela de lançamentos
    function TabelaLancamentos($headers, $data) {
        // Cabeçalho da tabela
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 10);
        
        $widths = [25, 80, 45, 35];
        foreach ($headers as $i => $header) {
            $this->Cell($widths[$i], 7, utf8_decode($header), 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Dados
        $this->SetFont('Arial', '', 9);
        $fill = false;
        
        foreach ($data as $row) {
            $this->SetFillColor($fill ? 250 : 255, $fill ? 250 : 255, $fill ? 250 : 255);
            
            // Data
            $this->Cell($widths[0], 6, $row['data'], 1, 0, 'C', $fill);
            
            // Descrição (truncar se muito longo)
            $descricao = mb_substr($row['descricao'], 0, 40);
            $this->Cell($widths[1], 6, utf8_decode($descricao), 1, 0, 'L', $fill);
            
            // Categoria
            $categoria = mb_substr($row['categoria'], 0, 20);
            $this->Cell($widths[2], 6, utf8_decode($categoria), 1, 0, 'L', $fill);
            
            // Valor (colorido)
            if ($row['tipo'] == 'receita') {
                $this->SetTextColor(0, 150, 0); // Verde
                $valor = '+' . $row['valor'];
            } else {
                $this->SetTextColor(200, 0, 0); // Vermelho
                $valor = '-' . $row['valor'];
            }
            $this->Cell($widths[3], 6, $valor, 1, 1, 'R', $fill);
            $this->SetTextColor(0, 0, 0);
            
            $fill = !$fill;
        }
    }
    
    // Gráfico de pizza simples (texto)
    function GraficoCategorias($categorias) {
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 7, utf8_decode('Top 5 Categorias de Despesa'), 0, 1);
        
        $this->SetFont('Arial', '', 9);
        $total = array_sum(array_column($categorias, 'total'));
        
        $cores = [
            [255, 99, 132],
            [54, 162, 235],
            [255, 206, 86],
            [75, 192, 192],
            [153, 102, 255]
        ];
        
        foreach ($categorias as $i => $cat) {
            if ($i >= 5) break; // Top 5
            
            $percentual = $total > 0 ? ($cat['total'] / $total) * 100 : 0;
            
            // Barra de cor
            $this->SetFillColor($cores[$i][0], $cores[$i][1], $cores[$i][2]);
            $this->Cell(5, 5, '', 1, 0, 'C', true);
            
            // Nome e valor
            $this->SetFont('Arial', '', 9);
            $this->Cell(100, 5, utf8_decode(' ' . $cat['nome']), 0, 0);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(35, 5, $cat['total'], 0, 0, 'R');
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 5, ' (' . number_format($percentual, 1, ',', '.') . '%)', 0, 1);
        }
    }
}

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Buscar lançamentos do mês
$stmt = $pdo->prepare("
    SELECT l.*, c.nome as categoria_nome
    FROM lancamentos l
    LEFT JOIN categorias c ON l.id_categoria = c.id
    WHERE l.id_usuario = ?
    AND DATE_FORMAT(l.data, '%Y-%m') = ?
    ORDER BY l.data DESC, l.id DESC
");
$stmt->execute([$user_id, $mes_ano]);
$lancamentos = $stmt->fetchAll();

// Calcular totais
$total_receitas = 0;
$total_despesas = 0;
foreach ($lancamentos as $l) {
    if ($l['tipo'] == 'receita') {
        $total_receitas += $l['valor'];
    } else {
        $total_despesas += $l['valor'];
    }
}
$saldo = $total_receitas - $total_despesas;

// Top categorias de despesa
$stmt = $pdo->prepare("
    SELECT c.nome, SUM(l.valor) as total
    FROM lancamentos l
    JOIN categorias c ON l.id_categoria = c.id
    WHERE l.id_usuario = ?
    AND l.tipo = 'despesa'
    AND DATE_FORMAT(l.data, '%Y-%m') = ?
    GROUP BY c.id, c.nome
    ORDER BY total DESC
    LIMIT 5
");
$stmt->execute([$user_id, $mes_ano]);
$top_categorias = $stmt->fetchAll();

// Gerar PDF
$pdf = new RelatorioMensalPDF($mes_ano, $user['nome']);
$pdf->AddPage();

// Resumo Financeiro
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(102, 13, 173);
$pdf->Cell(0, 10, utf8_decode('Resumo Financeiro'), 0, 1);
$pdf->Ln(2);

// Boxes de resumo
$pdf->ResumoBox('Total Receitas', 'R$ ' . number_format($total_receitas, 2, ',', '.'), [76, 175, 80]); // Verde
$pdf->Ln(1);
$pdf->ResumoBox('Total Despesas', 'R$ ' . number_format($total_despesas, 2, ',', '.'), [244, 67, 54]); // Vermelho
$pdf->Ln(1);

$saldoCor = $saldo >= 0 ? [33, 150, 243] : [255, 152, 0]; // Azul ou Laranja
$pdf->ResumoBox('Saldo do Período', 'R$ ' . number_format($saldo, 2, ',', '.'), $saldoCor);

$pdf->Ln(10);

// Gráfico de categorias
if (!empty($top_categorias)) {
    $categorias_formatted = [];
    foreach ($top_categorias as $cat) {
        $categorias_formatted[] = [
            'nome' => $cat['nome'],
            'total' => 'R$ ' . number_format($cat['total'], 2, ',', '.')
        ];
    }
    $pdf->GraficoCategorias($categorias_formatted);
    $pdf->Ln(8);
}

// Tabela de lançamentos
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(102, 13, 173);
$pdf->Cell(0, 10, utf8_decode('Lançamentos Detalhados'), 0, 1);
$pdf->Ln(2);

if (!empty($lancamentos)) {
    $data = [];
    foreach ($lancamentos as $l) {
        $data[] = [
            'data' => date('d/m/Y', strtotime($l['data'])),
            'descricao' => $l['descricao'],
            'categoria' => $l['categoria_nome'] ?? '-',
            'valor' => 'R$ ' . number_format($l['valor'], 2, ',', '.'),
            'tipo' => $l['tipo']
        ];
    }
    
    $pdf->TabelaLancamentos(
        ['Data', utf8_decode('Descrição'), 'Categoria', 'Valor'],
        $data
    );
} else {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 8, utf8_decode('Nenhum lançamento encontrado neste período.'), 0, 1, 'C');
}

// Output
$filename = 'relatorio_' . $mes_ano . '_' . date('Ymd_His') . '.pdf';
$pdf->Output('D', $filename);

// Log de geração
Security::logSecurityEvent('pdf_generated', [
    'user_id' => $user_id,
    'periodo' => $mes_ano,
    'total_lancamentos' => count($lancamentos)
]);
?>
