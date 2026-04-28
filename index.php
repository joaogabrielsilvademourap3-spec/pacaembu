<?php
session_start();

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    header('Location: install.php');
    exit;
}

$config = require $configFile;
$pdo = new PDO(
    $config['dsn'],
    $config['db_user'] ?? null,
    $config['db_pass'] ?? null,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

function h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash(?string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $msg = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $msg;
}

function authRequired(): void {
    if (empty($_SESSION['user'])) {
        header('Location: ?page=login');
        exit;
    }
}

function roleRequired(array $roles): void {
    if (!in_array($_SESSION['user']['role'] ?? '', $roles, true)) {
        flash('Você não tem permissão para esta ação.');
        header('Location: ?page=dashboard');
        exit;
    }
}

function simplePdf(string $content, string $filename = 'report.pdf'): void {
    $content = str_replace(["\r", "\n"], [' ', ' '], $content);
    $stream = "BT /F1 12 Tf 50 760 Td (" . addcslashes($content, "()\\") . ") Tj ET";

    $pdf = "%PDF-1.4\n";
    $objects = [
        "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
        "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
        "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n",
        "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n",
        "5 0 obj << /Length " . strlen($stream) . " >> stream\n{$stream}\nendstream endobj\n",
    ];

    $offsets = [0];
    foreach ($objects as $object) {
        $offsets[] = strlen($pdf);
        $pdf .= $object;
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 6\n0000000000 65535 f \n";
    for ($i = 1; $i <= 5; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer << /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename=' . $filename);
    echo $pdf;
    exit;
}

function fetchAll(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchOne(PDO $pdo, string $sql, array $params = []): ?array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

$page = $_GET['page'] ?? 'dashboard';

if ($page === 'logout') {
    session_destroy();
    header('Location: ?page=login');
    exit;
}

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $user = fetchOne($pdo, 'SELECT * FROM users WHERE email = ?', [$email]);
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        header('Location: ?page=dashboard');
        exit;
    }
    flash('Credenciais inválidas.');
    header('Location: ?page=login');
    exit;
}

if ($page === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)');
    $stmt->execute([
        trim($_POST['name'] ?? ''),
        strtolower(trim($_POST['email'] ?? '')),
        password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT),
        $_POST['role'] ?? 'Admin',
    ]);
    flash('Usuário criado com sucesso.');
    header('Location: ?page=login');
    exit;
}

if (in_array($page, ['login', 'register'], true)) {
    $msg = flash();
    ?>
    <!doctype html>
    <html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pacaembu OS</title>
        <link rel="stylesheet" href="static/style.css">
    </head>
    <body class="auth-body">
        <div class="auth-card">
            <h2><?= $page === 'login' ? 'Login' : 'Registro' ?> — Pacaembu OS</h2>
            <?php if ($msg): ?><div class="alert"><?= h($msg) ?></div><?php endif; ?>
            <form method="post" class="grid">
                <?php if ($page === 'register'): ?>
                    <input name="name" placeholder="Nome" required>
                    <input name="email" placeholder="Email" type="email" required>
                    <input name="password" placeholder="Senha" type="password" required>
                    <select name="role">
                        <option>Admin</option><option>Designer</option><option>Social media manager</option>
                        <option>Copywriter</option><option>Developer</option><option>Client</option>
                    </select>
                    <button type="submit">Criar conta</button>
                <?php else: ?>
                    <input name="email" placeholder="Email" type="email" required>
                    <input name="password" placeholder="Senha" type="password" required>
                    <button type="submit">Entrar</button>
                <?php endif; ?>
            </form>
            <a href="?page=<?= $page === 'login' ? 'register' : 'login' ?>">
                <?= $page === 'login' ? 'Criar conta' : 'Já tenho conta' ?>
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

authRequired();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($page) {
        case 'clients_create':
            $pdo->prepare('INSERT INTO clients (company_name,contact_name,whatsapp,email,website,instagram,facebook,tiktok,linkedin,service_plan,monthly_fee,status,internal_notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([
                    $_POST['company_name'], $_POST['contact_name'], $_POST['whatsapp'], $_POST['email'],
                    $_POST['website'], $_POST['instagram'], $_POST['facebook'], $_POST['tiktok'],
                    $_POST['linkedin'], $_POST['service_plan'], (float) ($_POST['monthly_fee'] ?? 0),
                    $_POST['status'], $_POST['internal_notes'],
                ]);
            flash('Cliente criado.');
            header('Location: ?page=clients');
            exit;
        case 'clients_delete':
            roleRequired(['Admin']);
            $pdo->prepare('DELETE FROM clients WHERE id = ?')->execute([(int) $_POST['id']]);
            flash('Cliente excluído.');
            header('Location: ?page=clients');
            exit;
        case 'projects_create':
            $pdo->prepare('INSERT INTO projects (client_id,name,category,status,priority,deadline,responsible_user,comments,checklist) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([
                    $_POST['client_id'], $_POST['name'], $_POST['category'], $_POST['status'], $_POST['priority'],
                    $_POST['deadline'], $_POST['responsible_user'], $_POST['comments'], $_POST['checklist'],
                ]);
            flash('Projeto salvo.');
            header('Location: ?page=projects');
            exit;
        case 'tasks_create':
            $pdo->prepare('INSERT INTO tasks (client_id,project_id,title,description,status,priority,responsible_user,due_date,tags) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([
                    $_POST['client_id'] ?: null, $_POST['project_id'] ?: null, $_POST['title'],
                    $_POST['description'], $_POST['status'], $_POST['priority'], $_POST['responsible_user'],
                    $_POST['due_date'], $_POST['tags'],
                ]);
            flash('Tarefa criada.');
            header('Location: ?page=tasks&view=' . urlencode($_GET['view'] ?? 'table'));
            exit;
        case 'content_create':
            $pdo->prepare('INSERT INTO content_calendar (client_id,platform,content_type,publish_date,caption,creative_briefing,references_link,approval_status,attached_file) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([
                    $_POST['client_id'], $_POST['platform'], $_POST['content_type'], $_POST['publish_date'],
                    $_POST['caption'], $_POST['creative_briefing'], $_POST['references_link'],
                    $_POST['approval_status'], $_POST['attached_file'],
                ]);
            flash('Conteúdo cadastrado.');
            header('Location: ?page=content');
            exit;
        case 'website_create':
            $pdo->prepare('INSERT INTO website_projects (client_id,domain,hosting_provider,cms_platform,admin_url,project_stage,seo_checklist,performance_checklist,security_checklist,backup_status,maintenance_notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([
                    $_POST['client_id'], $_POST['domain'], $_POST['hosting_provider'], $_POST['cms_platform'],
                    $_POST['admin_url'], $_POST['project_stage'], $_POST['seo_checklist'],
                    $_POST['performance_checklist'], $_POST['security_checklist'], $_POST['backup_status'],
                    $_POST['maintenance_notes'],
                ]);
            flash('Website job salvo.');
            header('Location: ?page=website');
            exit;
        case 'metrics_create':
            $pdo->prepare('INSERT INTO metrics (client_id,month,followers,reach,impressions,engagement,clicks,leads,conversions,website_traffic) VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute([
                    $_POST['client_id'], $_POST['month'], $_POST['followers'], $_POST['reach'], $_POST['impressions'],
                    $_POST['engagement'], $_POST['clicks'], $_POST['leads'], $_POST['conversions'],
                    $_POST['website_traffic'],
                ]);
            flash('Métricas salvas.');
            header('Location: ?page=metrics');
            exit;
        case 'payments_create':
            $pdo->prepare('INSERT INTO payments (client_id,kind,amount,due_date,status,notes) VALUES (?,?,?,?,?,?)')
                ->execute([$_POST['client_id'], $_POST['kind'], $_POST['amount'], $_POST['due_date'], $_POST['status'], $_POST['notes']]);
            flash('Pagamento registrado.');
            header('Location: ?page=finance');
            exit;
        case 'approvals_create':
            $pdo->prepare('INSERT INTO approvals (client_id,item_type,item_id,status,notes) VALUES (?,?,?,?,?)')
                ->execute([$_POST['client_id'], $_POST['item_type'], $_POST['item_id'], $_POST['status'], $_POST['notes']]);
            flash('Aprovação registrada.');
            header('Location: ?page=approvals');
            exit;
        case 'files_upload':
            $path = '';
            $name = '';
            if (!empty($_FILES['file']['name'])) {
                if (!is_dir(__DIR__ . '/uploads')) {
                    mkdir(__DIR__ . '/uploads', 0775, true);
                }
                $name = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $_FILES['file']['name']);
                $path = 'uploads/' . time() . '_' . $name;
                move_uploaded_file($_FILES['file']['tmp_name'], __DIR__ . '/' . $path);
            }
            $pdo->prepare('INSERT INTO files (client_id,project_id,filename,filepath,notes) VALUES (?,?,?,?,?)')
                ->execute([$_POST['client_id'] ?: null, $_POST['project_id'] ?: null, $name, $path, $_POST['notes']]);
            flash('Arquivo enviado.');
            header('Location: ?page=files');
            exit;
        case 'reports_create':
            $clientId = (int) $_POST['client_id'];
            $doneTasks = fetchOne($pdo, 'SELECT COUNT(*) AS total FROM tasks WHERE client_id = ? AND status = "done"', [$clientId])['total'];
            $published = fetchOne($pdo, 'SELECT COUNT(*) AS total FROM content_calendar WHERE client_id = ? AND approval_status = "published"', [$clientId])['total'];
            $summary = "Concluídas: {$doneTasks}; Publicadas: {$published}; Recomendação IA: aumentar frequência de conteúdos com CTA forte.";
            $pdo->prepare('INSERT INTO reports (client_id,month,summary) VALUES (?,?,?)')->execute([$clientId, $_POST['month'], $summary]);
            flash('Relatório gerado.');
            header('Location: ?page=reports');
            exit;
        case 'assistant_ask':
            $overdue = fetchOne($pdo, 'SELECT COUNT(*) AS total FROM tasks WHERE status != "done" AND due_date < date("now")')['total'];
            $pendingPayments = fetchOne($pdo, 'SELECT COUNT(*) AS total FROM payments WHERE status = "pending"')['total'];
            $answer = "Priorize {$overdue} tarefas atrasadas e cobre {$pendingPayments} pagamentos pendentes. Faça revisão de conteúdo de alto engajamento para replicar padrões.";
            $pdo->prepare('INSERT INTO ai_logs (user_id,prompt,response) VALUES (?,?,?)')->execute([$_SESSION['user']['id'], $_POST['prompt'], $answer]);
            flash($answer);
            header('Location: ?page=assistant');
            exit;
    }
}

if ($page === 'report_pdf') {
    $report = fetchOne($pdo, 'SELECT r.*, c.company_name FROM reports r JOIN clients c ON c.id = r.client_id WHERE r.id = ?', [(int) $_GET['id']]);
    if ($report) {
        simplePdf("Pacaembu OS | {$report['company_name']} | {$report['month']} | {$report['summary']}", 'report-' . $report['id'] . '.pdf');
    }
}

if ($page === 'api_metrics') {
    header('Content-Type: application/json');
    echo json_encode(fetchAll($pdo, 'SELECT month, SUM(conversions) conversions, SUM(leads) leads, SUM(reach) reach FROM metrics GROUP BY month ORDER BY month'));
    exit;
}

if ($page === 'api_calendar') {
    header('Content-Type: application/json');
    $events = fetchAll($pdo, 'SELECT cc.id, c.company_name, cc.platform, cc.publish_date, cc.approval_status FROM content_calendar cc JOIN clients c ON c.id = cc.client_id');
    $calendarEvents = array_map(static fn($row) => [
        'title' => $row['company_name'] . ' · ' . $row['platform'] . ' · ' . $row['approval_status'],
        'start' => $row['publish_date'],
    ], $events);
    echo json_encode($calendarEvents);
    exit;
}

$search = trim($_GET['q'] ?? '');
$clients = fetchAll($pdo, 'SELECT * FROM clients ORDER BY id DESC');
$projects = fetchAll($pdo, 'SELECT p.*, c.company_name FROM projects p LEFT JOIN clients c ON c.id = p.client_id ORDER BY p.id DESC');
$tasks = fetchAll($pdo, 'SELECT t.*, c.company_name, p.name AS project_name FROM tasks t LEFT JOIN clients c ON c.id = t.client_id LEFT JOIN projects p ON p.id = t.project_id ORDER BY t.id DESC');
$content = fetchAll($pdo, 'SELECT cc.*, c.company_name FROM content_calendar cc LEFT JOIN clients c ON c.id = cc.client_id ORDER BY publish_date DESC');
$websites = fetchAll($pdo, 'SELECT wp.*, c.company_name FROM website_projects wp LEFT JOIN clients c ON c.id = wp.client_id ORDER BY wp.id DESC');
$metrics = fetchAll($pdo, 'SELECT m.*, c.company_name FROM metrics m LEFT JOIN clients c ON c.id = m.client_id ORDER BY m.id DESC');
$payments = fetchAll($pdo, 'SELECT pay.*, c.company_name FROM payments pay LEFT JOIN clients c ON c.id = pay.client_id ORDER BY pay.id DESC');
$approvals = fetchAll($pdo, 'SELECT a.*, c.company_name FROM approvals a LEFT JOIN clients c ON c.id = a.client_id ORDER BY a.id DESC');
$reports = fetchAll($pdo, 'SELECT r.*, c.company_name FROM reports r LEFT JOIN clients c ON c.id = r.client_id ORDER BY r.id DESC');
$files = fetchAll($pdo, 'SELECT f.*, c.company_name, p.name AS project_name FROM files f LEFT JOIN clients c ON c.id = f.client_id LEFT JOIN projects p ON p.id = f.project_id ORDER BY f.id DESC');
$aiLogs = fetchAll($pdo, 'SELECT * FROM ai_logs ORDER BY id DESC LIMIT 10');

$activeClients = fetchOne($pdo, 'SELECT COUNT(*) AS total FROM clients WHERE status = "active"')['total'];
$activeProjects = fetchOne($pdo, 'SELECT COUNT(*) AS total FROM projects WHERE status IN ("planning", "in progress", "waiting approval", "revision")')['total'];
$dueToday = fetchOne($pdo, 'SELECT COUNT(*) AS total FROM tasks WHERE due_date = date("now")')['total'];
$overdue = fetchOne($pdo, 'SELECT COUNT(*) AS total FROM tasks WHERE due_date < date("now") AND status != "done"')['total'];
$revenue = fetchOne($pdo, 'SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE status != "overdue"')['total'];

$msg = flash();
?><!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pacaembu OS</title>
    <link rel="stylesheet" href="static/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <h3>Pacaembu OS</h3>
        <a href="?page=dashboard">Dashboard</a>
        <a href="?page=clients">Clientes</a>
        <a href="?page=projects">Projetos</a>
        <a href="?page=tasks">Tarefas</a>
        <a href="?page=content">Editorial</a>
        <a href="?page=website">Website Jobs</a>
        <a href="?page=metrics">Métricas</a>
        <a href="?page=reports">Relatórios</a>
        <a href="?page=finance">Financeiro</a>
        <a href="?page=files">Arquivos</a>
        <a href="?page=approvals">Aprovações</a>
        <a href="?page=assistant">Assistente IA</a>
        <a href="?page=logout">Sair</a>
    </aside>
    <main class="content-area">
        <header class="topbar">
            <form>
                <input type="hidden" name="page" value="search">
                <input name="q" placeholder="Busca global" value="<?= h($search) ?>">
                <button type="submit">Buscar</button>
            </form>
            <span><?= h($_SESSION['user']['name']) ?> (<?= h($_SESSION['user']['role']) ?>)</span>
        </header>

        <?php if ($msg): ?><div class="alert"><?= h($msg) ?></div><?php endif; ?>

        <?php if ($page === 'dashboard'): ?>
            <div class="tile-grid">
                <div class="tile"><h4>Clientes ativos</h4><p><?= h($activeClients) ?></p></div>
                <div class="tile"><h4>Projetos ativos</h4><p><?= h($activeProjects) ?></p></div>
                <div class="tile"><h4>Tarefas hoje</h4><p><?= h($dueToday) ?></p></div>
                <div class="tile"><h4>Atrasadas</h4><p><?= h($overdue) ?></p></div>
                <div class="tile"><h4>Receita estimada</h4><p>R$ <?= number_format((float) $revenue, 2, ',', '.') ?></p></div>
            </div>
            <div class="panel">
                <h4>Insights (Chart.js)</h4>
                <canvas id="metricsChart" height="80"></canvas>
            </div>
            <div class="panel">
                <h4>Agenda editorial (FullCalendar)</h4>
                <div id="contentCalendar"></div>
            </div>
        <?php endif; ?>

        <?php if ($page === 'clients'): ?>
            <h2>Clientes</h2>
            <form method="post" action="?page=clients_create" class="panel grid2">
                <input name="company_name" placeholder="Empresa" required>
                <input name="contact_name" placeholder="Contato">
                <input name="whatsapp" placeholder="WhatsApp">
                <input name="email" placeholder="Email">
                <input name="website" placeholder="Website">
                <input name="instagram" placeholder="Instagram">
                <input name="facebook" placeholder="Facebook">
                <input name="tiktok" placeholder="TikTok">
                <input name="linkedin" placeholder="LinkedIn">
                <input name="service_plan" placeholder="Plano de serviço">
                <input name="monthly_fee" type="number" step="0.01" placeholder="Mensalidade">
                <select name="status"><option>active</option><option>paused</option><option>cancelled</option><option>prospect</option></select>
                <textarea name="internal_notes" placeholder="Notas internas"></textarea>
                <button>Salvar cliente</button>
            </form>
            <div class="panel table-wrap">
                <table id="clientsTable">
                    <thead><tr><th>Empresa</th><th>Status</th><th>Plano</th><th>Mensalidade</th><th>Ação</th></tr></thead>
                    <tbody>
                    <?php foreach ($clients as $row): ?>
                        <tr>
                            <td><?= h($row['company_name']) ?></td>
                            <td><?= h($row['status']) ?></td>
                            <td><?= h($row['service_plan']) ?></td>
                            <td><?= h($row['monthly_fee']) ?></td>
                            <td>
                                <form method="post" action="?page=clients_delete" onsubmit="return confirm('Excluir cliente?')">
                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    <button>Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($page === 'projects'): ?>
            <h2>Projetos</h2>
            <form method="post" action="?page=projects_create" class="panel grid2">
                <select name="client_id"><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= h($c['company_name']) ?></option><?php endforeach; ?></select>
                <input name="name" placeholder="Projeto" required>
                <select name="category"><option>social media</option><option>website</option><option>landing page</option><option>branding</option><option>paid traffic</option><option>maintenance</option><option>consulting</option></select>
                <select name="status"><option>planning</option><option>in progress</option><option>waiting approval</option><option>revision</option><option>completed</option><option>cancelled</option></select>
                <select name="priority"><option>low</option><option>medium</option><option>high</option></select>
                <input type="date" name="deadline">
                <input name="responsible_user" placeholder="Responsável">
                <input name="checklist" placeholder="Checklist">
                <textarea name="comments" placeholder="Comentários"></textarea>
                <button>Salvar projeto</button>
            </form>
            <div class="panel table-wrap"><table id="projectsTable"><thead><tr><th>Projeto</th><th>Cliente</th><th>Status</th><th>Prazo</th></tr></thead><tbody><?php foreach ($projects as $row): ?><tr><td><?= h($row['name']) ?></td><td><?= h($row['company_name']) ?></td><td><?= h($row['status']) ?></td><td><?= h($row['deadline']) ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>

        <?php if ($page === 'tasks'): $view = $_GET['view'] ?? 'table'; ?>
            <h2>Tarefas</h2>
            <p><a href="?page=tasks&view=table">Tabela</a> | <a href="?page=tasks&view=kanban">Kanban</a> | <a href="?page=tasks&view=calendar">Calendário</a> | <a href="?page=tasks&view=timeline">Timeline</a></p>
            <form method="post" action="?page=tasks_create&view=<?= h($view) ?>" class="panel grid2">
                <input name="title" placeholder="Título" required>
                <textarea name="description" placeholder="Descrição"></textarea>
                <select name="status"><option>todo</option><option>doing</option><option>done</option><option>blocked</option></select>
                <select name="priority"><option>low</option><option>medium</option><option>high</option></select>
                <input name="responsible_user" placeholder="Responsável">
                <input type="date" name="due_date">
                <input name="tags" placeholder="Tags">
                <select name="client_id"><option value="">Sem cliente</option><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= h($c['company_name']) ?></option><?php endforeach; ?></select>
                <select name="project_id"><option value="">Sem projeto</option><?php foreach ($projects as $p): ?><option value="<?= (int) $p['id'] ?>"><?= h($p['name']) ?></option><?php endforeach; ?></select>
                <button>Adicionar tarefa</button>
            </form>

            <?php if ($view === 'kanban'): ?>
                <div class="kanban" id="kanbanRoot">
                    <?php foreach (['todo', 'doing', 'done', 'blocked'] as $status): ?>
                        <div class="panel kanban-col" data-status="<?= h($status) ?>">
                            <h4><?= h($status) ?></h4>
                            <div class="kanban-list">
                                <?php foreach ($tasks as $task): if ($task['status'] !== $status) continue; ?>
                                    <div class="task-chip" data-id="<?= (int) $task['id'] ?>"><?= h($task['title']) ?> · <?= h($task['priority']) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="panel table-wrap"><table id="tasksTable"><thead><tr><th>Título</th><th>Status</th><th>Prioridade</th><th>Prazo</th><th>Cliente</th></tr></thead><tbody><?php foreach ($tasks as $row): ?><tr><td><?= h($row['title']) ?></td><td><?= h($row['status']) ?></td><td><?= h($row['priority']) ?></td><td><?= h($row['due_date']) ?></td><td><?= h($row['company_name']) ?></td></tr><?php endforeach; ?></tbody></table></div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($page === 'content'): ?>
            <h2>Calendário editorial</h2>
            <form method="post" action="?page=content_create" class="panel grid2">
                <select name="client_id"><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= h($c['company_name']) ?></option><?php endforeach; ?></select>
                <input name="platform" placeholder="Plataforma" required>
                <input name="content_type" placeholder="Tipo de conteúdo" required>
                <input type="date" name="publish_date" required>
                <textarea name="caption" placeholder="Legenda"></textarea>
                <input name="creative_briefing" placeholder="Briefing criativo">
                <input name="references_link" placeholder="Referências">
                <select name="approval_status"><option>draft</option><option>internal review</option><option>sent to client</option><option>approved</option><option>changes requested</option><option>published</option></select>
                <input name="attached_file" placeholder="Arquivo/link">
                <button>Salvar conteúdo</button>
            </form>
            <div class="panel table-wrap"><table id="contentTable"><thead><tr><th>Data</th><th>Cliente</th><th>Plataforma</th><th>Status</th></tr></thead><tbody><?php foreach ($content as $row): ?><tr><td><?= h($row['publish_date']) ?></td><td><?= h($row['company_name']) ?></td><td><?= h($row['platform']) ?></td><td><?= h($row['approval_status']) ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>

        <?php if ($page === 'website'): ?>
            <h2>Website jobs</h2>
            <form method="post" action="?page=website_create" class="panel grid2">
                <select name="client_id"><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= h($c['company_name']) ?></option><?php endforeach; ?></select>
                <input name="domain" placeholder="Domínio">
                <input name="hosting_provider" placeholder="Hospedagem">
                <input name="cms_platform" placeholder="CMS/plataforma">
                <input name="admin_url" placeholder="Admin URL">
                <input name="project_stage" placeholder="Etapa">
                <input name="seo_checklist" placeholder="Checklist SEO">
                <input name="performance_checklist" placeholder="Checklist Performance">
                <input name="security_checklist" placeholder="Checklist Segurança">
                <input name="backup_status" placeholder="Status backup">
                <textarea name="maintenance_notes" placeholder="Notas manutenção"></textarea>
                <button>Salvar</button>
            </form>
            <div class="panel table-wrap"><table id="websiteTable"><thead><tr><th>Cliente</th><th>Domínio</th><th>CMS</th><th>Etapa</th></tr></thead><tbody><?php foreach ($websites as $row): ?><tr><td><?= h($row['company_name']) ?></td><td><?= h($row['domain']) ?></td><td><?= h($row['cms_platform']) ?></td><td><?= h($row['project_stage']) ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>

        <?php if ($page === 'metrics'): ?>
            <h2>Métricas</h2>
            <form method="post" action="?page=metrics_create" class="panel grid2">
                <select name="client_id"><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= h($c['company_name']) ?></option><?php endforeach; ?></select>
                <input type="month" name="month" required>
                <input type="number" name="followers" placeholder="Followers">
                <input type="number" name="reach" placeholder="Reach">
                <input type="number" name="impressions" placeholder="Impressions">
                <input type="number" name="engagement" placeholder="Engagement">
                <input type="number" name="clicks" placeholder="Clicks">
                <input type="number" name="leads" placeholder="Leads">
                <input type="number" name="conversions" placeholder="Conversions">
                <input type="number" name="website_traffic" placeholder="Website traffic">
                <button>Salvar métricas</button>
            </form>
            <div class="panel table-wrap"><table id="metricsTable"><thead><tr><th>Mês</th><th>Cliente</th><th>Followers</th><th>Reach</th><th>Conversions</th></tr></thead><tbody><?php foreach ($metrics as $row): ?><tr><td><?= h($row['month']) ?></td><td><?= h($row['company_name']) ?></td><td><?= h($row['followers']) ?></td><td><?= h($row['reach']) ?></td><td><?= h($row['conversions']) ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>

        <?php if ($page === 'reports'): ?>
            <h2>Relatórios</h2>
            <form method="post" action="?page=reports_create" class="panel grid2">
                <select name="client_id"><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= h($c['company_name']) ?></option><?php endforeach; ?></select>
                <input type="month" name="month" required>
                <button>Gerar relatório</button>
            </form>
            <div class="panel table-wrap"><table id="reportsTable"><thead><tr><th>Cliente</th><th>Mês</th><th>Resumo</th><th>PDF</th></tr></thead><tbody><?php foreach ($reports as $row): ?><tr><td><?= h($row['company_name']) ?></td><td><?= h($row['month']) ?></td><td><?= h($row['summary']) ?></td><td><a href="?page=report_pdf&id=<?= (int) $row['id'] ?>">Exportar</a></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>

        <?php if ($page === 'finance'): ?>
            <h2>Financeiro</h2>
            <form method="post" action="?page=payments_create" class="panel grid2">
                <select name="client_id"><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= h($c['company_name']) ?></option><?php endforeach; ?></select>
                <select name="kind"><option>retainer</option><option>one-time</option></select>
                <input type="number" step="0.01" name="amount" placeholder="Valor">
                <input type="date" name="due_date">
                <select name="status"><option>paid</option><option>pending</option><option>overdue</option></select>
                <textarea name="notes" placeholder="Notas"></textarea>
                <button>Salvar pagamento</button>
            </form>
            <div class="panel table-wrap"><table id="paymentsTable"><thead><tr><th>Cliente</th><th>Tipo</th><th>Valor</th><th>Status</th></tr></thead><tbody><?php foreach ($payments as $row): ?><tr><td><?= h($row['company_name']) ?></td><td><?= h($row['kind']) ?></td><td><?= h($row['amount']) ?></td><td><?= h($row['status']) ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>

        <?php if ($page === 'files'): ?>
            <h2>Arquivos</h2>
            <form method="post" action="?page=files_upload" enctype="multipart/form-data" class="panel grid2">
                <input type="file" name="file" required>
                <select name="client_id"><option value="">Sem cliente</option><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= h($c['company_name']) ?></option><?php endforeach; ?></select>
                <select name="project_id"><option value="">Sem projeto</option><?php foreach ($projects as $p): ?><option value="<?= (int) $p['id'] ?>"><?= h($p['name']) ?></option><?php endforeach; ?></select>
                <input name="notes" placeholder="Notas do arquivo">
                <button>Enviar arquivo</button>
            </form>
            <div class="panel table-wrap"><table id="filesTable"><thead><tr><th>Arquivo</th><th>Cliente</th><th>Projeto</th><th>Preview</th></tr></thead><tbody><?php foreach ($files as $row): ?><tr><td><?= h($row['filename']) ?></td><td><?= h($row['company_name']) ?></td><td><?= h($row['project_name']) ?></td><td><a href="<?= h($row['filepath']) ?>" target="_blank">Abrir</a></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>

        <?php if ($page === 'approvals'): ?>
            <h2>Aprovações</h2>
            <form method="post" action="?page=approvals_create" class="panel grid2">
                <select name="client_id"><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= h($c['company_name']) ?></option><?php endforeach; ?></select>
                <input name="item_type" placeholder="Tipo item">
                <input name="item_id" type="number" placeholder="ID item">
                <select name="status"><option>draft</option><option>internal review</option><option>sent to client</option><option>approved</option><option>changes requested</option><option>published</option></select>
                <textarea name="notes" placeholder="Notas"></textarea>
                <button>Registrar aprovação</button>
            </form>
            <div class="panel table-wrap"><table id="approvalsTable"><thead><tr><th>Cliente</th><th>Item</th><th>Status</th></tr></thead><tbody><?php foreach ($approvals as $row): ?><tr><td><?= h($row['company_name']) ?></td><td><?= h($row['item_type']) ?> #<?= h($row['item_id']) ?></td><td><?= h($row['status']) ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>

        <?php if ($page === 'assistant'): ?>
            <h2>Assistente IA</h2>
            <form method="post" action="?page=assistant_ask" class="panel grid">
                <textarea name="prompt" placeholder="Ex: Em que devo focar hoje?" required></textarea>
                <button>Analisar</button>
            </form>
            <div class="panel"><h4>Histórico</h4><ul><?php foreach ($aiLogs as $log): ?><li><strong><?= h($log['prompt']) ?></strong><br><?= h($log['response']) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <?php if ($page === 'search'): ?>
            <h2>Busca global: <?= h($search) ?></h2>
            <?php
            $resultClients = fetchAll($pdo, 'SELECT company_name as label, "Cliente" as type FROM clients WHERE company_name LIKE ?', ["%{$search}%"]);
            $resultProjects = fetchAll($pdo, 'SELECT name as label, "Projeto" as type FROM projects WHERE name LIKE ?', ["%{$search}%"]);
            $resultTasks = fetchAll($pdo, 'SELECT title as label, "Tarefa" as type FROM tasks WHERE title LIKE ?', ["%{$search}%"]);
            $results = array_merge($resultClients, $resultProjects, $resultTasks);
            ?>
            <div class="panel table-wrap"><table id="searchTable"><thead><tr><th>Tipo</th><th>Resultado</th></tr></thead><tbody><?php foreach ($results as $r): ?><tr><td><?= h($r['type']) ?></td><td><?= h($r['label']) ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    const tableIds = ['clientsTable','projectsTable','tasksTable','contentTable','websiteTable','metricsTable','reportsTable','paymentsTable','filesTable','approvalsTable','searchTable'];
    tableIds.forEach(id => { const el = document.getElementById(id); if (el) new DataTable(el); });

    if (document.getElementById('metricsChart')) {
        fetch('?page=api_metrics').then(r => r.json()).then(rows => {
            const ctx = document.getElementById('metricsChart');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: rows.map(r => r.month),
                    datasets: [
                        {label: 'Conversões', data: rows.map(r => r.conversions), borderColor: '#1d5fa7'},
                        {label: 'Leads', data: rows.map(r => r.leads), borderColor: '#3eb7d4'},
                        {label: 'Reach', data: rows.map(r => r.reach), borderColor: '#0b1e3b'}
                    ]
                }
            });
        });
    }

    if (document.getElementById('contentCalendar')) {
        fetch('?page=api_calendar').then(r => r.json()).then(events => {
            const calendar = new FullCalendar.Calendar(document.getElementById('contentCalendar'), {
                initialView: 'dayGridMonth',
                events
            });
            calendar.render();
        });
    }

    document.querySelectorAll('.kanban-list').forEach(list => {
        new Sortable(list, { group: 'kanban', animation: 120 });
    });
</script>
</body>
</html>
