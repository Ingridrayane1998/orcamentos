# 🏢 Sistema Facilities - Gestão Inteligente de Cotações
<div align="center">

### Sistema completo para gestão de cotações e análise de economia em facilities management

🚀 Demo • 📚 Documentação • 🔧 Instalação • 🤝 Contribuir

</div>

### 📋 Sobre o Projeto
O Sistema Facilities é uma solução web completa desenvolvida para empresas que precisam de controle eficiente sobre seus processos de cotações, negociações e análise de economia. O sistema oferece uma visão analítica baseada na data real das cotações, não apenas na data de criação no sistema, garantindo relatórios precisos e decisões mais inteligentes.

### 🎯 Problema Resolvido
Controle ineficiente de cotações e fornecedores.
Falta de visibilidade sobre as economias geradas em negociações.
Relatórios imprecisos baseados em datas de inserção no sistema.
Workflow manual e descentralizado de negociações.
Ausência de KPIs e métricas de performance para a área de compras.

### 💡 Solução Oferecida
Uma plataforma centralizada que automatiza todo o fluxo de cotações, desde a criação até o fechamento, com relatórios inteligentes e análise de ROI baseada na data real dos processos, fornecendo uma visão clara do desempenho da equipe.

### ✨ Funcionalidades Principais
🔥 Core Features
📊 Dashboard Analítico Avançado

KPIs em tempo real (Total de Cotações, Economia Gerada, % de Saving).
Gráficos de evolução temporal para acompanhamento de performance.
Análise por categoria e por usuário.
Filtros inteligentes por período, baseados na data da cotação.

### 📝 Gestão Completa de Cotações
CRUD (Criar, Ler, Atualizar, Deletar) completo com validações no servidor.
Numeração automática e sequencial de cotações baseada no ano (ex: 2024-001).
Campo dedicado para a data real da cotação, dissociada da data de criação.
Workflow de status (Em Cotação, Fechado, Cancelado) automatizado.

### 💰 Sistema de Negociação
Registro de múltiplas propostas por fornecedor para cada cotação.
Cálculo automático de economia (saving) ao fechar uma negociação.
Histórico completo de todas as negociações e valores.
Alertas visuais para cotações pendentes ou próximas do vencimento.

### 📈 Relatórios e Analytics
Relatório de economia total e percentual médio de saving.
Análise de performance por categoria de produto/serviço e por comprador.
Gráfico de evolução mensal da economia, refletindo a data correta dos eventos.
Exportação de dados para formatos Excel (.xlsx) e CSV.

### 🛠️ Funcionalidades de Apoio
👥 Gestão de Usuários com diferentes níveis de permissão (Admin/Usuário).
🏭 Cadastro de Fornecedores com validação de CNPJ.
📑 Categorização de produtos e serviços para melhor organização.
🔐 Sistema de Autenticação robusto com gerenciamento de sessão.
📱 Interface Responsiva desenvolvida com Bootstrap.
🎨 Design System consistente para uma melhor experiência do usuário.

### 🖼️ Screenshots (Em construção)
Dashboard Principal
Formulário de Nova Cotação


### 🚀 Instalação
Pré-requisitos
PHP 7.4+ com as extensões: PDO MySQL, mbstring, json.
MySQL 8.0+ ou MariaDB 10.4+.
Servidor Web (Apache com mod_rewrite ou Nginx).


### Passo a Passo:
📦 Clone o Repositório
Bash

git clone [https://github.com/seu-usuario/sistema-facilities.git
cd sistema-facilities](https://github.com/Ingridrayane1998/orcamentos)

🗄️ Configuração do Banco de Dados
Crie um novo banco de dados:

SQL
CREATE DATABASE facilities_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
Importe a estrutura completa do banco de dados:

Bash
mysql -u seu_usuario -p facilities_system < sql/database_completo.sql
⚙️ Configuração da Aplicação
Renomeie o arquivo de configuração de exemplo:

Bash
# (Se você tiver um arquivo de exemplo)
# mv includes/config.example.php includes/config.php
Configure as credenciais do banco de dados no arquivo includes/config.php:

PHP
define('DB_HOST', 'localhost');
define('DB_NAME', 'facilities_system');
define('DB_USER', 'seu_usuario_db');
define('DB_PASS', 'sua_senha_db');

Aponte seu servidor web para a raiz do projeto.

<VirtualHost *:80>
    ServerName facilities.local
    DocumentRoot "/caminho/para/seu/projeto/sistema-facilities"
    <Directory "/caminho/para/seu/projeto/sistema-facilities">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
🎯 Primeiro Acesso
URL de Acesso: http://localhost/sistema-facilities ou http://facilities.local (se configurado).

Usuários Padrão:

Perfil	Email	Senha	Acesso
Admin	admin@facilities.com	admin123	Completo
User	user@facilities.com	123456	Limitado

Exportar para as Planilhas
🏗️ Estrutura do Projeto
sistema-facilities/
├── 📁 assets/
│   ├── css/
│   ├── js/
│   └── images/
├── 📁 includes/
│   ├── config.php
│   └── functions.php
├── 📁 pages/
│   ├── dashboard.php
│   └── cotacoes/ (CRUD de cotações)
├── 📁 auth/
│   ├── login.php
│   └── logout.php
├── 📁 sql/
│   └── database_completo.sql
├── .htaccess
├── index.php
└── README.md

### 🛠️ Stack Tecnológico
Backend
PHP 7.4+: Linguagem principal para a lógica de servidor.
MySQL 8.0+: Banco de dados relacional.
PDO (PHP Data Objects): Camada de abstração para acesso seguro ao banco de dados.

Frontend
Bootstrap 5.3: Framework CSS para design responsivo e componentes de UI.
Chart.js: Biblioteca para criação de gráficos interativos no dashboard.
JavaScript (ES6): Para interatividade e validações no lado do cliente.
Font Awesome: Biblioteca de ícones.


👨‍💻 Autor
Desenvolvido por Ingrid Rayane.

GitHub: [@ingridrayane1998](https://github.com/Ingridrayane1998)

LinkedIn: www.linkedin.com/in/ingrid-rayane-5977a0195

<div align="center">

Se este projeto te ajudou, considere dar uma ⭐ no repositório!

</div>
