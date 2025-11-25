-- ==================================================
-- FinanSmart Pro - Índices e Otimizações de Banco
-- VERSÃO SEGURA (ignora erros de duplicação)
-- ==================================================

USE finansmart;

-- Criar índices (ignora se já existir)
-- ==================================================

-- usuarios
CREATE INDEX IF NOT EXISTS idx_email ON usuarios(email);
CREATE INDEX IF NOT EXISTS idx_moeda_base ON usuarios(moeda_base);

-- lancamentos
CREATE INDEX IF NOT EXISTS idx_usuario_data ON lancamentos(id_usuario, data);
CREATE INDEX IF NOT EXISTS idx_categoria ON lancamentos(id_categoria);
CREATE INDEX IF NOT EXISTS idx_tipo ON lancamentos(tipo);
CREATE INDEX IF NOT EXISTS idx_status ON lancamentos(status);
CREATE INDEX IF NOT EXISTS idx_data_vencimento ON lancamentos(data_vencimento);
CREATE INDEX IF NOT EXISTS idx_moeda ON lancamentos(moeda);
CREATE INDEX IF NOT EXISTS idx_usuario_tipo_data ON lancamentos(id_usuario, tipo, data);

-- categorias
CREATE INDEX IF NOT EXISTS idx_usuario_tipo ON categorias(id_usuario, tipo);
CREATE INDEX IF NOT EXISTS idx_tipo ON categorias(tipo);

-- metas
CREATE INDEX IF NOT EXISTS idx_usuario_status ON metas(id_usuario, status);
CREATE INDEX IF NOT EXISTS idx_data_limite ON metas(data_limite);
CREATE INDEX IF NOT EXISTS idx_categoria ON metas(categoria);

-- investimentos
CREATE INDEX IF NOT EXISTS idx_usuario_tipo ON investimentos(id_usuario, tipo);
CREATE INDEX IF NOT EXISTS idx_data_inicio ON investimentos(data_inicio);
CREATE INDEX IF NOT EXISTS idx_status ON investimentos(status);

-- orcamentos
CREATE INDEX IF NOT EXISTS idx_usuario_mes ON orcamentos(id_usuario, mes, ano);
CREATE INDEX IF NOT EXISTS idx_categoria ON orcamentos(id_categoria);

-- contas_bancarias
CREATE INDEX IF NOT EXISTS idx_usuario ON contas_bancarias(id_usuario);
CREATE INDEX IF NOT EXISTS idx_ativa ON contas_bancarias(ativa);

-- cartoes
CREATE INDEX IF NOT EXISTS idx_usuario ON cartoes(id_usuario);
CREATE INDEX IF NOT EXISTS idx_ativo ON cartoes(ativo);

-- lembretes
CREATE INDEX IF NOT EXISTS idx_usuario_lido ON lembretes(id_usuario, lido);
CREATE INDEX IF NOT EXISTS idx_data ON lembretes(data);

-- recorrencias
CREATE INDEX IF NOT EXISTS idx_usuario_ativa ON recorrencias(id_usuario, ativa);
CREATE INDEX IF NOT EXISTS idx_proxima_execucao ON recorrencias(proxima_execucao);

-- familia
CREATE INDEX IF NOT EXISTS idx_criador ON familia(id_criador);
CREATE INDEX IF NOT EXISTS idx_codigo ON familia(codigo_convite);

-- familia_membros
CREATE INDEX IF NOT EXISTS idx_familia ON familia_membros(id_familia);
CREATE INDEX IF NOT EXISTS idx_usuario ON familia_membros(id_usuario);
CREATE INDEX IF NOT EXISTS idx_status ON familia_membros(status);

-- anexos
CREATE INDEX IF NOT EXISTS idx_usuario ON anexos(id_usuario);
CREATE INDEX IF NOT EXISTS idx_tipo ON anexos(tipo_arquivo);

-- anexos_lancamentos
CREATE INDEX IF NOT EXISTS idx_lancamento ON anexos_lancamentos(id_lancamento);
CREATE INDEX IF NOT EXISTS idx_anexo ON anexos_lancamentos(id);

-- notificacoes
CREATE INDEX IF NOT EXISTS idx_usuario_lida ON notificacoes(id_usuario, lida);
CREATE INDEX IF NOT EXISTS idx_data ON notificacoes(data);
CREATE INDEX IF NOT EXISTS idx_tipo ON notificacoes(tipo);

-- contas_pagar_receber
CREATE INDEX IF NOT EXISTS idx_usuario_tipo ON contas_pagar_receber(id_usuario, tipo);
CREATE INDEX IF NOT EXISTS idx_status ON contas_pagar_receber(status);
CREATE INDEX IF NOT EXISTS idx_data_vencimento ON contas_pagar_receber(data_vencimento);
CREATE INDEX IF NOT EXISTS idx_usuario_status_tipo ON contas_pagar_receber(id_usuario, status, tipo);

-- conciliacao
CREATE INDEX IF NOT EXISTS idx_conta ON conciliacao(id_conta);
CREATE INDEX IF NOT EXISTS idx_data ON conciliacao(data);

-- planejamento_cenarios
CREATE INDEX IF NOT EXISTS idx_usuario ON planejamento_cenarios(id_usuario);
CREATE INDEX IF NOT EXISTS idx_tipo ON planejamento_cenarios(tipo);

-- ==================================================
-- ANALYZE TABLE - Atualizar estatísticas
-- ==================================================

ANALYZE TABLE usuarios;
ANALYZE TABLE lancamentos;
ANALYZE TABLE categorias;
ANALYZE TABLE metas;
ANALYZE TABLE investimentos;
ANALYZE TABLE orcamentos;
ANALYZE TABLE contas_bancarias;
ANALYZE TABLE cartoes;
ANALYZE TABLE lembretes;
ANALYZE TABLE recorrencias;
ANALYZE TABLE familia;
ANALYZE TABLE familia_membros;
ANALYZE TABLE anexos;
ANALYZE TABLE anexos_lancamentos;
ANALYZE TABLE notificacoes;
ANALYZE TABLE contas_pagar_receber;
ANALYZE TABLE conciliacao;
ANALYZE TABLE planejamento_cenarios;

-- ==================================================
-- OPTIMIZE TABLE - Desfragmentar e reorganizar
-- ==================================================

OPTIMIZE TABLE usuarios;
OPTIMIZE TABLE lancamentos;
OPTIMIZE TABLE categorias;
OPTIMIZE TABLE metas;
OPTIMIZE TABLE investimentos;
OPTIMIZE TABLE orcamentos;
OPTIMIZE TABLE contas_bancarias;
OPTIMIZE TABLE cartoes;
OPTIMIZE TABLE lembretes;
OPTIMIZE TABLE recorrencias;
OPTIMIZE TABLE familia;
OPTIMIZE TABLE familia_membros;
OPTIMIZE TABLE anexos;
OPTIMIZE TABLE anexos_lancamentos;
OPTIMIZE TABLE notificacoes;
OPTIMIZE TABLE contas_pagar_receber;
OPTIMIZE TABLE conciliacao;
OPTIMIZE TABLE planejamento_cenarios;

-- ==================================================
-- ✅ OTIMIZAÇÃO CONCLUÍDA
-- ==================================================

SELECT '✅ Todos os índices criados com sucesso!' AS status;
SELECT '✅ Análise de tabelas concluída!' AS status;
SELECT '✅ Otimização concluída!' AS status;
