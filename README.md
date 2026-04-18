# Taskmaster — Plugin para GLPI

![GLPI Version](https://img.shields.io/badge/GLPI-10.0%20%7C%2011.0%20%7C%2012.0-blue)
![Version](https://img.shields.io/badge/Versão-1.1.0-orange)
![License](https://img.shields.io/badge/License-GPL--2.0-green)
![Author](https://img.shields.io/badge/Autor-Wilter%20P.%20Porto-purple)

O **Taskmaster** é um plugin para o ecossistema GLPI projetado para gerenciar e acompanhar processos de implantação de módulos de forma estruturada, hierárquica e visual. Equipes de TI e consultorias conseguem controlar implantações complexas dividindo-as em **Módulos**, **Tarefas** e **Subtarefas**, com visibilidade total do progresso em cada nível.

---

## 🎯 Objetivo

Permitir que equipes de TI e consultorias gerenciem implantações em larga escala com controle granular sobre progresso, responsabilidade técnica e status de execução de cada etapa — do módulo macro até a subtarefa mais atômica.

---

## 🚀 Funcionalidades

### 📦 Gestão de Estrutura (Configuração)

| Entidade | Descrição |
|---|---|
| **Módulo** | Agrupador de funcionalidades (ex: Módulo Financeiro, Inventário). Suporta ativação/desativação. |
| **Tarefa** | Etapa de trabalho vinculada a um módulo. Pode ser marcada como ativa/inativa. |
| **Subtarefa** | Divisão atômica de uma tarefa. Pode ser marcada como ativa/inativa. |

- Proteção contra exclusão de entidades com dependências (módulos com tarefas, tarefas com subtarefas etc.).
- Hierarquia completa: Módulo → Tarefa → Subtarefa.

---

### 🏗️ Fluxo de Implantação

1. **Criação:** Ao criar uma implantação, são obrigatórios:
   - Nome da implantação
   - Entidade de destino
   - Analista responsável geral
   - Data de início
   - Um ou mais módulos a implantar (seleção múltipla)

2. **Geração automática:** Ao selecionar os módulos, todas as tarefas e subtarefas **ativas** são copiadas para a implantação automaticamente.

3. **Acompanhamento:** Na aba **Acompanhamento** da implantação é possível:
   - Visualizar o **resumo geral** (% concluído, contadores por status).
   - Gerenciar módulos vinculados (adicionar novos ou remover em lote).
   - Acompanhar **cada módulo individualmente** com sua própria barra de progresso.
   - Editar o status, analista responsável, datas e observações de cada tarefa e subtarefa.

---

### 📊 Progresso por Módulo

A tela de acompanhamento exibe, para **cada módulo vinculado**, uma barra de progresso individual calculada com base no status de todas as suas tarefas e subtarefas:

| Faixa | Cor |
|---|---|
| 0% (nenhum item concluído) | 🔴 Vermelho |
| 1% – 49% | 🟡 Laranja |
| 50% – 99% | 🔵 Azul |
| 100% (todos concluídos) | 🟢 Verde |

O contador `X / Y itens concluídos` é exibido ao lado da barra para leitura rápida.

---

### 🖨️ Relatório de Impressão

A partir da aba **Acompanhamento**, o botão **"Imprimir Relatório"** abre uma página dedicada e otimizada para impressão ou geração de PDF, contendo:

- **Cabeçalho** com o nome da organização (entidade raiz do GLPI), o rótulo *"Relatório de Implantação"*, o nome da implantação, a entidade vinculada e a data/hora de geração.
- **Card de progresso geral** com círculo colorido, barra de progresso e contador de itens concluídos (tarefas + subtarefas).
- **Tabela de módulos** em **ordem alfabética**, com:
  - Número sequencial
  - Nome do módulo
  - Contagem de itens concluídos / total
  - Barra de progresso
  - Badge com percentual colorido por faixa
- **Rodapé** com a cidade e o estado da entidade raiz e a data/hora da impressão, no formato `Goiânia-Goiás, 18/04/2026 às 09:48`.

> O relatório **não é um Ctrl+P da tela do GLPI**. É uma página standalone com identidade visual própria, sem menus ou elementos de navegação do sistema.
>
> A URL e o número de página nativos do navegador são suprimidos automaticamente via `@page { margin: 0 }`, preservando um layout limpo.

---

### 🛠️ Status de Execução

| Código | Status | Contabiliza no progresso? |
|---|---|---|
| 0 | 🔘 Não iniciado | ❌ Não |
| 1 | 📅 Planejado | ❌ Não |
| 2 | 🔄 Em andamento | ❌ Não |
| 3 | ✅ Concluído | ✅ Sim |
| 4 | ⚠️ Não optante | ✅ Sim |

> **Não optante:** Status especial para itens que não serão implementados por decisão estratégica. Exige preenchimento obrigatório do campo **Observações** e é contabilizado positivamente no percentual de progresso.

---

### 🛡️ Administração e Permissões

Dois níveis de acesso distintos:

| Direito | Perfil recomendado | Acesso |
|---|---|---|
| `plugin_taskmaster_manage` | Administrador | Módulos, Tarefas, Subtarefas e Configurações |
| `plugin_taskmaster_impl` | Analista / Gestor | Implantações, Acompanhamento e Relatórios |

- Configuração de perfis específicos autorizados a atuar como analistas responsáveis.
- Permissões granulares de leitura, criação, edição e exclusão.
- Auto-registro de permissões na primeira utilização pelo perfil ativo.

---

### 🔒 Integridade de Dados

- **Módulos** só podem ser excluídos se não possuírem tarefas vinculadas.
- **Tarefas** só podem ser excluídas se não possuírem subtarefas vinculadas.
- Módulos vinculados a uma implantação não podem ser removidos do sistema enquanto existir esse vínculo.

---

### 🎨 Interface e Usabilidade

- Campos obrigatórios sinalizados com **asterisco vermelho (`*`)**.
- Listagens paginadas com limite de **30 registros por página**.
- Ícones representativos nos menus de **Ferramentas** para navegação intuitiva.
- Compatível com o design Tabler UI do GLPI 10/11/12.
- Reparação retroativa automática de vínculos de módulo em implantações antigas.

---

## 💻 Requisitos Técnicos

| Componente | Versão mínima |
|---|---|
| GLPI | 10.0.0 (compatível até 12.x) |
| PHP | 7.4 ou superior |
| Banco de dados | MySQL 5.7+ / MariaDB 10.3+ |

---

## 📦 Instalação

```bash
# 1. Clone o repositório no diretório de plugins do GLPI
cd glpi/plugins/
git clone https://github.com/seu-usuario/taskmaster.git taskmaster

# 2. Acesse o GLPI: Configurar > Plugins
# 3. Localize "Taskmaster" e clique em Instalar
# 4. Após a instalação, clique em Ativar
```

> O plugin realiza a criação e atualização automática das tabelas necessárias durante a ativação e na inicialização (`setup.php`).

---

## 🗃️ Estrutura de Tabelas

| Tabela | Descrição |
|---|---|
| `glpi_plugin_taskmaster_modules` | Cadastro de módulos |
| `glpi_plugin_taskmaster_tasks` | Cadastro de tarefas por módulo |
| `glpi_plugin_taskmaster_subtasks` | Cadastro de subtarefas por tarefa |
| `glpi_plugin_taskmaster_implementations` | Implantações |
| `glpi_plugin_taskmaster_implementations_modules` | Vínculo implantação ↔ módulo |
| `glpi_plugin_taskmaster_implementationtasks` | Instâncias de tarefas por implantação |
| `glpi_plugin_taskmaster_implementationsubtasks` | Instâncias de subtarefas por implantação |

---

## 📝 Histórico de Versões

| Versão | Data | Alterações |
|---|---|---|
| **1.1.0** | Abr/2026 | Progresso por módulo na tela de acompanhamento; **relatório dedicado de impressão/PDF** com progresso geral e por módulo em ordem alfabética; proteção contra exclusão com dependências; permissões granulares por perfil; reparação retroativa de vínculos; status "Não optante" com observação obrigatória; suporte a adição/remoção de módulos pós-criação |
| **1.0.0** | Abr/2026 | Versão inicial: CRUD de módulos, tarefas e subtarefas; criação de implantações com geração automática de tarefas; barra de progresso global; listagem paginada |

---

### 📄 Licença

Distribuído sob a licença **GPLv2+**. Contribuições são bem-vindas!

---

*Desenvolvido por **Wilter P. Porto** com foco em excelência e performance.*
