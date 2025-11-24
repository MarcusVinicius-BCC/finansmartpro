<?php
/**
 * Exportação Excel - Relatório Financeiro Completo
 * Gera arquivo Excel (.xlsx) com múltiplas planilhas
 */

require_once '../includes/db.php';
require_once '../includes/currency.php';
require_once '../includes/security.php';

// Verificar se PhpSpreadsheet está instalado
if (!file_exists('../vendor/autoload.php')) {
    die('PhpSpreadsheet não instalado. Execute: composer require phpoffice/phpspreadsheet');
}

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

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
$tipo_export = $_GET['tipo'] ?? 'completo'; // completo, resumo, categorias

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Criar planilha
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator('FinanSmart Pro')
    ->setTitle('Relatório Financeiro - ' . date('m/Y', strtotime($mes_ano . '-01')))
    ->setSubject('Relatório Mensal')
    ->setDescription('Relatório financeiro gerado pelo FinanSmart Pro')
    ->setCategory('Finanças');

// =====================================================
// ABA 1: RESUMO MENSAL
// =====================================================
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Resumo');

// Cabeçalho
$sheet->setCellValue('A1', 'FINANSMART PRO');
$sheet->mergeCells('A1:E1');
$sheet->getStyle('A1')->getFont()->setSize(18)->setBold(true);
$sheet->getStyle('A1')->getFont()->getColor()->setARGB('FF660DAD');
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'Relatório Financeiro Mensal');
$sheet->mergeCells('A2:E2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A3', 'Período: ' . date('m/Y', strtotime($mes_ano . '-01')));
$sheet->mergeCells('A3:E3');
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A4', 'Usuário: ' . $user['nome']);
$sheet->mergeCells('A4:E4');
$sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A4')->getFont()->setItalic(true);

// Buscar totais
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as total_receitas,
        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as total_despesas,
        COUNT(*) as total_lancamentos
    FROM lancamentos
    WHERE id_usuario = ?
    AND DATE_FORMAT(data, '%Y-%m') = ?
");
$stmt->execute([$user_id, $mes_ano]);
$totais = $stmt->fetch();

$total_receitas = $totais['total_receitas'] ?? 0;
$total_despesas = $totais['total_despesas'] ?? 0;
$saldo = $total_receitas - $total_despesas;
$total_lancamentos = $totais['total_lancamentos'] ?? 0;

// Resumo Financeiro
$row = 6;
$sheet->setCellValue('B' . $row, 'RESUMO FINANCEIRO');
$sheet->mergeCells('B' . $row . ':D' . $row);
$sheet->getStyle('B' . $row)->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
$row += 2;

// Total Receitas
$sheet->setCellValue('B' . $row, 'Total Receitas');
$sheet->setCellValue('D' . $row, $total_receitas);
$sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
$sheet->getStyle('B' . $row . ':D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD4EDDA');
$sheet->getStyle('B' . $row)->getFont()->setBold(true);
$row++;

// Total Despesas
$sheet->setCellValue('B' . $row, 'Total Despesas');
$sheet->setCellValue('D' . $row, $total_despesas);
$sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
$sheet->getStyle('B' . $row . ':D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8D7DA');
$sheet->getStyle('B' . $row)->getFont()->setBold(true);
$row++;

// Saldo
$sheet->setCellValue('B' . $row, 'Saldo do Período');
$sheet->setCellValue('D' . $row, $saldo);
$sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
$bgColor = $saldo >= 0 ? 'FFD1ECF1' : 'FFFFF3CD';
$sheet->getStyle('B' . $row . ':D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
$sheet->getStyle('B' . $row . ':D' . $row)->getFont()->setBold(true);
$row += 2;

// Métricas adicionais
$sheet->setCellValue('B' . $row, 'Total de Lançamentos');
$sheet->setCellValue('D' . $row, $total_lancamentos);
$row++;

$ticket_medio = $total_lancamentos > 0 ? $total_despesas / $total_lancamentos : 0;
$sheet->setCellValue('B' . $row, 'Ticket Médio Despesas');
$sheet->setCellValue('D' . $row, $ticket_medio);
$sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
$row++;

$taxa_economia = $total_receitas > 0 ? (($total_receitas - $total_despesas) / $total_receitas) * 100 : 0;
$sheet->setCellValue('B' . $row, 'Taxa de Economia');
$sheet->setCellValue('D' . $row, $taxa_economia / 100);
$sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('0.00%');

// Bordas
$sheet->getStyle('B6:D' . $row)->applyFromArray([
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]
    ]
]);

// Ajustar largura das colunas
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(25);
$sheet->getColumnDimension('C')->setWidth(5);
$sheet->getColumnDimension('D')->setWidth(20);
$sheet->getColumnDimension('E')->setWidth(5);

// =====================================================
// ABA 2: LANÇAMENTOS DETALHADOS
// =====================================================
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Lançamentos');

// Cabeçalhos
$headers = ['ID', 'Data', 'Descrição', 'Categoria', 'Tipo', 'Valor', 'Conta', 'Status'];
$col = 'A';
foreach ($headers as $header) {
    $sheet2->setCellValue($col . '1', $header);
    $sheet2->getStyle($col . '1')->getFont()->setBold(true);
    $sheet2->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF660DAD');
    $sheet2->getStyle($col . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
    $sheet2->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $col++;
}

// Buscar lançamentos
$stmt = $pdo->prepare("
    SELECT 
        l.id,
        l.data,
        l.descricao,
        c.nome as categoria,
        l.tipo,
        l.valor,
        co.nome as conta,
        l.status
    FROM lancamentos l
    LEFT JOIN categorias c ON l.id_categoria = c.id
    LEFT JOIN contas co ON l.id_conta = co.id
    WHERE l.id_usuario = ?
    AND DATE_FORMAT(l.data, '%Y-%m') = ?
    ORDER BY l.data DESC, l.id DESC
");
$stmt->execute([$user_id, $mes_ano]);
$lancamentos = $stmt->fetchAll();

// Preencher dados
$row = 2;
foreach ($lancamentos as $lanc) {
    $sheet2->setCellValue('A' . $row, $lanc['id']);
    $sheet2->setCellValue('B' . $row, date('d/m/Y', strtotime($lanc['data'])));
    $sheet2->setCellValue('C' . $row, $lanc['descricao']);
    $sheet2->setCellValue('D' . $row, $lanc['categoria'] ?? '-');
    $sheet2->setCellValue('E' . $row, ucfirst($lanc['tipo']));
    $sheet2->setCellValue('F' . $row, $lanc['valor']);
    $sheet2->setCellValue('G' . $row, $lanc['conta'] ?? '-');
    $sheet2->setCellValue('H' . $row, ucfirst($lanc['status']));
    
    // Formatação de valor
    $sheet2->getStyle('F' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
    
    // Cor da linha baseada no tipo
    if ($lanc['tipo'] == 'receita') {
        $sheet2->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE8F5E9');
    } else {
        $sheet2->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFEEE');
    }
    
    $row++;
}

// Totais no final
if (!empty($lancamentos)) {
    $row++;
    $sheet2->setCellValue('E' . $row, 'TOTAIS:');
    $sheet2->getStyle('E' . $row)->getFont()->setBold(true);
    
    // Fórmula para somar valores
    $sheet2->setCellValue('F' . $row, '=SUM(F2:F' . ($row - 2) . ')');
    $sheet2->getStyle('F' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
    $sheet2->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);
    $sheet2->getStyle('E' . $row . ':F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
}

// Bordas
if ($row > 2) {
    $sheet2->getStyle('A1:H' . ($row))->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]
        ]
    ]);
}

// Auto-ajustar colunas
foreach (range('A', 'H') as $col) {
    $sheet2->getColumnDimension($col)->setAutoSize(true);
}

// =====================================================
// ABA 3: POR CATEGORIA
// =====================================================
$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('Por Categoria');

// Cabeçalhos
$sheet3->setCellValue('A1', 'Categoria');
$sheet3->setCellValue('B1', 'Receitas');
$sheet3->setCellValue('C1', 'Despesas');
$sheet3->setCellValue('D1', 'Saldo');
$sheet3->setCellValue('E1', '% do Total');

$sheet3->getStyle('A1:E1')->getFont()->setBold(true);
$sheet3->getStyle('A1:E1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF660DAD');
$sheet3->getStyle('A1:E1')->getFont()->getColor()->setARGB('FFFFFFFF');
$sheet3->getStyle('A1:E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Buscar dados por categoria
$stmt = $pdo->prepare("
    SELECT 
        c.nome as categoria,
        SUM(CASE WHEN l.tipo = 'receita' THEN l.valor ELSE 0 END) as receitas,
        SUM(CASE WHEN l.tipo = 'despesa' THEN l.valor ELSE 0 END) as despesas
    FROM lancamentos l
    LEFT JOIN categorias c ON l.id_categoria = c.id
    WHERE l.id_usuario = ?
    AND DATE_FORMAT(l.data, '%Y-%m') = ?
    GROUP BY c.id, c.nome
    ORDER BY despesas DESC
");
$stmt->execute([$user_id, $mes_ano]);
$por_categoria = $stmt->fetchAll();

$row = 2;
foreach ($por_categoria as $cat) {
    $saldo_cat = $cat['receitas'] - $cat['despesas'];
    $percentual = $total_despesas > 0 ? ($cat['despesas'] / $total_despesas) : 0;
    
    $sheet3->setCellValue('A' . $row, $cat['categoria'] ?? 'Sem categoria');
    $sheet3->setCellValue('B' . $row, $cat['receitas']);
    $sheet3->setCellValue('C' . $row, $cat['despesas']);
    $sheet3->setCellValue('D' . $row, $saldo_cat);
    $sheet3->setCellValue('E' . $row, $percentual);
    
    $sheet3->getStyle('B' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
    $sheet3->getStyle('C' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
    $sheet3->getStyle('D' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
    $sheet3->getStyle('E' . $row)->getNumberFormat()->setFormatCode('0.00%');
    
    $row++;
}

// Totais
if (!empty($por_categoria)) {
    $row++;
    $sheet3->setCellValue('A' . $row, 'TOTAL GERAL');
    $sheet3->setCellValue('B' . $row, '=SUM(B2:B' . ($row - 2) . ')');
    $sheet3->setCellValue('C' . $row, '=SUM(C2:C' . ($row - 2) . ')');
    $sheet3->setCellValue('D' . $row, '=B' . $row . '-C' . $row);
    $sheet3->setCellValue('E' . $row, '=SUM(E2:E' . ($row - 2) . ')');
    
    $sheet3->getStyle('B' . $row . ':D' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
    $sheet3->getStyle('E' . $row)->getNumberFormat()->setFormatCode('0.00%');
    $sheet3->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);
    $sheet3->getStyle('A' . $row . ':E' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
}

// Bordas e auto-ajuste
if ($row > 2) {
    $sheet3->getStyle('A1:E' . $row)->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]
        ]
    ]);
}

foreach (range('A', 'E') as $col) {
    $sheet3->getColumnDimension($col)->setAutoSize(true);
}

// =====================================================
// GERAR ARQUIVO
// =====================================================
$spreadsheet->setActiveSheetIndex(0);

$filename = 'relatorio_' . $mes_ano . '_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Log de geração
Security::logSecurityEvent('excel_generated', [
    'user_id' => $user_id,
    'periodo' => $mes_ano,
    'total_lancamentos' => count($lancamentos)
]);

exit;
?>
