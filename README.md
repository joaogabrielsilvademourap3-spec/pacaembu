# Pacaembu OS (PHP)

Sistema completo em **PHP + PDO** para gestão de agência social media/web design.

## Recursos principais
- Autenticação: login, registro e logout.
- Dashboard com visão de clientes, projetos, tarefas, atrasos e receita.
- CRUD funcional para: clientes, projetos, tarefas, calendário editorial, website projects, métricas, finanças, arquivos, aprovações, relatórios, logs de IA.
- Busca global.
- Exportação de relatório em PDF.
- Controle básico de papel (ex.: exclusão de cliente restrita a Admin).
- Layout responsivo no estilo Windows 7/8.

## Instalação em Hostinger / HostGator (cPanel)
1. Envie os arquivos para `public_html` (ou subpasta).
2. Garanta PHP 8.1+ habilitado.
3. Acesse `https://seu-dominio.com/install.php`.
4. Escolha SQLite (rápido) ou MySQL.
5. Informe credenciais de admin.
6. Após concluir, entre em `index.php?page=login`.

## Banco de dados
O instalador cria automaticamente todas as tabelas do arquivo `php_app/schema.sql`.

## Estrutura
- `index.php`: aplicação principal (roteamento e módulos).
- `install.php`: instalador web.
- `php_app/schema.sql`: schema completo.
- `static/style.css`: tema e responsividade.
- `uploads/`: arquivos enviados.
- `storage/`: SQLite local (quando escolhido).

## Observação de segurança
Após instalar, remova acesso público a `install.php` (ex.: bloqueio via `.htaccess`) para produção.
