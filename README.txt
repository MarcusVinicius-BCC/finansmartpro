FinanSmart Pro 

Autor: Marcus Vinicius Campos da Silva
Ano: 2025

Instruções rápidas:
1. Extraia a pasta 'finansmart_pro' para C:\xampp\htdocs\ (ou /var/www/htdocs).
2. No XAMPP, inicie Apache e MySQL.
3. Importar database.sql via phpMyAdmin.
4. Abra http://localhost/finansmart_pro
5. Para PDFs, baixar FPDF em https://www.fpdf.org/ e colocar em vendor/fpdf/fpdf.php (ou usar composer).

Observações:
- As taxas de câmbio são cacheadas em cache/rates.json.

Para rodar o projeto Finansmart por completo, você precisará do seguinte:

1. Ambiente de Servidor:

XAMPP: Como seus arquivos estão na pasta htdocs, o ideal é ter o XAMPP instalado. Ele já vem com o servidor web Apache, o PHP e o banco de dados MariaDB (compatível com MySQL).
2. Banco de Dados:

Servidor MySQL/MariaDB: Precisa estar rodando (o do XAMPP serve).
Banco de Dados: Um banco de dados com o nome finansmart deve ser criado.
Tabelas: O arquivo database.sql na raiz do projeto contém a estrutura das tabelas. Você precisa importar este arquivo para dentro do banco finansmart para criar as tabelas necessárias.
Credenciais: O arquivo includes/db.php está configurado para usar o usuário root e a senha mv16082005. Se a senha do seu banco de dados for diferente, você precisará ajustá-la neste arquivo.
3. Configuração do PHP:

Extensão pdo_mysql: Essencial para a conexão com o banco de dados. Geralmente já vem ativada no XAMPP.
Extensão curl: Necessária para a funcionalidade de conversão de moeda (api/conversao.php), pois ela busca as taxas de câmbio em uma API externa.
4. Estrutura de Pastas:

Criar a pasta cache: Você precisa criar uma pasta chamada cache na raiz do seu projeto (c:\xampp\htdocs\finansmart\cache). O sistema a utiliza para armazenar temporariamente as taxas de câmbio.
5. Conexão com a Internet:

É necessário que o servidor tenha acesso à internet para que a consulta das taxas de câmbio funcione.
Resumindo: instale o XAMPP, inicie o Apache e o MySQL, crie o banco finansmart, importe o database.sql, crie a pasta cache e, se necessário, ajuste a senha do banco no arquivo includes/db.php.

usuário cadastrado: admin@gmail.com
senha cadastrada: 123456
