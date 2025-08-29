CREATE DATABASE IF NOT EXISTS pi_autonomos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pi_autonomos;

CREATE TABLE usuario (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(150) NOT NULL,
  cpf_cnpj VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  telefone VARCHAR(30),
  tipo ENUM('Cliente','Prestador') NOT NULL,
  banco VARCHAR(100),
  agencia VARCHAR(50),
  conta VARCHAR(50),
  tipo_conta VARCHAR(50),
  criado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE prestador (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NOT NULL,
  descricao TEXT NOT NULL,
  tipo_servico VARCHAR(120) NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  foto VARCHAR(255),
  nota_media DECIMAL(3,2) DEFAULT 0,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
);

CREATE TABLE categoria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  descricao VARCHAR(255)
);

CREATE TABLE servico_produto (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  prestador_id BIGINT UNSIGNED,
  tipo ENUM('Serviço','Produto') NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  descricao TEXT,
  categoria_id INT,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (prestador_id) REFERENCES prestador(id) ON DELETE SET NULL,
  FOREIGN KEY (categoria_id) REFERENCES categoria(id) ON DELETE SET NULL
);

CREATE TABLE endereco (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NOT NULL,
  logradouro VARCHAR(200) NOT NULL,
  numero VARCHAR(20) NOT NULL,
  bairro VARCHAR(100) NOT NULL,
  cidade VARCHAR(100) NOT NULL,
  estado VARCHAR(50) NOT NULL,
  cep VARCHAR(20),
  FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
);

CREATE TABLE agendamento (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  status ENUM('em análise','aceito','em execução','cancelado','finalizado') NOT NULL DEFAULT 'em análise',
  data_agendamento DATE NOT NULL,
  hora_agendamento TIME NOT NULL,
  endereco_id BIGINT UNSIGNED,
  servico_id BIGINT UNSIGNED NOT NULL,
  cliente_id BIGINT UNSIGNED NOT NULL,
  prestador_id BIGINT UNSIGNED NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  data_execucao DATE,
  hora_execucao TIME,
  taxa DECIMAL(5,2) DEFAULT 0,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (endereco_id) REFERENCES endereco(id) ON DELETE SET NULL,
  FOREIGN KEY (servico_id) REFERENCES servico_produto(id),
  FOREIGN KEY (cliente_id) REFERENCES usuario(id),
  FOREIGN KEY (prestador_id) REFERENCES prestador(id)
);

CREATE TABLE recebimento (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  agendamento_id BIGINT UNSIGNED NOT NULL,
  status ENUM('pendente','quitado') DEFAULT 'pendente',
  data_recebimento DATE,
  forma_recebimento VARCHAR(50),
  taxa DECIMAL(5,2) DEFAULT 0,
  FOREIGN KEY (agendamento_id) REFERENCES agendamento(id)
);

CREATE TABLE avaliacao (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  agendamento_id BIGINT UNSIGNED NOT NULL,
  nota TINYINT UNSIGNED NOT NULL,
  comentario TEXT,
  data_avaliacao DATE DEFAULT CURRENT_DATE,
  FOREIGN KEY (agendamento_id) REFERENCES agendamento(id)
);

CREATE TABLE mensagem (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  agendamento_id BIGINT UNSIGNED,
  cliente_id BIGINT UNSIGNED,
  prestador_id BIGINT UNSIGNED,
  conteudo TEXT NOT NULL,
  data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (agendamento_id) REFERENCES agendamento(id),
  FOREIGN KEY (cliente_id) REFERENCES usuario(id),
  FOREIGN KEY (prestador_id) REFERENCES prestador(id)
);


