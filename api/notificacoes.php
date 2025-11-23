<?php
session_start();
require_once '../includes/db.php';

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Criar tabela de notificações se não existir
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notificacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        tipo VARCHAR(50) NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        mensagem TEXT NOT NULL,
        lida TINYINT(1) DEFAULT 0,
        data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // Tabela já existe
}

// Ações
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    if($action === 'list') {
        // Listar notificações não lidas
        $stmt = $pdo->prepare("SELECT * FROM notificacoes WHERE id_usuario = ? AND lida = 0 ORDER BY data_criacao DESC LIMIT 10");
        $stmt->execute([$user_id]);
        $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'notificacoes' => $notificacoes]);
        
    } elseif($action === 'count') {
        // Contar notificações não lidas
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notificacoes WHERE id_usuario = ? AND lida = 0");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'count' => $result['total']]);
        
    } elseif($action === 'mark_read') {
        // Marcar como lida
        $id = $_POST['id'] ?? null;
        if($id) {
            $stmt = $pdo->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND id_usuario = ?");
            $stmt->execute([$id, $user_id]);
        }
        
        echo json_encode(['success' => true]);
        
    } elseif($action === 'mark_all_read') {
        // Marcar todas como lidas
        $stmt = $pdo->prepare("UPDATE notificacoes SET lida = 1 WHERE id_usuario = ?");
        $stmt->execute([$user_id]);
        
        echo json_encode(['success' => true]);
        
    } elseif($action === 'generate') {
        // Gerar notificações (executado por cronjob ou manualmente)
        $notifications_created = 0;
        
        // 1. Verificar orçamentos estourados ou próximos do limite
        $current_month = date('Y-m');
        $stmt = $pdo->prepare("
            SELECT 
                o.id,
                o.id_categoria, 
                o.valor_limite, 
                c.nome as categoria_nome,
                COALESCE(SUM(CASE WHEN l.tipo = 'despesa' THEN l.valor ELSE 0 END), 0) as gasto_atual
            FROM orcamentos o
            JOIN categorias c ON o.id_categoria = c.id
            LEFT JOIN lancamentos l ON l.id_categoria = o.id_categoria 
                AND l.id_usuario = o.id_usuario 
                AND DATE_FORMAT(l.data, '%Y-%m') = o.mes_ano
            WHERE o.id_usuario = ? AND o.mes_ano = ?
            GROUP BY o.id, o.id_categoria, o.valor_limite, c.nome
        ");
        $stmt->execute([$user_id, $current_month]);
        $orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($orcamentos as $orc) {
            $progresso = ($orc['valor_limite'] > 0) ? ($orc['gasto_atual'] / $orc['valor_limite']) * 100 : 0;
            
            // Verificar se já existe notificação recente para este orçamento
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificacoes 
                WHERE id_usuario = ? 
                AND tipo = 'orcamento' 
                AND mensagem LIKE ? 
                AND data_criacao > datetime('now', '-1 day')");
            $stmt->execute([$user_id, '%' . $orc['categoria_nome'] . '%']);
            $existing = $stmt->fetchColumn();
            
            if($existing == 0) {
                if($progresso >= 100) {
                    $stmt = $pdo->prepare("INSERT INTO notificacoes (id_usuario, tipo, titulo, mensagem) VALUES (?, 'orcamento', ?, ?)");
                    $stmt->execute([
                        $user_id,
                        'Orçamento Estourado!',
                        'O orçamento da categoria "' . $orc['categoria_nome'] . '" foi ultrapassado (' . number_format($progresso, 1) . '%).'
                    ]);
                    $notifications_created++;
                } elseif($progresso >= 80) {
                    $stmt = $pdo->prepare("INSERT INTO notificacoes (id_usuario, tipo, titulo, mensagem) VALUES (?, 'orcamento', ?, ?)");
                    $stmt->execute([
                        $user_id,
                        'Atenção ao Orçamento',
                        'O orçamento da categoria "' . $orc['categoria_nome'] . '" está em ' . number_format($progresso, 1) . '% de utilização.'
                    ]);
                    $notifications_created++;
                }
            }
        }
        
        // 2. Verificar metas próximas do prazo ou concluídas
        $stmt = $pdo->prepare("SELECT * FROM metas WHERE id_usuario = ? AND status != 'concluida'");
        $stmt->execute([$user_id]);
        $metas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($metas as $meta) {
            $dias_restantes = (strtotime($meta['data_limite']) - time()) / (60 * 60 * 24);
            $progresso = ($meta['valor_meta'] > 0) ? ($meta['valor_atual'] / $meta['valor_meta']) * 100 : 0;
            
            // Meta concluída
            if($progresso >= 100 && $meta['status'] != 'concluida') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificacoes 
                    WHERE id_usuario = ? 
                    AND tipo = 'meta' 
                    AND mensagem LIKE ? 
                    AND data_criacao > datetime('now', '-7 days')");
                $stmt->execute([$user_id, '%' . $meta['descricao'] . '%concluída%']);
                $existing = $stmt->fetchColumn();
                
                if($existing == 0) {
                    $stmt = $pdo->prepare("INSERT INTO notificacoes (id_usuario, tipo, titulo, mensagem) VALUES (?, 'meta', ?, ?)");
                    $stmt->execute([
                        $user_id,
                        'Meta Alcançada! 🎉',
                        'Parabéns! Você concluiu a meta "' . $meta['descricao'] . '".'
                    ]);
                    
                    // Atualizar status da meta
                    $stmt = $pdo->prepare("UPDATE metas SET status = 'concluida' WHERE id = ?");
                    $stmt->execute([$meta['id']]);
                    
                    $notifications_created++;
                }
            }
            // Meta próxima do prazo (7 dias)
            elseif($dias_restantes <= 7 && $dias_restantes > 0 && $progresso < 100) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificacoes 
                    WHERE id_usuario = ? 
                    AND tipo = 'meta' 
                    AND mensagem LIKE ? 
                    AND data_criacao > datetime('now', '-3 days')");
                $stmt->execute([$user_id, '%' . $meta['descricao'] . '%prazo%']);
                $existing = $stmt->fetchColumn();
                
                if($existing == 0) {
                    $stmt = $pdo->prepare("INSERT INTO notificacoes (id_usuario, tipo, titulo, mensagem) VALUES (?, 'meta', ?, ?)");
                    $stmt->execute([
                        $user_id,
                        'Prazo de Meta se Aproxima',
                        'A meta "' . $meta['descricao'] . '" vence em ' . ceil($dias_restantes) . ' dias. Você está em ' . number_format($progresso, 1) . '% do objetivo.'
                    ]);
                    $notifications_created++;
                }
            }
            // Meta atrasada
            elseif($dias_restantes < 0 && $meta['status'] != 'atrasada') {
                $stmt = $pdo->prepare("UPDATE metas SET status = 'atrasada' WHERE id = ?");
                $stmt->execute([$meta['id']]);
                
                $stmt = $pdo->prepare("INSERT INTO notificacoes (id_usuario, tipo, titulo, mensagem) VALUES (?, 'meta', ?, ?)");
                $stmt->execute([
                    $user_id,
                    'Meta Atrasada',
                    'A meta "' . $meta['descricao'] . '" está atrasada. Considere revisar o prazo ou o valor.'
                ]);
                $notifications_created++;
            }
        }
        
        echo json_encode(['success' => true, 'notifications_created' => $notifications_created]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
