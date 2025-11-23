CREATE DATABASE IF NOT EXISTS finansmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE finansmart;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `finansmart`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `alertas`
--

CREATE TABLE `alertas` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` enum('orcamento','meta','vencimento','sistema','outro') NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensagem` text NOT NULL,
  `data_criacao` datetime DEFAULT current_timestamp(),
  `data_leitura` datetime DEFAULT NULL,
  `data_expiracao` datetime DEFAULT NULL,
  `nivel` enum('info','warning','danger') DEFAULT 'info',
  `status` enum('nao_lido','lido','arquivado') DEFAULT 'nao_lido'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ativos_financeiros`
--

CREATE TABLE `ativos_financeiros` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` enum('acao','fundo','criptomoeda','renda_fixa','tesouro_direto','outros') NOT NULL,
  `nome` varchar(100) NOT NULL,
  `simbolo` varchar(20) NOT NULL,
  `quantidade` decimal(15,6) NOT NULL,
  `preco_medio` decimal(15,2) NOT NULL,
  `valor_total` decimal(15,2) GENERATED ALWAYS AS (`quantidade` * `preco_medio`) STORED,
  `data_aquisicao` date NOT NULL,
  `data_vencimento` date DEFAULT NULL,
  `rentabilidade_esperada` decimal(5,2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('ativo','vendido','vencido') DEFAULT 'ativo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cartoes`
--

CREATE TABLE `cartoes` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `bandeira` varchar(50) DEFAULT NULL,
  `numero_final` varchar(4) DEFAULT NULL,
  `limite` decimal(12,2) DEFAULT NULL,
  `dia_fechamento` int(11) NOT NULL,
  `dia_vencimento` int(11) NOT NULL,
  `status` enum('ativo','bloqueado','cancelado') DEFAULT 'ativo',
  `cor` varchar(7) DEFAULT '#000000',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `tipo` enum('receita','despesa') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `tipo`, `icone`, `cor`, `descricao`) VALUES
(1, 'Salário', 'receita', 'fa-money-bill-wave', '#28a745', NULL),
(2, 'Freelance', 'receita', 'fa-laptop', '#17a2b8', NULL),
(3, 'Investimentos', 'receita', 'fa-chart-line', '#fd7e14', NULL),
(4, 'Rendimentos', 'receita', 'fa-percentage', '#20c997', NULL),
(5, 'Bônus', 'receita', 'fa-gift', '#6f42c1', NULL),
(6, 'Reembolsos', 'receita', 'fa-undo', '#e83e8c', NULL),
(7, 'Alimentação', 'despesa', 'fa-utensils', '#dc3545', NULL),
(8, 'Transporte', 'despesa', 'fa-car', '#6c757d', NULL),
(9, 'Moradia', 'despesa', 'fa-home', '#fd7e14', NULL),
(10, 'Saúde', 'despesa', 'fa-heartbeat', '#e83e8c', NULL),
(11, 'Educação', 'despesa', 'fa-graduation-cap', '#20c997', NULL),
(12, 'Lazer', 'despesa', 'fa-smile', '#ffc107', NULL),
(13, 'Vestuário', 'despesa', 'fa-tshirt', '#6f42c1', NULL),
(14, 'Investimentos', 'despesa', 'fa-chart-pie', '#28a745', NULL),
(15, 'Seguros', 'despesa', 'fa-shield-alt', '#17a2b8', NULL),
(16, 'Presentes', 'despesa', 'fa-gift', '#e83e8c', NULL),
(17, 'Impostos', 'despesa', 'fa-file-invoice-dollar', '#dc3545', NULL),
(18, 'Outros', 'despesa', 'fa-ellipsis-h', '#6c757d', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_recorrentes`
--

CREATE TABLE `contas_recorrentes` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `dia_vencimento` int(11) NOT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `frequencia` enum('diaria','semanal','mensal','bimestral','trimestral','semestral','anual') DEFAULT 'mensal',
  `data_inicio` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `status` enum('ativa','pausada','cancelada') DEFAULT 'ativa',
  `ultima_geracao` date DEFAULT NULL,
  `proxima_geracao` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `faturas_cartao`
--

CREATE TABLE `faturas_cartao` (
  `id` int(11) NOT NULL,
  `id_cartao` int(11) NOT NULL,
  `mes_referencia` varchar(7) NOT NULL,
  `valor_total` decimal(12,2) DEFAULT 0.00,
  `status` enum('aberta','fechada','paga') DEFAULT 'aberta',
  `data_fechamento` date DEFAULT NULL,
  `data_vencimento` date DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `investimentos`
--

CREATE TABLE `investimentos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `valor_investido` decimal(12,2) NOT NULL,
  `valor_atual` decimal(12,2) DEFAULT 0.00,
  `rendimento_percentual` decimal(5,2) DEFAULT 0.00,
  `data_inicio` date NOT NULL,
  `data_vencimento` date DEFAULT NULL,
  `risco` enum('Baixo','Médio','Alto') NOT NULL,
  `status` enum('Ativo','Resgatado','Vencido') DEFAULT 'Ativo',
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `lancamentos`
--

CREATE TABLE `lancamentos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `data` date NOT NULL,
  `data_vencimento` date DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `moeda` varchar(10) NOT NULL DEFAULT 'BRL',
  `tipo` enum('receita','despesa') NOT NULL,
  `status` enum('pendente','pago','atrasado') DEFAULT 'pendente',
  `comprovante_url` varchar(255) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `recorrente` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `metas`
--

CREATE TABLE `metas` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor_meta` decimal(12,2) NOT NULL,
  `valor_atual` decimal(12,2) DEFAULT 0.00,
  `data_limite` date NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `status` enum('Em andamento','Concluída','Atrasada') DEFAULT 'Em andamento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
ADD COLUMN moeda VARCHAR(10) NOT NULL DEFAULT 'BRL' AFTER status;
--
-- Estrutura para tabela `metas_financeiras`
--

CREATE TABLE `metas_financeiras` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `valor_meta` decimal(12,2) NOT NULL,
  `valor_atual` decimal(12,2) DEFAULT 0.00,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `categoria_relacionada` int(11) DEFAULT NULL,
  `prioridade` enum('baixa','media','alta') DEFAULT 'media',
  `status` enum('em_andamento','concluida','cancelada','atrasada') DEFAULT 'em_andamento',
  `notificar_progresso` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `orcamentos`
--

CREATE TABLE `orcamentos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `valor_limite` decimal(12,2) NOT NULL,
  `valor_atual` decimal(12,2) DEFAULT 0.00,
  `mes_ano` varchar(7) NOT NULL,
  `notificar_em_porcentagem` int(11) DEFAULT 80,
  `status` enum('dentro_limite','proximo_limite','excedido') DEFAULT 'dentro_limite',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_personalizados`
--

CREATE TABLE `relatorios_personalizados` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `filtros` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filtros`)),
  `periodo` enum('diario','semanal','mensal','anual','personalizado') DEFAULT 'mensal',
  `categorias` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`categorias`)),
  `tipo_grafico` enum('linha','barra','pizza','tabela') DEFAULT 'tabela',
  `ordem` varchar(50) DEFAULT 'data_desc',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `moeda_base` varchar(10) DEFAULT 'BRL',
  `tema` varchar(20) DEFAULT 'light',
  `notificacoes_email` tinyint(1) DEFAULT 1,
  `notificacoes_sistema` tinyint(1) DEFAULT 1,
  `formato_data` varchar(20) DEFAULT 'DD/MM/YYYY',
  `data_registro` datetime DEFAULT current_timestamp(),
  `ultimo_acesso` datetime DEFAULT NULL,
  `status` enum('ativo','inativo','bloqueado') DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `token` (`token`(191)),
  KEY `email` (`email`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `moeda_base`, `tema`, `notificacoes_email`, `notificacoes_sistema`, `formato_data`, `data_registro`, `ultimo_acesso`, `status`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$ceVuB2VpLA6g96nFpiuA8.tOezj1eKsrjdgJGPhf9wbpunybJNGb2', 'BRL', 'light', 1, 1, 'DD/MM/YYYY', '2025-11-04 15:28:26', NULL, 'ativo');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alertas`
--
ALTER TABLE `alertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alertas_usuario_status` (`id_usuario`,`status`);

--
-- Índices de tabela `ativos_financeiros`
--
ALTER TABLE `ativos_financeiros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `cartoes`
--
ALTER TABLE `cartoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `contas_recorrentes`
--
ALTER TABLE `contas_recorrentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `idx_contas_recorrentes_status` (`id_usuario`,`status`);

--
-- Índices de tabela `faturas_cartao`
--
ALTER TABLE `faturas_cartao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cartao` (`id_cartao`);

--
-- Índices de tabela `investimentos`
--
ALTER TABLE `investimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `lancamentos`
--
ALTER TABLE `lancamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `idx_lancamentos_usuario_data` (`id_usuario`,`data`);

--
-- Índices de tabela `metas`
--
ALTER TABLE `metas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `metas_financeiras`
--
ALTER TABLE `metas_financeiras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_relacionada` (`categoria_relacionada`),
  ADD KEY `idx_metas_usuario_status` (`id_usuario`,`status`);

--
-- Índices de tabela `orcamentos`
--
ALTER TABLE `orcamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_orcamento` (`id_usuario`,`id_categoria`,`mes_ano`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `idx_orcamentos_usuario_mes` (`id_usuario`,`mes_ano`);

--
-- Índices de tabela `relatorios_personalizados`
--
ALTER TABLE `relatorios_personalizados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`(191));

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alertas`
--
ALTER TABLE `alertas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ativos_financeiros`
--
ALTER TABLE `ativos_financeiros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cartoes`
--
ALTER TABLE `cartoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `contas_recorrentes`
--
ALTER TABLE `contas_recorrentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `faturas_cartao`
--
ALTER TABLE `faturas_cartao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `investimentos`
--
ALTER TABLE `investimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `lancamentos`
--
ALTER TABLE `lancamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `metas`
--
ALTER TABLE `metas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `metas_financeiras`
--
ALTER TABLE `metas_financeiras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `orcamentos`
--
ALTER TABLE `orcamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_personalizados`
--
ALTER TABLE `relatorios_personalizados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `alertas`
--
ALTER TABLE `alertas`
  ADD CONSTRAINT `alertas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ativos_financeiros`
--
ALTER TABLE `ativos_financeiros`
  ADD CONSTRAINT `ativos_financeiros_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cartoes`
--
ALTER TABLE `cartoes`
  ADD CONSTRAINT `cartoes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Tabela de contas bancárias
--
CREATE TABLE IF NOT EXISTS `contas_bancarias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `agencia` varchar(20) DEFAULT NULL,
  `numero_conta` varchar(30) DEFAULT NULL,
  `saldo_inicial` decimal(15,2) NOT NULL DEFAULT 0.00,
  `saldo_atual` decimal(15,2) NOT NULL DEFAULT 0.00,
  `moeda` varchar(3) NOT NULL DEFAULT 'BRL',
  `status` enum('ativa','inativa') DEFAULT 'ativa',
  `cor` varchar(7) DEFAULT '#6a0dad',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `contas_bancarias_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tabela de transferências entre contas
--
CREATE TABLE IF NOT EXISTS `transferencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `conta_origem` int(11) NOT NULL,
  `conta_destino` int(11) NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `data_transferencia` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `conta_origem` (`conta_origem`),
  KEY `conta_destino` (`conta_destino`),
  CONSTRAINT `transferencias_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transferencias_ibfk_2` FOREIGN KEY (`conta_origem`) REFERENCES `contas_bancarias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transferencias_ibfk_3` FOREIGN KEY (`conta_destino`) REFERENCES `contas_bancarias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Restrições para tabelas `contas_recorrentes`
--
ALTER TABLE `contas_recorrentes`
  ADD CONSTRAINT `contas_recorrentes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contas_recorrentes_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `faturas_cartao`
--
ALTER TABLE `faturas_cartao`
  ADD CONSTRAINT `faturas_cartao_ibfk_1` FOREIGN KEY (`id_cartao`) REFERENCES `cartoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `investimentos`
--
ALTER TABLE `investimentos`
  ADD CONSTRAINT `investimentos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `lancamentos`
--
ALTER TABLE `lancamentos`
  ADD CONSTRAINT `lancamentos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lancamentos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `metas`
--
ALTER TABLE `metas`
  ADD CONSTRAINT `metas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `metas_financeiras`
--
ALTER TABLE `metas_financeiras`
  ADD CONSTRAINT `metas_financeiras_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `metas_financeiras_ibfk_2` FOREIGN KEY (`categoria_relacionada`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `orcamentos`
--
ALTER TABLE `orcamentos`
  ADD CONSTRAINT `orcamentos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orcamentos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `relatorios_personalizados`
--
ALTER TABLE `relatorios_personalizados`
  ADD CONSTRAINT `relatorios_personalizados_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Estrutura para tabela `planejamento_cenarios`
--

CREATE TABLE `planejamento_cenarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('receita','despesa','investimento','divida') NOT NULL,
  `valor_base` decimal(15,2) NOT NULL,
  `percentual_variacao` decimal(5,2) NOT NULL,
  `resultado_calculado` decimal(15,2) NOT NULL,
  `data_criacao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `planejamento_cenarios_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `importacoes`
--

CREATE TABLE `importacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `tipo_arquivo` enum('ofx','csv') NOT NULL,
  `total_lancamentos` int(11) DEFAULT 0,
  `data_importacao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `importacoes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_pagar`
--

CREATE TABLE `contas_pagar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `descricao` varchar(200) NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `status` enum('pendente','pago','atrasado') DEFAULT 'pendente',
  `id_categoria` int(11) DEFAULT NULL,
  `fornecedor` varchar(100) DEFAULT NULL,
  `num_documento` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_criacao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_categoria` (`id_categoria`),
  KEY `vencimento` (`vencimento`),
  KEY `status` (`status`),
  CONSTRAINT `contas_pagar_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contas_pagar_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_receber`
--

CREATE TABLE `contas_receber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `descricao` varchar(200) NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `vencimento` date NOT NULL,
  `data_recebimento` date DEFAULT NULL,
  `status` enum('pendente','recebido','atrasado') DEFAULT 'pendente',
  `id_categoria` int(11) DEFAULT NULL,
  `cliente` varchar(100) DEFAULT NULL,
  `num_documento` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_criacao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_categoria` (`id_categoria`),
  KEY `vencimento` (`vencimento`),
  KEY `status` (`status`),
  CONSTRAINT `contas_receber_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contas_receber_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
