@echo off
REM ==========================================
REM Script de Preparacao para Producao
REM FinanSmart Pro
REM ==========================================

echo.
echo =========================================
echo   PREPARANDO PARA PRODUCAO
echo =========================================
echo.

REM 1. Criar pasta de producao
echo 1. Criando pasta de producao...
if not exist "..\finansmart_producao" mkdir "..\finansmart_producao"

REM 2. Copiar arquivos (exceto .git, node_modules, etc)
echo 2. Copiando arquivos...
xcopy /E /I /Y /EXCLUDE:exclude.txt * "..\finansmart_producao\"

REM 3. Avisos importantes
echo.
echo =========================================
echo   AVISOS IMPORTANTES
echo =========================================
echo.
echo ANTES DE FAZER UPLOAD:
echo.
echo 1. Edite .env em finansmart_producao:
echo    - Altere DB_PASS para senha forte
echo    - Altere APP_ENV para 'production'
echo    - Altere APP_DEBUG para 'false'
echo    - Altere APP_URL para seu dominio
echo    - Configure SMTP real
echo.
echo 2. Verifique que .htaccess esta presente
echo.
echo 3. Nao envie a pasta .git
echo.
echo 4. Configure permissoes no servidor:
echo    - Pastas: 755
echo    - Arquivos: 644
echo    - .env: 600
echo    - uploads/, cache/, logs/: 775
echo.
echo =========================================
echo.

pause
