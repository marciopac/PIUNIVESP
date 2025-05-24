-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 04-Set-2023 às 18:38
-- Versão do servidor: 10.4.22-MariaDB
-- versão do PHP: 8.1.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `projeto`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `acessos`
--

CREATE TABLE `acessos` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `chave` varchar(50) NOT NULL,
  `grupo` int(11) NOT NULL,
  `pagina` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Extraindo dados da tabela `acessos`
--

INSERT INTO `acessos` (`id`, `nome`, `chave`, `grupo`, `pagina`) VALUES
(1, 'Home', 'home', 0, 'Sim'),
(2, 'Configurações', 'configuracoes', 0, 'Não'),
(3, 'Usuários', 'usuarios', 1, 'Sim'),
(4, 'Acessos', 'acessos', 2, 'Sim'),
(5, 'Grupos Acesso', 'grupo_acessos', 2, 'Sim');

-- --------------------------------------------------------

--
-- Estrutura da tabela `config`
--

CREATE TABLE `config` (
  `nome` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(100) DEFAULT NULL,
  `instagram` varchar(100) DEFAULT NULL,
  `logo` varchar(100) DEFAULT NULL,
  `icone` varchar(100) DEFAULT NULL,
  `logo_rel` varchar(100) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `ativo` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Extraindo dados da tabela `config`
--

INSERT INTO `config` (`nome`, `email`, `telefone`, `endereco`, `instagram`, `logo`, `icone`, `logo_rel`, `id`, `ativo`) VALUES
('Projeto Modelo', 'contato@hugocursos.com.br', '(31) 97527-5084', 'Rua X Número 150 - Bairro Centro Belo Horizonte - MG', '', 'logo.png', 'icone.png', 'logo.jpg', 1, 'Sim');

-- --------------------------------------------------------

--
-- Estrutura da tabela `grupo_acessos`
--

CREATE TABLE `grupo_acessos` (
  `id` int(11) NOT NULL,
  `nome` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Extraindo dados da tabela `grupo_acessos`
--

INSERT INTO `grupo_acessos` (`id`, `nome`) VALUES
(1, 'Pessoas'),
(2, 'Cadastros');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `senha` varchar(50) DEFAULT NULL,
  `senha_crip` varchar(130) NOT NULL,
  `nivel` varchar(25) NOT NULL,
  `ativo` varchar(5) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(150) DEFAULT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `data` date NOT NULL,
  `token` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `senha_crip`, `nivel`, `ativo`, `telefone`, `endereco`, `foto`, `data`, `token`) VALUES
(15, 'Projeto Modelo', 'contato@hugocursos.com.br', '', '40bd001563085fc35165329ea1ff5c5ecbdbbeef', 'Administrador', 'Sim', '(31) 97527-5084', NULL, 'sem-foto.jpg', '2023-09-04', NULL),
(16, 'Usuário Teste', 'usuario@hotmail.com', '', '40bd001563085fc35165329ea1ff5c5ecbdbbeef', 'Comum', 'Sim', '(33) 33333-3333', '', 'sem-foto.jpg', '2023-09-04', 'dea2f6e235832c30e7c2dadce33ccc1294fea4abe1b8bc1d61ac5fc2aedf0a02');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios_permissoes`
--

CREATE TABLE `usuarios_permissoes` (
  `id` int(11) NOT NULL,
  `usuario` int(11) NOT NULL,
  `permissao` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Extraindo dados da tabela `usuarios_permissoes`
--

INSERT INTO `usuarios_permissoes` (`id`, `usuario`, `permissao`) VALUES
(76, 11, 2),
(77, 11, 4),
(78, 16, 3),
(79, 16, 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `acessos`
--
ALTER TABLE `acessos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `grupo_acessos`
--
ALTER TABLE `grupo_acessos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `usuarios_permissoes`
--
ALTER TABLE `usuarios_permissoes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `acessos`
--
ALTER TABLE `acessos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `config`
--
ALTER TABLE `config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `grupo_acessos`
--
ALTER TABLE `grupo_acessos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `usuarios_permissoes`
--
ALTER TABLE `usuarios_permissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
