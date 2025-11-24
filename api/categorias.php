<?php
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Add or Update a category
        $id = $_POST['id'] ?? null;
        $nome = trim($_POST['nome'] ?? '');
        $tipo = $_POST['tipo'] ?? '';

        if (empty($nome) || empty($tipo)) {
            echo json_encode(['success' => false, 'message' => 'Nome e tipo são obrigatórios.']);
            exit;
        }

        if (!in_array($tipo, ['receita', 'despesa'])) {
            echo json_encode(['success' => false, 'message' => 'Tipo inválido.']);
            exit;
        }

        if ($id) {
            // Update existing category
            $stmt = $pdo->prepare('UPDATE categorias SET nome = ?, tipo = ? WHERE id = ? AND id_usuario = ?');
            $success = $stmt->execute([$nome, $tipo, $id, $user_id]);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Categoria atualizada com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar categoria.']);
            }
        } else {
            // Add new category
            $stmt = $pdo->prepare('INSERT INTO categorias (id_usuario, nome, tipo) VALUES (?, ?, ?)');
            $success = $stmt->execute([$user_id, $nome, $tipo]);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Categoria adicionada com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao adicionar categoria.']);
            }
        }
        break;

    case 'DELETE':
        // Delete a category
        $id = $_GET['id'] ?? null;

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID da categoria é obrigatório.']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM categorias WHERE id = ? AND id_usuario = ?');
        $success = $stmt->execute([$id, $user_id]);
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Categoria excluída com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir categoria.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
        break;
}
?>