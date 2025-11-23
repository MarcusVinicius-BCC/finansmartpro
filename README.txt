═══════════════════════════════════════════════════════════════
                    FINANSMART PRO
         Sistema Completo de Gestão Financeira Pessoal
═══════════════════════════════════════════════════════════════

Autor: Marcus Vinicius Campos da Silva
Ano: 2025
Versão: 2.0

═══════════════════════════════════════════════════════════════
                 REQUISITOS DO SISTEMA
═══════════════════════════════════════════════════════════════

✓ XAMPP 8.2 ou superior (Apache + MySQL/MariaDB + PHP 8.2+)
✓ Composer (gerenciador de dependências PHP)
✓ Navegador web moderno (Chrome, Firefox, Edge)
✓ Conexão com internet (para conversão de moedas)
✓ Mínimo 100MB de espaço em disco

═══════════════════════════════════════════════════════════════
              PASSO A PASSO - INSTALAÇÃO COMPLETA
═══════════════════════════════════════════════════════════════

┌─────────────────────────────────────────────────────────────┐
│ ETAPA 1: INSTALAR XAMPP                                     │
└─────────────────────────────────────────────────────────────┘

1. Baixe o XAMPP em: https://www.apachefriends.org/
2. Instale na pasta padrão: C:\xampp
3. Abra o XAMPP Control Panel
4. Inicie os serviços: Apache e MySQL


┌─────────────────────────────────────────────────────────────┐
│ ETAPA 2: INSTALAR COMPOSER                                  │
└─────────────────────────────────────────────────────────────┘

1. Baixe o Composer em: https://getcomposer.org/download/
2. Execute o instalador Composer-Setup.exe
3. Durante a instalação, aponte para: C:\xampp\php\php.exe
4. Conclua a instalação
5. Teste no CMD/PowerShell: composer --version


┌─────────────────────────────────────────────────────────────┐
│ ETAPA 3: CONFIGURAR PHP.INI                                 │
└─────────────────────────────────────────────────────────────┘

1. Abra o arquivo: C:\xampp\php\php.ini
2. Procure e DESCOMENTE (remova o ponto e vírgula) as linhas:

   extension=curl
   extension=fileinfo
   extension=gd
   extension=mbstring
   extension=pdo_mysql

   (Descomente removendo o ";" do início da linha)

3. Procure por "upload_max_filesize" e ajuste para:

   upload_max_filesize = 10M
   post_max_size = 10M

4. Salve o arquivo php.ini
5. Reinicie o Apache no XAMPP Control Panel


┌─────────────────────────────────────────────────────────────┐
│ ETAPA 4: EXTRAIR O PROJETO                                  │
└─────────────────────────────────────────────────────────────┘

1. Extraia a pasta 'finansmartpro' para: C:\xampp\htdocs\
2. Caminho final deve ser: C:\xampp\htdocs\finansmartpro\


┌─────────────────────────────────────────────────────────────┐
│ ETAPA 5: INSTALAR DEPENDÊNCIAS COM COMPOSER                 │
└─────────────────────────────────────────────────────────────┘

1. Abra o CMD ou PowerShell
2. Navegue até a pasta do projeto:
   
   cd C:\xampp\htdocs\finansmartpro

3. Execute o comando:
   
   composer install

4. Aguarde o download de todas as dependências (FPDF, PHPMailer, etc.)
5. Será criada automaticamente a pasta 'vendor' com todas as bibliotecas


┌─────────────────────────────────────────────────────────────┐
│ ETAPA 6: CRIAR ESTRUTURA DE PASTAS                          │
└─────────────────────────────────────────────────────────────┘

Certifique-se de que existem as seguintes pastas:

✓ cache/          (para cache de taxas de câmbio)
✓ vendor/         (criada automaticamente pelo Composer)
✓ assets/img/     (imagens do sistema)
✓ pdf/            (geração de relatórios PDF)

Se a pasta 'cache' não existir, crie manualmente:
- Crie a pasta: C:\xampp\htdocs\finansmartpro\cache


┌─────────────────────────────────────────────────────────────┐
│ ETAPA 7: CONFIGURAR BANCO DE DADOS                          │
└─────────────────────────────────────────────────────────────┘

1. Acesse o phpMyAdmin: http://localhost/phpmyadmin

2. Vá em "SQL" e execute o arquivo database.sql completo
   (Copie todo o conteúdo de database.sql e cole na aba SQL)

3. O script irá:
   - Criar o banco 'finansmart'
   - Criar 20+ tabelas do sistema
   - Inserir categorias padrão
   - Criar usuário de teste

4. Verifique se o banco 'finansmart' foi criado com sucesso


┌─────────────────────────────────────────────────────────────┐
│ ETAPA 8: CONFIGURAR CREDENCIAIS DO BANCO                    │
└─────────────────────────────────────────────────────────────┘

1. Abra o arquivo: includes/db.php

2. Verifique as credenciais (padrão XAMPP):
   
   $host = 'localhost';
   $dbname = 'finansmart';
   $username = 'root';
   $password = 'mv16082005';  // <-- ALTERE SE NECESSÁRIO

3. Se sua senha do MySQL for diferente, altere aqui
4. Salve o arquivo


┌─────────────────────────────────────────────────────────────┐
│ ETAPA 9: TESTAR O SISTEMA                                   │
└─────────────────────────────────────────────────────────────┘

1. Abra seu navegador

2. Acesse: http://localhost/finansmartpro

3. Faça login com as credenciais de teste:
   
   Email: admin@gmail.com
   Senha: 123456

4. Explore os 15 módulos do sistema!


═══════════════════════════════════════════════════════════════
                   MÓDULOS DO SISTEMA
═══════════════════════════════════════════════════════════════

 1. Dashboard        - Visão geral financeira com gráficos
 2. Lançamentos      - Receitas e despesas
 3. Orçamentos       - Planejamento por categoria
 4. Metas            - Objetivos financeiros
 5. Relatórios       - Exportação PDF/CSV
 6. Investimentos    - Controle de carteira
 7. Categorias       - Personalização com ícones e cores
 8. Cartões          - Gestão de cartões de crédito
 9. Contas           - Contas bancárias e transferências
10. Analytics        - Análises avançadas e previsões
11. Recorrentes      - Lançamentos automáticos
12. Lembretes        - Notificações e alertas
13. Planejamento     - Cenários "E se?" e aposentadoria
14. Importar         - Upload de extratos OFX/CSV
15. Pagar/Receber    - Contas a pagar e receber


═══════════════════════════════════════════════════════════════
                  FUNCIONALIDADES PRINCIPAIS
═══════════════════════════════════════════════════════════════

✓ Multi-moeda (BRL, USD, EUR, GBP, JPY, ARS, CLP)
✓ Conversão automática de moedas (API externa)
✓ Gráficos interativos (Chart.js)
✓ Exportação PDF com FPDF
✓ Importação de extratos bancários (OFX/CSV)
✓ Sistema de alertas e notificações
✓ Simulador de aposentadoria
✓ Detecção de despesas recorrentes
✓ Análise de sazonalidade
✓ Contas a pagar/receber com vencimentos
✓ Categorias personalizadas com 12 ícones
✓ Gestão completa de cartões de crédito
✓ Transferências entre contas
✓ Metas com acompanhamento visual


═══════════════════════════════════════════════════════════════
              SOLUÇÃO DE PROBLEMAS COMUNS
═══════════════════════════════════════════════════════════════

❌ ERRO: "Could not find driver"
✓ SOLUÇÃO: Ative extension=pdo_mysql no php.ini e reinicie Apache

❌ ERRO: "Access denied for user 'root'"
✓ SOLUÇÃO: Verifique a senha em includes/db.php

❌ ERRO: "Call to undefined function curl_init"
✓ SOLUÇÃO: Ative extension=curl no php.ini e reinicie Apache

❌ ERRO: "Class 'FPDF' not found"
✓ SOLUÇÃO: Execute 'composer install' na pasta do projeto

❌ ERRO: Página em branco ao acessar
✓ SOLUÇÃO: Verifique se Apache e MySQL estão rodando no XAMPP

❌ ERRO: Upload de arquivo não funciona
✓ SOLUÇÃO: Aumente upload_max_filesize no php.ini para 10M

❌ ERRO: Taxas de câmbio não atualizam
✓ SOLUÇÃO: Verifique conexão com internet e pasta cache/ com permissão


═══════════════════════════════════════════════════════════════
                    ESTRUTURA DE ARQUIVOS
═══════════════════════════════════════════════════════════════

finansmartpro/
├── index.php                    # Landing page
├── login.php                    # Autenticação
├── dashboard.php                # Painel principal
├── lancamentos.php              # Lançamentos
├── orcamento.php                # Orçamentos
├── metas.php                    # Metas financeiras
├── relatorios.php               # Relatórios
├── investimentos.php            # Investimentos
├── categorias.php               # Categorias
├── cartoes.php                  # Cartões
├── contas.php                   # Contas bancárias
├── analytics.php                # Analytics avançado
├── recorrentes.php              # Lançamentos recorrentes
├── lembretes.php                # Alertas
├── planejamento.php             # Planejamento financeiro
├── importar.php                 # Importação extratos
├── contas_pagar_receber.php     # Contas a pagar/receber
├── database.sql                 # Estrutura do banco
├── composer.json                # Dependências PHP
│
├── includes/
│   ├── db.php                   # Conexão com banco
│   ├── header.php               # Cabeçalho + Sidebar
│   ├── footer.php               # Rodapé
│   └── currency.php             # Conversão de moedas
│
├── api/
│   ├── conversao.php            # API de conversão
│   ├── dashboard_summary.php   # Resumo dashboard
│   ├── get_lembretes.php        # Notificações
│   ├── categorias.php           # CRUD categorias
│   └── notificacoes.php         # Sistema de alertas
│
├── assets/
│   ├── css/                     # Estilos
│   ├── js/                      # JavaScript
│   └── img/                     # Imagens
│
├── cache/                       # Cache de taxas
├── pdf/                         # Geração de PDFs
└── vendor/                      # Dependências (Composer)


═══════════════════════════════════════════════════════════════
                     TECNOLOGIAS UTILIZADAS
═══════════════════════════════════════════════════════════════

Backend:
• PHP 8.2+
• MySQL/MariaDB
• PDO (Database)
• Composer

Frontend:
• HTML5 + CSS3
• Bootstrap 5.3.0
• JavaScript (ES6+)
• Chart.js 4.4.0
• Font Awesome 6.4.0

Bibliotecas PHP:
• FPDF (geração de PDF)
• PHPMailer (emails)
• PHPUnit (testes)

APIs Externas:
• ExchangeRate-API (conversão de moedas)


═══════════════════════════════════════════════════════════════
                      CREDENCIAIS PADRÃO
═══════════════════════════════════════════════════════════════

Email: admin@gmail.com
Senha: 123456

⚠️ IMPORTANTE: Altere a senha após primeiro login!


═══════════════════════════════════════════════════════════════
                         SUPORTE
═══════════════════════════════════════════════════════════════

Para dúvidas ou problemas:
• Email: marcus.vinicius@email.com
• GitHub: MarcusVinicius-BCC

═══════════════════════════════════════════════════════════════
                          LICENÇA
═══════════════════════════════════════════════════════════════

© 2025 Marcus Vinicius Campos da Silva
Todos os direitos reservados.

Projeto desenvolvido para fins educacionais e acadêmicos.

═══════════════════════════════════════════════════════════════
