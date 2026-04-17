# Taskmaster Impl - Plugin para GLPI

![GLPI Version](https://img.shields.io/badge/GLPI-10.0%20%7C%2011.0-blue)
![License](https://img.shields.io/badge/License-GPL--2.0-green)

O **Taskmaster Impl** é um plugin robusto para o ecossistema GLPI, projetado para gerenciar e acompanhar processos de implantação de módulos complexos de forma estruturada, hierárquica e visual.

## 🎯 Objetivo

O objetivo principal do Taskmaster é permitir que equipes de TI e consultorias gerenciem implantações em larga escala, dividindo-as em **Módulos**, **Tarefas** e **Subtarefas**, garantindo o controle total sobre o progresso e a responsabilidade técnica de cada etapa.

## 🚀 Principais Funcionalidades

### 📋 Gestão de Estrutura
- **Módulos:** Agrupadores de funcionalidades ou serviços (ex: Módulo Financeiro, Módulo de Inventário).
- **Tarefas e Subtarefas:** Divisão atômica do trabalho necessário para implementar cada módulo.
- **Hierarquia:** Suporte completo para vincular subtarefas a tarefas e tarefas a módulos.

### 🏗️ Fluxo de Implantação
- **Criação Direcionada:** Ao iniciar uma implantação, é obrigatório informar o nome, a entidade de destino, o **Analista Responsável Geral** e a **Data de Início**.
- **Seleção de Módulos:** Possibilidade de selecionar múltiplos módulos pré-configurados no momento da criação.
- **Acompanhamento Visual:** Barra de progresso dinâmica que calcula o percentual de conclusão baseado no status de todas as tarefas e subtarefas vinculadas.

### 🛠️ Controle de Status e Execução
- **Status Disponíveis:** 
  - 🔘 Não iniciado
  - 📅 Planejado
  - 🔄 Em andamento
  - ✅ Concluído
  - ⚠️ **Não optante:** Status especial para itens que não serão implementados por decisão estratégica, exigindo justificativa obrigatória em campo de Observações (contabiliza positivamente no progresso).
- **Responsabilidade:** Designação de analistas específicos para cada tarefa ou subtarefa.
- **Datas:** Controle de data de início e conclusão por item.

### 🛡️ Administração e Permissões
- Perfis de acesso distintos para **Gestores** (gerenciam módulos e configurações) e **Analistas** (executam o acompanhamento).
- Configuração de perfis específicos permitidos para atuar como analistas.

## 💻 Requisitos Técnicos

- **GLPI:** Versão 10.0.0 ou superior (compatível com GLPI 11).
- **PHP:** 7.4 ou superior.
- **Banco de Dados:** MariaDB ou MySQL.

## 📦 Instalação

1. Clone este repositório no diretório de plugins do seu GLPI:
   ```bash
   cd glpi/plugins/
   git clone https://github.com/seu-usuario/taskmaster.git
   ```
2. Acesse o GLPI no menu **Configurar > Plugins**.
3. Localize o **Taskmaster Impl** e clique em **Instalar**.
4. Após a instalação, clique em **Ativar**.

## 🎨 Interface e Usabilidade

O plugin segue as diretrizes de design moderno do GLPI (Tabler UI para versão 10+), com elementos visuais claros:
- Campos obrigatórios sinalizados com **asterisco vermelho**.
- Listagens paginadas (limite de 30 registros) para melhor performance.
- Ícones representativos para facilitar a navegação nos menus de Ferramentas.

---

### 📝 Licença

Este plugin é distribuído sob a licença **GPLv2+**. Sinta-se à vontade para contribuir e sugerir melhorias!

---
*Desenvolvido com foco em excelência e performance por Antigravity.*
