# Pacaembu OS (PHP CRM)

Aplicação CRM completa em PHP para agência de social media + web design, pronta para HostGator.

## Stack
- **Backend**: PHP 8.1+ (PDO, sessões, rotas por página).
- **Banco**: SQLite ou MySQL (via instalador).
- **Frontend**: HTML/CSS/JS responsivo (Windows 7/8 hybrid).

## Plugins Open Source (GitHub) integrados
- DataTables — tabelas avançadas (busca, paginação, ordenação): https://github.com/DataTables/DataTables
- Chart.js — gráficos de performance: https://github.com/chartjs/Chart.js
- FullCalendar — calendário editorial: https://github.com/fullcalendar/fullcalendar
- SortableJS — arrastar e organizar cards no kanban: https://github.com/SortableJS/Sortable

## Módulos incluídos
- Login/registro/logout, sessão persistente.
- Dashboard com KPIs e widgets.
- Clientes (CRUD).
- Projetos (CRUD).
- Tarefas com views tabela/kanban/calendário/timeline.
- Calendário editorial com status de aprovação.
- Website manager.
- Métricas + insights + gráficos.
- Relatórios mensais + exportação PDF.
- Financeiro (retainer, one-time, status).
- Gerenciador de arquivos.
- Aprovações.
- Assistente IA (priorização/sugestões + logs).
- Busca global.

## Instalar no HostGator (cPanel)
1. Faça upload dos arquivos para `public_html`.
2. Verifique PHP 8.1+ habilitado.
3. Acesse: `https://SEU-DOMINIO/install.php`.
4. Escolha SQLite (rápido) ou MySQL (produção).
5. Crie usuário admin e finalize.
6. Entre em: `index.php?page=login`.

## Estrutura
- `index.php` → app principal (frontend + backend + APIs internas).
- `install.php` → instalador web.
- `php_app/schema.sql` → schema completo do banco.
- `static/style.css` → estilo responsivo.
- `uploads/` → arquivos enviados.
- `storage/` → banco SQLite quando usado.

## Segurança básica
- `config.php` fica bloqueado por `.htaccess`.
- Recomendado: após instalação, bloquear `install.php` via cPanel/htaccess.
