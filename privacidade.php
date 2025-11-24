<?php
$page_title = 'Política de Privacidade';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $page_title ?> - FinanSmart Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h1 class="mb-4"><?= $page_title ?></h1>
                        <p class="text-muted"><small>Última atualização: <?= date('d/m/Y') ?></small></p>
                        
                        <hr>
                        
                        <h3>1. Informações que Coletamos</h3>
                        <p>O FinanSmart Pro coleta e processa as seguintes informações:</p>
                        <ul>
                            <li><strong>Dados de Cadastro:</strong> Nome, email e senha (criptografada)</li>
                            <li><strong>Dados Financeiros:</strong> Lançamentos, categorias, contas bancárias, investimentos, metas</li>
                            <li><strong>Arquivos:</strong> Comprovantes e notas fiscais que você anexar</li>
                            <li><strong>Dados de Uso:</strong> Logs de acesso, endereço IP, navegador utilizado</li>
                        </ul>
                        
                        <h3>2. Como Usamos Suas Informações</h3>
                        <p>Utilizamos seus dados para:</p>
                        <ul>
                            <li>Fornecer e melhorar os serviços do sistema</li>
                            <li>Processar e exibir suas informações financeiras</li>
                            <li>Enviar notificações e lembretes (se habilitados)</li>
                            <li>Garantir a segurança da sua conta</li>
                            <li>Cumprir obrigações legais</li>
                        </ul>
                        
                        <h3>3. Armazenamento e Segurança</h3>
                        <p>Seus dados são:</p>
                        <ul>
                            <li>Armazenados em servidores seguros com criptografia</li>
                            <li>Protegidos por senhas hash (não armazenamos senhas em texto plano)</li>
                            <li>Acessíveis apenas por você através de login autenticado</li>
                            <li>Mantidos enquanto sua conta estiver ativa</li>
                        </ul>
                        
                        <h3>4. Compartilhamento de Dados</h3>
                        <p><strong>NÃO compartilhamos seus dados</strong> com terceiros, exceto:</p>
                        <ul>
                            <li>Quando exigido por lei ou ordem judicial</li>
                            <li>Com membros da família que você convidar (módulo familiar)</li>
                            <li>Para prevenir fraudes ou proteger segurança</li>
                        </ul>
                        
                        <h3>5. Seus Direitos (LGPD)</h3>
                        <p>Você tem direito a:</p>
                        <ul>
                            <li><strong>Acesso:</strong> Visualizar todos os seus dados</li>
                            <li><strong>Correção:</strong> Editar informações incorretas</li>
                            <li><strong>Exclusão:</strong> Deletar sua conta e todos os dados</li>
                            <li><strong>Portabilidade:</strong> Exportar seus dados (backup)</li>
                            <li><strong>Revogação:</strong> Revogar consentimento a qualquer momento</li>
                        </ul>
                        
                        <h3>6. Cookies e Tecnologias</h3>
                        <p>Utilizamos cookies para:</p>
                        <ul>
                            <li>Manter você logado (sessão)</li>
                            <li>Lembrar preferências (moeda, idioma)</li>
                            <li>Proteger contra ataques CSRF</li>
                        </ul>
                        
                        <h3>7. Retenção de Dados</h3>
                        <p>Mantemos seus dados enquanto sua conta estiver ativa. Após exclusão da conta:</p>
                        <ul>
                            <li>Dados são permanentemente deletados em até 30 dias</li>
                            <li>Backups são mantidos por 90 dias para recuperação</li>
                            <li>Logs de segurança são mantidos por 1 ano</li>
                        </ul>
                        
                        <h3>8. Menores de Idade</h3>
                        <p>O FinanSmart Pro não é destinado a menores de 18 anos. Não coletamos intencionalmente dados de menores.</p>
                        
                        <h3>9. Alterações nesta Política</h3>
                        <p>Podemos atualizar esta política periodicamente. Alterações significativas serão notificadas por email.</p>
                        
                        <h3>10. Contato</h3>
                        <p>Para exercer seus direitos ou tirar dúvidas:</p>
                        <ul>
                            <li>Email: privacidade@finansmart.com</li>
                            <li>Dentro do sistema: Configurações → Privacidade</li>
                        </ul>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <a href="index.php" class="btn btn-primary">Voltar ao Início</a>
                            <a href="termos.php" class="btn btn-outline-secondary">Ver Termos de Uso</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
