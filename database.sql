CREATE DATABASE IF NOT EXISTS finansmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE finansmart;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  moeda_base VARCHAR(10) DEFAULT 'BRL'
);

CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) NOT NULL,
  tipo ENUM('receita','despesa') NOT NULL
);

CREATE TABLE IF NOT EXISTS lancamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  descricao VARCHAR(255) NOT NULL,
  valor DECIMAL(12,2) NOT NULL,
  data DATE NOT NULL,
  moeda VARCHAR(10) NOT NULL DEFAULT 'BRL',
  tipo ENUM('receita','despesa') NOT NULL,
  id_usuario INT NOT NULL,
  id_categoria INT DEFAULT NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE SET NULL
);

INSERT INTO categorias (nome, tipo) VALUES
('Salário','receita'),
('Freelance','receita'),
('Alimentação','despesa'),
('Transporte','despesa'),
('Lazer','despesa');
