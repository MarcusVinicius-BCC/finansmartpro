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
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,utf8_decode('Relatório Financeiro'),0,1,'C');
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,utf8_decode('Página ').$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',11);

$stmt = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?'); 
$stmt->execute([$user_id]); 
$user = $stmt->fetch();

$pdf->Cell(0,8,utf8_decode('Usuário: '.($user['nome']??'')),0,1);
$pdf->Cell(0,8,'Data: '.date('d/m/Y'),0,1);
$pdf->Ln(5);

$sql = 'SELECT l.*, c.nome as categoria_nome FROM lancamentos l LEFT JOIN categorias c ON l.id_categoria = c.id WHERE l.id_usuario = ?';
$params = [$user_id];

if (!empty($_POST['start_date'])) {
    $sql .= ' AND l.data >= ?';
    $params[] = $_POST['start_date'];
}

if (!empty($_POST['end_date'])) {
    $sql .= ' AND l.data <= ?';
    $params[] = $_POST['end_date'];
}

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

// Cabeçalho da tabela
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(106, 13, 173); // Roxo
$pdf->SetTextColor(255, 255, 255); // Branco
$pdf->Cell(30,8,'Data',1,0,'C',true);
$pdf->Cell(70,8,utf8_decode('Descrição'),1,0,'C',true);
$pdf->Cell(45,8,'Categoria',1,0,'C',true);
$pdf->Cell(45,8,'Valor',1,1,'C',true);

// Dados
$pdf->SetFont('Arial','',10);
$pdf->SetTextColor(0, 0, 0); // Preto
$fill = false;
foreach($lancamentos as $l){
    $pdf->Cell(30,7,date('d/m/Y', strtotime($l['data'])),1,0,'C',$fill);
    $pdf->Cell(70,7,utf8_decode(substr($l['descricao'],0,35)),1,0,'L',$fill);
    $pdf->Cell(45,7,utf8_decode(substr($l['categoria_nome']??'',0,18)),1,0,'L',$fill);
    $valor = ($l['moeda']??'R$').' '.number_format($l['valor'],2,',','.');
    $pdf->Cell(45,7,$valor,1,1,'R',$fill);
    $fill = !$fill;
}

// Resumo
$pdf->Ln(5);
$pdf->SetFont('Arial','B',11);

// Calcular totais
$total_receitas = 0;
$total_despesas = 0;
foreach($lancamentos as $l){
    if($l['tipo'] == 'receita'){
        $total_receitas += $l['valor'];
    } else {
        $total_despesas += $l['valor'];
    }
}
$saldo = $total_receitas - $total_despesas;

$pdf->SetFillColor(40, 167, 69); // Verde
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(95,7,utf8_decode('Total Receitas:'),1,0,'L',true);
$pdf->Cell(95,7,'R$ '.number_format($total_receitas,2,',','.'),1,1,'R',true);

$pdf->SetFillColor(220, 53, 69); // Vermelho
$pdf->Cell(95,7,utf8_decode('Total Despesas:'),1,0,'L',true);
$pdf->Cell(95,7,'R$ '.number_format($total_despesas,2,',','.'),1,1,'R',true);

$pdf->SetFillColor(106, 13, 173); // Roxo
$pdf->Cell(95,7,'Saldo:',1,0,'L',true);
$pdf->Cell(95,7,'R$ '.number_format($saldo,2,',','.'),1,1,'R',true);

$pdf->Output();
?>