<?php
session_start();

$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    header('Location: index.php?page=login');
    exit;
}

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = $_POST['driver'] ?? 'sqlite';
    $adminName = trim($_POST['admin_name'] ?? '');
    $adminEmail = strtolower(trim($_POST['admin_email'] ?? ''));
    $adminPassword = $_POST['admin_password'] ?? '';
    $seedDemo = isset($_POST['seed_demo']);

    try {
        if ($driver === 'mysql') {
            $host = trim($_POST['db_host'] ?? 'localhost');
            $port = trim($_POST['db_port'] ?? '3306');
            $dbName = trim($_POST['db_name'] ?? '');
            $dbUser = trim($_POST['db_user'] ?? '');
            $dbPass = $_POST['db_pass'] ?? '';
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
        } else {
            $storageDir = __DIR__ . '/storage';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0775, true);
            }
            $dsn = 'sqlite:' . $storageDir . '/pacaembu.sqlite';
            $dbUser = null;
            $dbPass = null;
        }

        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $schema = file_get_contents(__DIR__ . '/php_app/schema.sql');
        $pdo->exec($schema);

        $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)');
        $stmt->execute([$adminName, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT), 'Admin']);

        if ($seedDemo) {
            $pdo->exec("INSERT INTO clients (company_name,contact_name,email,service_plan,monthly_fee,status) VALUES ('Blue Harbor Dental','Amanda','client@example.com','Growth',2500,'active')");
            $clientId = (int) $pdo->lastInsertId();
            $pdo->exec("INSERT INTO projects (client_id,name,category,status,priority,deadline) VALUES ({$clientId},'Campanha Abril','social media','in progress','high',date('now'))");
            $projectId = (int) $pdo->lastInsertId();
            $pdo->exec("INSERT INTO tasks (client_id,project_id,title,status,priority,due_date) VALUES ({$clientId},{$projectId},'Publicar carrossel semanal','doing','high',date('now'))");
            $pdo->exec("INSERT INTO payments (client_id,kind,amount,due_date,status,notes) VALUES ({$clientId},'retainer',2500,date('now'),'pending','Contrato mensal')");
        }

        $secret = bin2hex(random_bytes(24));
        $config = "<?php\nreturn [\n" .
            "    'app_name' => 'Pacaembu OS',\n" .
            "    'secret_key' => '{$secret}',\n" .
            "    'dsn' => '" . addslashes($dsn) . "',\n" .
            "    'db_user' => " . ($dbUser ? "'" . addslashes($dbUser) . "'" : 'null') . ",\n" .
            "    'db_pass' => " . ($dbPass ? "'" . addslashes($dbPass) . "'" : 'null') . "\n" .
            "];\n";

        file_put_contents($configPath, $config);
        $message = 'Instalação concluída! Agora faça login.';
    } catch (Throwable $e) {
        $message = 'Erro: ' . $e->getMessage();
    }
}
?><!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalador Pacaembu OS</title>
    <link rel="stylesheet" href="static/style.css">
</head>
<body class="auth-body">
<div class="auth-card">
    <h2>Instalador Pacaembu OS (HostGator Ready)</h2>
    <p>Configure banco e usuário admin para publicar rapidamente no cPanel.</p>
    <?php if ($message): ?><div class="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post" class="grid">
        <select name="driver" id="driver" onchange="document.getElementById('mysqlBox').style.display=this.value==='mysql'?'grid':'none'">
            <option value="sqlite">SQLite (rápido)</option>
            <option value="mysql">MySQL (HostGator)</option>
        </select>
        <div id="mysqlBox" class="grid" style="display:none">
            <input name="db_host" placeholder="MySQL host">
            <input name="db_port" placeholder="Porta" value="3306">
            <input name="db_name" placeholder="Nome do banco">
            <input name="db_user" placeholder="Usuário do banco">
            <input name="db_pass" placeholder="Senha do banco" type="password">
        </div>
        <input name="admin_name" placeholder="Nome do admin" required>
        <input name="admin_email" placeholder="Email do admin" type="email" required>
        <input name="admin_password" placeholder="Senha do admin" type="password" required>
        <label><input type="checkbox" name="seed_demo"> Criar dados de demonstração</label>
        <button>Instalar sistema</button>
    </form>
    <p><a href="index.php?page=login">Ir para login</a></p>
</div>
</body>
</html>
