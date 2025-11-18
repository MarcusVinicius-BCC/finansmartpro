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
        $this->SetFont('Arial','B',12);
        $this->Cell(0,10,'Relatório Financeiro',0,1,'C');
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Página '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

$stmt = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?'); 
$stmt->execute([$user_id]); 
$user = $stmt->fetch();

$pdf->Cell(0,10,'Usuário: '.($user['nome']??''),0,1);
$pdf->Cell(0,10,'Data: '.date('d/m/Y'),0,1);
$pdf->Ln(10);

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

$pdf->SetFont('Arial','B',12);
$pdf->Cell(30,10,'Data',1,0,'C');
$pdf->Cell(80,10,'Descrição',1,0,'C');
$pdf->Cell(40,10,'Categoria',1,0,'C');
$pdf->Cell(40,10,'Valor',1,1,'C');

$pdf->SetFont('Arial','',12);
$fill = false;
foreach($lancamentos as $l){
    $pdf->Cell(30,10,$l['data'],1,0,'C',$fill);
    $pdf->Cell(80,10,substr($l['descricao'],0,40),1,0,'L',$fill);
    $pdf->Cell(40,10,substr($l['categoria_nome'],0,20),1,0,'L',$fill);
    $pdf->Cell(40,10,$l['moeda'].' '.number_format($l['valor'],2,',','.'),1,1,'R',$fill);
    $fill = !$fill;
}

$pdf->Output();
?>