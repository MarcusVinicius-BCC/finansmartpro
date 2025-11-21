<?php
require 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch categories for the current user
$stmt = $pdo->prepare('SELECT * FROM categorias WHERE id_usuario = ? ORDER BY tipo DESC, nome ASC');
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll();

require 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6>Gerenciar Categorias</h6>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus me-2"></i>Adicionar Categoria
                    </button>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nome</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tipo</th>
                                    <th class="text-secondary opacity-7"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Nenhuma categoria encontrada.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?= htmlspecialchars($category['nome']) ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">
                                                    <?= $category['tipo'] == 'receita' ? 'Receita' : 'Despesa' ?>
                                                </p>
                                            </td>
                                            <td class="align-middle">
                                                <button class="btn btn-link text-secondary mb-0 ps-0 edit-category-btn"
                                                        data-id="<?= $category['id'] ?>"
                                                        data-nome="<?= htmlspecialchars($category['nome']) ?>"
                                                        data-tipo="<?= $category['tipo'] ?>">
                                                    <i class="fas fa-edit text-info"></i>
                                                </button>
                                                <button class="btn btn-link text-secondary mb-0 ps-0 delete-category-btn"
                                                        data-id="<?= $category['id'] ?>">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Add/Edit Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Adicionar Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="categoryId">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Nome da Categoria</label>
                        <input type="text" class="form-control" id="categoryName" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoryType" class="form-label">Tipo</label>
                        <select class="form-select" id="categoryType" name="tipo" required>
                            <option value="receita">Receita</option>
                            <option value="despesa">Despesa</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addCategoryModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
    const categoryForm = document.getElementById('categoryForm');
    const categoryIdInput = document.getElementById('categoryId');
    const categoryNameInput = document.getElementById('categoryName');
    const categoryTypeSelect = document.getElementById('categoryType');
    const modalTitle = document.getElementById('addCategoryModalLabel');

    // Open Add Modal
    document.querySelector('[data-bs-target="#addCategoryModal"]').addEventListener('click', function() {
        modalTitle.textContent = 'Adicionar Categoria';
        categoryIdInput.value = '';
        categoryNameInput.value = '';
        categoryTypeSelect.value = 'despesa'; // Default to despesa
    });

    // Open Edit Modal
    document.querySelectorAll('.edit-category-btn').forEach(button => {
        button.addEventListener('click', function() {
            modalTitle.textContent = 'Editar Categoria';
            categoryIdInput.value = this.dataset.id;
            categoryNameInput.value = this.dataset.nome;
            categoryTypeSelect.value = this.dataset.tipo;
            addCategoryModal.show();
        });
    });

    // Handle Form Submission (Add/Edit)
    categoryForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(categoryForm);
        const id = formData.get('id');
        const method = id ? 'PUT' : 'POST';
        const url = 'api/categorias.php' + (id ? `?id=${id}` : '');

        try {
            const response = await fetch(url, {
                method: 'POST', // Always POST for form submission, handle PUT/DELETE via hidden field or query param
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                alert(result.message);
                addCategoryModal.hide();
                location.reload(); // Reload page to show updated list
            } else {
                alert('Erro: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Ocorreu um erro ao salvar a categoria.');
        }
    });

    // Handle Delete
    document.querySelectorAll('.delete-category-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const categoryId = this.dataset.id;
            if (confirm('Tem certeza que deseja excluir esta categoria?')) {
                try {
                    const response = await fetch(`api/categorias.php?id=${categoryId}`, {
                        method: 'DELETE'
                    });
                    const result = await response.json();

                    if (result.success) {
                        alert(result.message);
                        location.reload();
                    } else {
                        alert('Erro: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Ocorreu um erro ao excluir a categoria.');
                }
            }
        });
    });
});
</script>
