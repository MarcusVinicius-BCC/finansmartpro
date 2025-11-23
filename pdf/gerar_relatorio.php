<?php
require '../includes/db.php';
require_once('../vendor/fpdf/fpdf.php');

session_start();
if(!isset($_SESSION['user_id'])) header('Location: ../login.php');
$user_id = $_SESSION['user_id'];

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial','B',16);
        $this->SetTextColor(106, 13, 173);
        $this->Cell(0,10,utf8_decode('Relatório Financeiro Completo'),0,1,'C');
        $this->SetFont('Arial','I',10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0,5,'FinanSmart Pro',0,1,'C');
        $this->Ln(3);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0,10,utf8_decode('Página ').$this->PageNo().'/{nb} - Gerado em '.date('d/m/Y H:i'),0,0,'C');
    }
    
    function SectionTitle($title)
    {
        $this->SetFont('Arial','B',12);
        $this->SetFillColor(106, 13, 173);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0,8,utf8_decode($title),0,1,'L',true);
        $this->Ln(2);
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Informações do usuário
$stmt = $pdo->prepare('SELECT nome, moeda_base FROM usuarios WHERE id = ?'); 
$stmt->execute([$user_id]); 
$user = $stmt->fetch();

$pdf->SetFont('Arial','',10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0,6,utf8_decode('Usuário: '.($user['nome']??'')),0,1);
$pdf->Cell(0,6,'Data de Geracao: '.date('d/m/Y H:i:s'),0,1);

// Determinar período
$start_date = $_POST['start_date'] ?? date('Y-m-01', strtotime('-6 months'));
$end_date = $_POST['end_date'] ?? date('Y-m-d');
$pdf->Cell(0,6,utf8_decode('Período: '.date('d/m/Y', strtotime($start_date)).' a '.date('d/m/Y', strtotime($end_date))),0,1);
$pdf->Ln(5);

// Buscar lançamentos do período
$sql = 'SELECT l.*, c.nome as categoria_nome FROM lancamentos l LEFT JOIN categorias c ON l.id_categoria = c.id WHERE l.id_usuario = ? AND l.data >= ? AND l.data <= ?';
$params = [$user_id, $start_date, $end_date];

if (!empty($_POST['filter_type'])) {
    $sql .= ' AND l.tipo = ?';
    $params[] = $_POST['filter_type'];
}

if (!empty($_POST['filter_category'])) {
    $sql .= ' AND l.id_categoria = ?';
    $params[] = $_POST['filter_category'];
}

$sql .= ' ORDER BY data DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lancamentos = $stmt->fetchAll();

// === RESUMO FINANCEIRO ===
$pdf->SectionTitle('Resumo Financeiro');

$total_receitas = 0;
$total_despesas = 0;
$monthly_data = [];
$category_totals = [];

foreach($lancamentos as $l){
    $mes = date('Y-m', strtotime($l['data']));
    
    if(!isset($monthly_data[$mes])) {
        $monthly_data[$mes] = ['receitas' => 0, 'despesas' => 0];
    }
    
    if($l['tipo'] == 'receita'){
        $total_receitas += $l['valor'];
        $monthly_data[$mes]['receitas'] += $l['valor'];
    } else {
        $total_despesas += $l['valor'];
        $monthly_data[$mes]['despesas'] += $l['valor'];
        
        $cat = $l['categoria_nome'] ?? 'Sem Categoria';
        if(!isset($category_totals[$cat])) {
            $category_totals[$cat] = 0;
        }
        $category_totals[$cat] += $l['valor'];
    }
}

$saldo = $total_receitas - $total_despesas;

$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(40, 167, 69);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(95,7,utf8_decode('Total Receitas:'),1,0,'L',true);
$pdf->SetFont('Arial','',10);
$pdf->Cell(95,7,'R$ '.number_format($total_receitas,2,',','.'),1,1,'R',true);

$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(220, 53, 69);
$pdf->Cell(95,7,utf8_decode('Total Despesas:'),1,0,'L',true);
$pdf->SetFont('Arial','',10);
$pdf->Cell(95,7,'R$ '.number_format($total_despesas,2,',','.'),1,1,'R',true);

$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(106, 13, 173);
$pdf->Cell(95,7,'Saldo do Período:',1,0,'L',true);
$pdf->SetFont('Arial','',10);
$pdf->Cell(95,7,'R$ '.number_format($saldo,2,',','.'),1,1,'R',true);

$pdf->Ln(5);

// === ANÁLISE DE TENDÊNCIAS ===
$pdf->SectionTitle('Análise de Tendências Mensais');

ksort($monthly_data);
$meses = array_keys($monthly_data);

if(count($meses) >= 2) {
    $mes_anterior = $meses[count($meses) - 2];
    $mes_atual = $meses[count($meses) - 1];
    
    $receitas_anterior = $monthly_data[$mes_anterior]['receitas'];
    $receitas_atual = $monthly_data[$mes_atual]['receitas'];
    $despesas_anterior = $monthly_data[$mes_anterior]['despesas'];
    $despesas_atual = $monthly_data[$mes_atual]['despesas'];
    
    $var_receitas = $receitas_anterior > 0 ? (($receitas_atual - $receitas_anterior) / $receitas_anterior) * 100 : 0;
    $var_despesas = $despesas_anterior > 0 ? (($despesas_atual - $despesas_anterior) / $despesas_anterior) * 100 : 0;
    
    $pdf->SetFont('Arial','',9);
    $pdf->SetTextColor(0, 0, 0);
    
    $mes_anterior_nome = date('m/Y', strtotime($mes_anterior.'-01'));
    $mes_atual_nome = date('m/Y', strtotime($mes_atual.'-01'));
    
    $pdf->Cell(0,6,utf8_decode("Comparação: $mes_anterior_nome vs $mes_atual_nome"),0,1);
    $pdf->Ln(2);
    
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(50,6,'Indicador',1,0,'C',true);
    $pdf->Cell(35,6,$mes_anterior_nome,1,0,'C',true);
    $pdf->Cell(35,6,$mes_atual_nome,1,0,'C',true);
    $pdf->Cell(35,6,utf8_decode('Variação'),1,0,'C',true);
    $pdf->Cell(35,6,utf8_decode('Tendência'),1,1,'C',true);
    
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(50,6,'Receitas',1,0,'L');
    $pdf->Cell(35,6,'R$ '.number_format($receitas_anterior,2,',','.'),1,0,'R');
    $pdf->Cell(35,6,'R$ '.number_format($receitas_atual,2,',','.'),1,0,'R');
    $pdf->Cell(35,6,number_format($var_receitas,1,',','.').'%',1,0,'C');
    $pdf->Cell(35,6,$var_receitas >= 0 ? 'Positiva' : 'Negativa',1,1,'C');
    
    $pdf->Cell(50,6,'Despesas',1,0,'L');
    $pdf->Cell(35,6,'R$ '.number_format($despesas_anterior,2,',','.'),1,0,'R');
    $pdf->Cell(35,6,'R$ '.number_format($despesas_atual,2,',','.'),1,0,'R');
    $pdf->Cell(35,6,number_format($var_despesas,1,',','.').'%',1,0,'C');
    $pdf->Cell(35,6,$var_despesas <= 0 ? 'Positiva' : 'Negativa',1,1,'C');
}

$pdf->Ln(5);

// === PREVISÃO SIMPLES ===
if(count($meses) >= 3) {
    $pdf->SectionTitle(utf8_decode('Previsão para Próximo Mês'));
    
    $ultimos_3_meses_receitas = array_slice(array_column($monthly_data, 'receitas'), -3);
    $ultimos_3_meses_despesas = array_slice(array_column($monthly_data, 'despesas'), -3);
    
    $previsao_receitas = array_sum($ultimos_3_meses_receitas) / count($ultimos_3_meses_receitas);
    $previsao_despesas = array_sum($ultimos_3_meses_despesas) / count($ultimos_3_meses_despesas);
    $previsao_saldo = $previsao_receitas - $previsao_despesas;
    
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(0,6,utf8_decode('Baseado na média dos últimos 3 meses:'),0,1);
    $pdf->Ln(1);
    
    $pdf->Cell(95,6,utf8_decode('Receitas Previstas:'),1,0,'L');
    $pdf->Cell(95,6,'R$ '.number_format($previsao_receitas,2,',','.'),1,1,'R');
    
    $pdf->Cell(95,6,utf8_decode('Despesas Previstas:'),1,0,'L');
    $pdf->Cell(95,6,'R$ '.number_format($previsao_despesas,2,',','.'),1,1,'R');
    
    $pdf->Cell(95,6,utf8_decode('Saldo Previsto:'),1,0,'L');
    $pdf->Cell(95,6,'R$ '.number_format($previsao_saldo,2,',','.'),1,1,'R');
}

$pdf->Ln(5);

// === TOP CATEGORIAS ===
if(!empty($category_totals)) {
    $pdf->SectionTitle('Top 5 Categorias de Despesa');
    
    arsort($category_totals);
    $top_categories = array_slice($category_totals, 0, 5, true);
    
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(106, 13, 173);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(120,6,'Categoria',1,0,'L',true);
    $pdf->Cell(35,6,'Total',1,0,'R',true);
    $pdf->Cell(35,6,'% do Total',1,1,'C',true);
    
    $pdf->SetFont('Arial','',9);
    $pdf->SetTextColor(0, 0, 0);
    
    foreach($top_categories as $cat => $total) {
        $percentage = ($total_despesas > 0) ? ($total / $total_despesas) * 100 : 0;
        $pdf->Cell(120,6,utf8_decode(substr($cat, 0, 45)),1,0,'L');
        $pdf->Cell(35,6,'R$ '.number_format($total,2,',','.'),1,0,'R');
        $pdf->Cell(35,6,number_format($percentage,1,',','.').'%',1,1,'C');
    }
}

$pdf->AddPage();

// === DETALHAMENTO DE LANÇAMENTOS ===
$pdf->SectionTitle(utf8_decode('Detalhamento de Lançamentos'));

$pdf->SetFont('Arial','B',8);
$pdf->SetFillColor(106, 13, 173);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(25,6,'Data',1,0,'C',true);
$pdf->Cell(60,6,utf8_decode('Descrição'),1,0,'C',true);
$pdf->Cell(45,6,'Categoria',1,0,'C',true);
$pdf->Cell(30,6,'Tipo',1,0,'C',true);
$pdf->Cell(30,6,'Valor',1,1,'C',true);

$pdf->SetFont('Arial','',8);
$pdf->SetTextColor(0, 0, 0);
$fill = false;

foreach($lancamentos as $l){
    $pdf->Cell(25,5,date('d/m/Y', strtotime($l['data'])),1,0,'C',$fill);
    $pdf->Cell(60,5,utf8_decode(substr($l['descricao'],0,30)),1,0,'L',$fill);
    $pdf->Cell(45,5,utf8_decode(substr($l['categoria_nome']??'N/A',0,20)),1,0,'L',$fill);
    $pdf->Cell(30,5,ucfirst($l['tipo']),1,0,'C',$fill);
    $valor = 'R$ '.number_format($l['valor'],2,',','.');
    $pdf->Cell(30,5,$valor,1,1,'R',$fill);
    $fill = !$fill;
}

$pdf->Output();
?>