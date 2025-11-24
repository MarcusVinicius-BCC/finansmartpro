#!/bin/bash
# ==========================================
# COMANDOS RÁPIDOS - FinanSmart Pro
# Segurança e Otimização
# ==========================================

echo "🛡️ FINANSMART PRO - SCRIPTS DE SEGURANÇA"
echo "========================================"
echo ""

# Menu
echo "Escolha uma opção:"
echo ""
echo "1. 🧪 Testar Antivírus"
echo "2. 📊 Ver Logs de Segurança"
echo "3. 📊 Ver Logs de Antivírus"
echo "4. 🔍 Status do Scanner"
echo "5. 🗜️ Minificar Assets (CSS/JS)"
echo "6. 🗄️ Otimizar Banco de Dados"
echo "7. 🔒 Verificar Permissões"
echo "8. 💾 Backup do Banco"
echo "9. 🧹 Limpar Logs Antigos"
echo "0. ❌ Sair"
echo ""

read -p "Digite a opção: " opcao

case $opcao in
    1)
        echo ""
        echo "🧪 TESTANDO ANTIVÍRUS..."
        echo "========================"
        php test_antivirus.php
        ;;
    
    2)
        echo ""
        echo "📊 LOGS DE SEGURANÇA (últimas 20 linhas)"
        echo "========================================"
        if [ -f "logs/security_$(date +%Y-%m-%d).log" ]; then
            tail -n 20 logs/security_$(date +%Y-%m-%d).log | while IFS= read -r line; do
                echo "$line" | python3 -m json.tool 2>/dev/null || echo "$line"
            done
        else
            echo "❌ Nenhum log de segurança hoje"
        fi
        ;;
    
    3)
        echo ""
        echo "📊 LOGS DE ANTIVÍRUS (últimas 20 linhas)"
        echo "========================================"
        if [ -f "logs/antivirus_$(date +%Y-%m-%d).log" ]; then
            tail -n 20 logs/antivirus_$(date +%Y-%m-%d).log | while IFS= read -r line; do
                echo "$line" | python3 -m json.tool 2>/dev/null || echo "$line"
            done
            
            echo ""
            echo "📈 ESTATÍSTICAS:"
            total=$(wc -l < "logs/antivirus_$(date +%Y-%m-%d).log")
            threats=$(grep -c '"result":"THREAT"' "logs/antivirus_$(date +%Y-%m-%d).log" 2>/dev/null || echo 0)
            clean=$(grep -c '"result":"CLEAN"' "logs/antivirus_$(date +%Y-%m-%d).log" 2>/dev/null || echo 0)
            
            echo "  Total de scans: $total"
            echo "  Arquivos limpos: $clean"
            echo "  Ameaças detectadas: $threats"
        else
            echo "❌ Nenhum scan de antivírus hoje"
        fi
        ;;
    
    4)
        echo ""
        echo "🔍 STATUS DO SCANNER"
        echo "===================="
        php -r "
        require_once 'includes/AntivirusScanner.php';
        \$status = AntivirusScanner::getScannerStatus();
        echo 'Scanner: ' . \$status['scanner'] . PHP_EOL;
        echo 'Disponível: ' . (\$status['available'] ? '✅ SIM' : '⚠️  NÃO') . PHP_EOL;
        echo 'Descrição: ' . \$status['description'] . PHP_EOL;
        "
        ;;
    
    5)
        echo ""
        echo "🗜️ MINIFICANDO ASSETS..."
        echo "======================="
        php minify_assets.php
        ;;
    
    6)
        echo ""
        echo "🗄️ OTIMIZANDO BANCO DE DADOS..."
        echo "==============================="
        read -p "Usuário MySQL [root]: " mysql_user
        mysql_user=${mysql_user:-root}
        
        read -sp "Senha MySQL: " mysql_pass
        echo ""
        
        read -p "Nome do banco [finansmart]: " db_name
        db_name=${db_name:-finansmart}
        
        echo ""
        echo "Executando database_indexes.sql..."
        mysql -u "$mysql_user" -p"$mysql_pass" "$db_name" < database_indexes.sql
        
        if [ $? -eq 0 ]; then
            echo "✅ Banco otimizado com sucesso!"
        else
            echo "❌ Erro ao otimizar banco"
        fi
        ;;
    
    7)
        echo ""
        echo "🔒 VERIFICANDO PERMISSÕES..."
        echo "============================"
        echo ""
        
        echo "📁 Pastas:"
        ls -ld uploads/ cache/ logs/ backups/ 2>/dev/null || echo "Algumas pastas não existem"
        
        echo ""
        echo "📄 Arquivo .env:"
        ls -l .env 2>/dev/null || echo ".env não encontrado"
        
        echo ""
        echo "💡 PERMISSÕES RECOMENDADAS:"
        echo "  Pastas (uploads, cache, logs): 755"
        echo "  Arquivos PHP: 644"
        echo "  .env: 600"
        echo ""
        
        read -p "Deseja corrigir permissões? (s/N): " corrigir
        
        if [ "$corrigir" = "s" ] || [ "$corrigir" = "S" ]; then
            find . -type d -exec chmod 755 {} \; 2>/dev/null
            find . -type f -name "*.php" -exec chmod 644 {} \; 2>/dev/null
            chmod 600 .env 2>/dev/null
            chmod 775 uploads/ cache/ logs/ backups/ 2>/dev/null
            echo "✅ Permissões corrigidas!"
        fi
        ;;
    
    8)
        echo ""
        echo "💾 BACKUP DO BANCO DE DADOS"
        echo "============================"
        
        read -p "Usuário MySQL [root]: " mysql_user
        mysql_user=${mysql_user:-root}
        
        read -sp "Senha MySQL: " mysql_pass
        echo ""
        
        read -p "Nome do banco [finansmart]: " db_name
        db_name=${db_name:-finansmart}
        
        backup_file="backups/backup_${db_name}_$(date +%Y-%m-%d_%H-%M-%S).sql"
        
        echo ""
        echo "Criando backup em: $backup_file"
        
        mysqldump -u "$mysql_user" -p"$mysql_pass" "$db_name" > "$backup_file"
        
        if [ $? -eq 0 ]; then
            size=$(du -h "$backup_file" | cut -f1)
            echo "✅ Backup criado com sucesso! ($size)"
        else
            echo "❌ Erro ao criar backup"
        fi
        ;;
    
    9)
        echo ""
        echo "🧹 LIMPANDO LOGS ANTIGOS..."
        echo "============================"
        echo ""
        
        read -p "Deletar logs com mais de quantos dias? [30]: " dias
        dias=${dias:-30}
        
        echo "Procurando logs com mais de $dias dias..."
        
        find logs/ -name "*.log" -type f -mtime +$dias -print
        
        echo ""
        read -p "Deseja deletar esses arquivos? (s/N): " confirma
        
        if [ "$confirma" = "s" ] || [ "$confirma" = "S" ]; then
            deletados=$(find logs/ -name "*.log" -type f -mtime +$dias -delete -print | wc -l)
            echo "✅ $deletados arquivos deletados"
        else
            echo "❌ Operação cancelada"
        fi
        ;;
    
    0)
        echo "👋 Até logo!"
        exit 0
        ;;
    
    *)
        echo "❌ Opção inválida"
        ;;
esac

echo ""
echo "=========================================="
echo "Pressione Enter para sair..."
read
