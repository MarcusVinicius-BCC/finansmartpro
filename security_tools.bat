@echo off
REM ==========================================
REM COMANDOS RÁPIDOS - FinanSmart Pro (Windows)
REM Segurança e Otimização
REM ==========================================

title FinanSmart Pro - Ferramentas de Segurança
color 0A

:MENU
cls
echo.
echo =========================================
echo   FINANSMART PRO - SCRIPTS DE SEGURANCA
echo =========================================
echo.
echo Escolha uma opcao:
echo.
echo 1. Testar Antivirus
echo 2. Ver Logs de Seguranca
echo 3. Ver Logs de Antivirus
echo 4. Status do Scanner
echo 5. Minificar Assets (CSS/JS)
echo 6. Otimizar Banco de Dados
echo 7. Verificar Permissoes
echo 8. Backup do Banco
echo 9. Limpar Logs Antigos
echo 0. Sair
echo.
echo =========================================
echo.

set /p opcao="Digite a opcao: "

if "%opcao%"=="1" goto TESTAR_ANTIVIRUS
if "%opcao%"=="2" goto LOGS_SEGURANCA
if "%opcao%"=="3" goto LOGS_ANTIVIRUS
if "%opcao%"=="4" goto STATUS_SCANNER
if "%opcao%"=="5" goto MINIFICAR
if "%opcao%"=="6" goto OTIMIZAR_BD
if "%opcao%"=="7" goto VERIFICAR_PERMISSOES
if "%opcao%"=="8" goto BACKUP_BD
if "%opcao%"=="9" goto LIMPAR_LOGS
if "%opcao%"=="0" goto SAIR

echo.
echo Opcao invalida!
pause
goto MENU

:TESTAR_ANTIVIRUS
cls
echo.
echo =========================================
echo   TESTANDO ANTIVIRUS
echo =========================================
echo.
php test_antivirus.php
echo.
pause
goto MENU

:LOGS_SEGURANCA
cls
echo.
echo =========================================
echo   LOGS DE SEGURANCA
echo =========================================
echo.

set "hoje=%date:~-4%-%date:~3,2%-%date:~0,2%"
set "arquivo=logs\security_%hoje%.log"

if exist "%arquivo%" (
    powershell -Command "Get-Content '%arquivo%' | Select-Object -Last 20"
) else (
    echo Nenhum log de seguranca hoje
)

echo.
pause
goto MENU

:LOGS_ANTIVIRUS
cls
echo.
echo =========================================
echo   LOGS DE ANTIVIRUS
echo =========================================
echo.

set "hoje=%date:~-4%-%date:~3,2%-%date:~0,2%"
set "arquivo=logs\antivirus_%hoje%.log"

if exist "%arquivo%" (
    powershell -Command "Get-Content '%arquivo%' | Select-Object -Last 20"
    
    echo.
    echo ESTATISTICAS:
    
    for /f %%a in ('find /c /v "" ^< "%arquivo%"') do set total=%%a
    for /f %%a in ('findstr /c:"\"result\":\"THREAT\"" "%arquivo%" ^| find /c /v ""') do set threats=%%a
    for /f %%a in ('findstr /c:"\"result\":\"CLEAN\"" "%arquivo%" ^| find /c /v ""') do set clean=%%a
    
    echo   Total de scans: %total%
    echo   Arquivos limpos: %clean%
    echo   Ameacas detectadas: %threats%
) else (
    echo Nenhum scan de antivirus hoje
)

echo.
pause
goto MENU

:STATUS_SCANNER
cls
echo.
echo =========================================
echo   STATUS DO SCANNER
echo =========================================
echo.

php -r "require_once 'includes/AntivirusScanner.php'; $status = AntivirusScanner::getScannerStatus(); echo 'Scanner: ' . $status['scanner'] . PHP_EOL; echo 'Disponivel: ' . ($status['available'] ? 'SIM' : 'NAO') . PHP_EOL; echo 'Descricao: ' . $status['description'] . PHP_EOL;"

echo.
pause
goto MENU

:MINIFICAR
cls
echo.
echo =========================================
echo   MINIFICANDO ASSETS
echo =========================================
echo.

php minify_assets.php

echo.
pause
goto MENU

:OTIMIZAR_BD
cls
echo.
echo =========================================
echo   OTIMIZAR BANCO DE DADOS
echo =========================================
echo.

set /p mysql_user="Usuario MySQL [root]: "
if "%mysql_user%"=="" set mysql_user=root

set /p mysql_pass="Senha MySQL: "

set /p db_name="Nome do banco [finansmart]: "
if "%db_name%"=="" set db_name=finansmart

echo.
echo Executando database_indexes.sql...
echo.

mysql -u %mysql_user% -p%mysql_pass% %db_name% < database_indexes.sql

if %errorlevel%==0 (
    echo.
    echo Banco otimizado com sucesso!
) else (
    echo.
    echo Erro ao otimizar banco
)

echo.
pause
goto MENU

:VERIFICAR_PERMISSOES
cls
echo.
echo =========================================
echo   VERIFICAR PERMISSOES
echo =========================================
echo.

echo PASTAS:
if exist uploads\ (echo   uploads\ - OK) else (echo   uploads\ - NAO EXISTE)
if exist cache\ (echo   cache\ - OK) else (echo   cache\ - NAO EXISTE)
if exist logs\ (echo   logs\ - OK) else (echo   logs\ - NAO EXISTE)
if exist backups\ (echo   backups\ - OK) else (echo   backups\ - NAO EXISTE)

echo.
echo ARQUIVO .env:
if exist .env (echo   .env - OK) else (echo   .env - NAO EXISTE)

echo.
echo No Windows, as permissoes sao gerenciadas via Propriedades do arquivo
echo Clique direito no arquivo/pasta e va em Seguranca

echo.
pause
goto MENU

:BACKUP_BD
cls
echo.
echo =========================================
echo   BACKUP DO BANCO DE DADOS
echo =========================================
echo.

set /p mysql_user="Usuario MySQL [root]: "
if "%mysql_user%"=="" set mysql_user=root

set /p mysql_pass="Senha MySQL: "

set /p db_name="Nome do banco [finansmart]: "
if "%db_name%"=="" set db_name=finansmart

if not exist backups mkdir backups

set "timestamp=%date:~-4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%%time:~6,2%"
set "timestamp=%timestamp: =0%"
set "backup_file=backups\backup_%db_name%_%timestamp%.sql"

echo.
echo Criando backup em: %backup_file%
echo.

mysqldump -u %mysql_user% -p%mysql_pass% %db_name% > "%backup_file%"

if %errorlevel%==0 (
    echo.
    echo Backup criado com sucesso!
    dir "%backup_file%"
) else (
    echo.
    echo Erro ao criar backup
)

echo.
pause
goto MENU

:LIMPAR_LOGS
cls
echo.
echo =========================================
echo   LIMPAR LOGS ANTIGOS
echo =========================================
echo.

set /p dias="Deletar logs com mais de quantos dias? [30]: "
if "%dias%"=="" set dias=30

echo.
echo Procurando logs com mais de %dias% dias...
echo.

forfiles /P logs /S /M *.log /D -%dias% /C "cmd /c echo @path" 2>nul

echo.
set /p confirma="Deseja deletar esses arquivos? (S/N): "

if /i "%confirma%"=="S" (
    forfiles /P logs /S /M *.log /D -%dias% /C "cmd /c del @path" 2>nul
    echo.
    echo Arquivos deletados
) else (
    echo.
    echo Operacao cancelada
)

echo.
pause
goto MENU

:SAIR
cls
echo.
echo Ate logo!
timeout /t 2 >nul
exit

REM ==========================================
REM Fim do script
REM ==========================================
