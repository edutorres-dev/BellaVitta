# 🍕 BellaVitta Pizzaria

Sistema Web completo de uma pizzaria com integração a um ERP simples verticalizado. O projeto inclui:

- Cardápio dinâmico
- Sistema de pedidos com sacola virtual
- Autenticação e gerenciamento de usuários
- Painel administrativo completo
- Relatórios e gráficos financeiros
- Integrações via e-mail e simulação de API para WhatsApp

---

## 🧾 Sumário

- [🧩 Funcionalidades](#-funcionalidades)
- [🖥️ Tecnologias Utilizadas](#-tecnologias-utilizadas)
- [📁 Estrutura do Projeto](#-estrutura-do-projeto)
- [⚙️ Instalação e Configuração](#-instalação-e-configuração)
- [📊 Banco de Dados](#-banco-de-dados)
- [🔐 Autenticação e Acesso](#-autenticação-e-acesso)
- [🎨 UI e Paleta de Cores](#-ui-e-paleta-de-cores)
- [📄 Licença](#-licença)
- [👨‍💻 Autor](#-autor)

---

## 🧩 Funcionalidades

- Cardápio interativo e responsivo
- Cadastro, login e recuperação de senha
- Área do cliente com sacola e finalização do pedido
- Envio automatizado de e-mail (PHPMailer)
- Simulação de envio via WhatsApp com JSON
- Área administrativa protegida com:
  - Gerenciamento de pedidos
  - Gestão de produtos, clientes e funcionários
  - Relatórios financeiros com gráficos
  - Filtros dinâmicos
- Controle de acesso por nível: cliente ou master

---

## 🖥️ Tecnologias Utilizadas

### Frontend

- HTML5
- CSS3 + Bootstrap 5
- JavaScript (ES6)
- jQuery

### Backend

- PHP 8
- MySQL
- Apache
- PhpMyAdmin (opcional)
- PHPMailer

---

## 📁 Estrutura do Projeto

```
bella-vitta/
├── index.php                  # Página inicial
├── cadastrar.php              # Cadastro de usuários
├── login.php                  # Tela de login
├── restrita.php               # Área logada do cliente
├── admin_pedidos.php          # Painel Admin: Pedidos
├── admin_produtos.php         # Painel Admin: Produtos
├── admin_clientes.php         # Painel Admin: Clientes
├── financeiro.php             # Painel Admin: Financeiro
├── recupera_senha.php         # Recuperação de senha
├── confirmacao.php            # Confirmação de código
├── email_enviado_recupera.php # Confirmação de envio
├── logout.php                 # Logout de sessão
│
└── assets/
    ├── config/                # Configurações e credenciais
    ├── css/                   # Estilos e temas
    ├── js/                    # Scripts e lógica do frontend
    ├── lib/PHPMailer/         # Biblioteca de envio de e-mails
    └── img/                   # Imagens do projeto
```

> Os arquivos possuem comentários internos explicativos para compreensão da estrutura do código e funcionalidades .

---

## ⚙️ Instalação e Configuração

### 1. Pré-requisitos

- PHP 8+
- MySQL 5.7+
- Apache Web Server
- PHPMailer (biblioteca)
- Editor de código (VSCode recomendado)
- PhpMyAdmin (opcional)

> Para facilitar, use o [XAMPP](https://www.apachefriends.org/pt_br/index.html), que já vem com PHP, MySQL e Apache.

---

### 2. Instalação com XAMPP

#### Windows

1. Baixe o XAMPP e instale com Apache, MySQL, PHP e PhpMyAdmin.
2. Copie o projeto para: `C:\xampp\htdocs\NomeDoProjeto`
3. Inicie Apache e MySQL via XAMPP Control Panel
4. Acesse: `http://localhost/NomeDoProjeto`

#### Linux

```bash
# Baixe e instale o XAMPP
wget https://www.apachefriends.org/xampp-files/8.2.4/xampp-linux-x64-8.2.4-0-installer.run
chmod +x xampp-linux-*.run
sudo ./xampp-linux-*.run
sudo /opt/lampp/lampp start

# Copie seu projeto para o diretório correto
sudo mv bella-vitta /opt/lampp/htdocs/
sudo chown -R $USER:$USER /opt/lampp/htdocs/bella-vitta

# Acesse via navegador
http://localhost/bella-vitta
```

#### macOS

1. Baixe o `.dmg` do XAMPP
2. Instale e execute Apache/MySQL
3. Copie o projeto para: `/Applications/XAMPP/htdocs/bella-vitta`
4. Acesse: `http://localhost/bella-vitta`

---

### 3. Configuração do Projeto

Edite `assets/config/config.php` com suas credenciais locais ou de produção:

```php
$modo = "local"; // ou "producao"

if ($modo == "local") {
    $servidor = "localhost";
    $usuario = "root";
    $senha = "";
    $banco = "bella_vitta";
}
```

---

## 📊 Banco de Dados

Execute os comandos SQL no PhpMyAdmin ou terminal:

### Tabela `usuarios`

```sql
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  contato VARCHAR(15) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  recupera_senha VARCHAR(255),
  token VARCHAR(64),
  codigo_confirmacao VARCHAR(64),
  status ENUM('novo','confirmado') DEFAULT 'novo',
  data_cadastro DATE NOT NULL,
  nivel_acesso ENUM('cliente','master') DEFAULT 'cliente'
);
```

### Tabela `produtos`
```sql
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `imagem` varchar(255) COLLATE utf8_unicode_ci NOT NULL ,
  `preco_pequena` decimal(10,2) NOT NULL ,
  `preco_media` decimal(10,2) NOT NULL ,
  `preco_grande` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

### Tabela `pedidos`
```sql
CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `numero` varchar(20) COLLATE utf8_unicode_ci NOT NULL ,
  `pedido` text COLLATE utf8_unicode_ci NOT NULL ,
  `data_pedido` datetime NOT NULL,
  `endereco` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pagamento` enum('Pix','Cartão de Débito','Cartão de Credito','VR') COLLATE utf8_unicode_ci NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `status` enum('confirmado','preparando','entregue','cancelado') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'confirmado',
  PRIMARY KEY (`id`),
  KEY `cliente` (`cliente`),
  KEY `data_pedido` (`data_pedido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```
---

## 🔐 Autenticação e Acesso

- Login via `login.php`
- Sessão: `$_SESSION["TOKEN"]`
- Middleware de verificação:

```php
$usuario = auth($_SESSION["TOKEN"]);
if (!$usuario || $usuario['nivel'] !== 'master') {
    header("Location: login.php");
    exit;
}
```

### Níveis de Acesso:

- `cliente`: acesso limitado à página `restrita.php`
- `master`: acesso total ao painel administrativo

---

## 🎨 UI e Paleta de Cores

- **Fundo**: `#252525` (marrom escuro)
- **Botões**: `#ffc107` (amarelo dourado)
- **Texto claro**: `#f8f9fa`
- **Fonte**: `'Poppins', sans-serif`

---

## 📄 Licença

> © 2025 Eduardo Torres Do Ó – Todos os direitos reservados.

PROPRIETÁRIA - TODOS OS DIREITOS RESERVADOS
© 2025 Eduardo Torres Do Ó – Direitos Autorais e Propriedade Intelectual Reservados.

Este software e todo o seu conteúdo relacionado são de propriedade exclusiva do autor mencionado acima. É protegido por leis de direitos autorais e tratados internacionais.

⚖️ CONSEQUÊNCIAS LEGAIS:
Qualquer violação destes termos estará sujeita às medidas legais cabíveis conforme a Lei de Direitos Autorais (Lei nº 9.610/98) e demais legislações aplicáveis.

📧 PARA AUTORIZAÇÕES:
Para solicitar permissão de uso, entre em contato com:
Eduardo Torres Do Ó - edutorres_dev@hotmail.com

---

## 👨‍💻 Autor

**Eduardo Torres**  
Desenvolvedor Full Stack

- GitHub: https://github.com/edutorres-dev
- Email: edutorres_dev@hotmail.com
- Linkedin: https://www.linkedin.com/in/eduardo-torres-do-%C3%B3-576085385/

---
