<?php
require '../includes/db.php';
session_start();
if(!isset($_SESSION['user_id'])) header('Location: ../login.php');
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?'); $stmt->execute([$user_id]); $user = $stmt->fetch();
$stmt = $pdo->prepare('SELECT l.*, c.nome as categoria_nome FROM lancamentos l LEFT JOIN categorias c ON l.id_categoria = c.id WHERE l.id_usuario = ? ORDER BY data DESC'); $stmt->execute([$user_id]); $lancamentos = $stmt->fetchAll();
require_once('../vendor/fpdf/fpdf.php');
$pdf = new FPDF(); $pdf->AddPage(); $pdf->SetFont('Arial','B',14); $pdf->Cell(0,10,'Relatório Financeiro - '.($user['nome']??''),0,1); $pdf->Ln(4); $pdf->SetFont('Arial','',10);
foreach($lancamentos as $l){ $pdf->Cell(30,7,$l['data']); $pdf->Cell(80,7,substr($l['descricao'],0,40)); $pdf->Cell(30,7,$l['moeda'].' '.number_format($l['valor'],2,',','.')); $pdf->Ln(); }
$pdf->Output();
?>