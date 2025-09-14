# 📋 DESCRIÇÃO DO PROJETO - Sistema Facilities
### 🎯 Resumo Executivo
O Sistema Facilities é uma aplicação web completa desenvolvida em PHP para gestão inteligente de cotações e análise de economia em facilities management. O sistema oferece controle total sobre o ciclo de vida das cotações, desde a solicitação até o fechamento, com foco especial na data real dos processos para gerar relatórios precisos e confiáveis.

## 🔍 Contexto e Motivação
### Problema Identificado
Empresas de facilities management enfrentam desafios significativos no controle de cotações:
Relatórios imprecisos baseados em datas de criação no sistema, e não na data do evento.
Falta de visibilidade sobre as economias reais geradas nas negociações.
Workflow manual e descentralizado, propenso a erros e perda de informações.
Ausência de KPIs claros para a tomada de decisões estratégicas.

### Solução Proposta
Um sistema web que automatiza todo o fluxo de cotações, oferecendo:
Dashboard analítico com indicadores baseados na data real dos processos.
Cálculo automático de saving (economia) em cada negociação.
Workflow sistematizado para registro e acompanhamento de propostas.
Relatórios inteligentes com filtros avançados para análises detalhadas.

### 🏗️ Arquitetura Técnica
Stack Tecnológico
Backend: PHP 7.4+ com PDO para acesso seguro ao MySQL.
Frontend: Bootstrap 5.3, Chart.js e JavaScript (ES6).
Banco de Dados: MySQL 8.0+ com views otimizadas para relatórios.
Arquitetura: MVC simplificado, monolítico.

### Padrões Utilizados
Repository Pattern (básico) para abstração da lógica de dados.
Dependency Injection para configurações globais (conexão com DB).
Prepared Statements para prevenção de SQL Injection.
Responsive Design com abordagem mobile-first.

### 💡 Diferenciais Técnicos
1. Data Real da Cotação
Campo específico para a data real do processo, dissociado da data de criação do registro.
Todos os relatórios e KPIs são baseados no momento real dos processos, garantindo precisão.
Numeração automática de cotações baseada no ano do evento (ex: 2025-001).

2. Dashboard Inteligente
KPIs atualizados em tempo real (Total de Cotações, Economia, % de Saving).

Gráficos interativos (criados com Chart.js) para visualização de tendências.
Filtros por período com validação de datas.
Performance otimizada com uso de índices estratégicos no banco de dados.

3. Sistema de Economia (Saving)
Cálculo automático da economia gerada em cada negociação fechada.
Análise de saving percentual por categoria de serviço/produto.
Gráficos comparativos de economia ao longo do tempo.
Análise de ROI por usuário e por categoria.

4. Interface Intuitiva (UI/UX)
Design system consistente em toda a aplicação.
Validações robustas no client-side (JavaScript) e server-side (PHP).
Feedback visual imediato para ações do usuário (alertas, modais, etc.).
Experiência de uso otimizada para dispositivos móveis.

### 📊 Funcionalidades Detalhadas
Core Business
Gestão de Cotações

CRUD completo com validações de dados.
Workflow de status automatizado (Em Cotação, Fechado, Cancelado).
Histórico completo de alterações (log de atividades).
Sistema de alerta para cotações próximas ao vencimento.
Sistema de Negociação
Registro de múltiplas propostas por fornecedor para cada cotação.
Comparativo automático de valores entre propostas.
Histórico de negociação para auditoria.

Roadmap: Aprovação por múltiplos níveis.

Relatórios e Analytics
Dashboard executivo com visão geral.
Gráfico de evolução temporal de custos e economias.
Análise de performance por categoria e por usuário.
Exportação de dados para Excel (.xlsx) e CSV.

Módulos de Apoio
Gestão de Usuários com perfis de acesso (roles: admin/user).
Cadastro de Fornecedores com validação de CNPJ.
Categorização flexível de produtos e serviços.
Sistema de Autenticação robusto com gerenciamento de sessão.

### 🎨 Design e UX
Princípios de Design
Abordagem Mobile-first: A interface é projetada primeiro para telas pequenas.
Design System Consistente: Cores, tipografia e componentes padronizados.
Feedback Visual Imediato: O usuário sempre sabe o resultado de suas ações.
Acessibilidade: Boas práticas como contraste de cores e uso de labels ARIA.

### 🔐 Segurança
Implementado
Prevenção de SQL Injection (uso exclusivo de PDO com Prepared Statements).
Gerenciamento de Sessão seguro (regeneração de ID, flags de segurança).
Validação de Entradas dupla (no cliente com JS e no servidor com PHP).
Controle de Acesso Baseado em Perfil (Role-Based Access Control).

### 🎯 Público-Alvo
Primário
Empresas de facilities management.
Departamentos de compras e suprimentos.
Gestores de contratos e fornecedores.

### Este sistema representa uma solução moderna e eficiente para um problema real do mercado, combinando tecnologias consolidadas com inovações focadas na experiência do usuário e na precisão dos dados.
