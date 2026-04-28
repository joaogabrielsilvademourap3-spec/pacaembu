<?php
session_start();
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    header('Location: index.php?page=login');
    exit;
}

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = $_POST['driver'];
    $adminName = trim($_POST['admin_name']);
    $adminEmail = strtolower(trim($_POST['admin_email']));
    $adminPass = $_POST['admin_password'];

    if ($driver === 'sqlite') {
        $dbFile = __DIR__ . '/storage/pacaembu.sqlite';
        if (!is_dir(__DIR__ . '/storage')) mkdir(__DIR__ . '/storage', 0775, true);
        $dsn = 'sqlite:' . $dbFile; $dbUser = null; $dbPass = null;
    } else {
        $host = trim($_POST['db_host']); $port = trim($_POST['db_port'] ?: '3306'); $dbName = trim($_POST['db_name']); $dbUser = trim($_POST['db_user']); $dbPass = $_POST['db_pass'];
        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
    }

    try {
        $pdo = new PDO($dsn, $dbUser ?? null, $dbPass ?? null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $schema = file_get_contents(__DIR__ . '/php_app/schema.sql');
        $pdo->exec($schema);
        $stmt = $pdo->prepare('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)');
        $stmt->execute([$adminName, $adminEmail, password_hash($adminPass, PASSWORD_DEFAULT), 'Admin']);

        $secret = bin2hex(random_bytes(16));
        $export = "<?php\nreturn [\n  'app_name' => 'Pacaembu OS',\n  'secret_key' => '{$secret}',\n  'dsn' => '" . addslashes($dsn) . "',\n  'db_user' => " . ($dbUser ? "'".addslashes($dbUser)."'" : 'null') . ",\n  'db_pass' => " . (isset($dbPass) && $dbPass !== '' ? "'".addslashes($dbPass)."'" : 'null') . "\n];\n";
        file_put_contents($configPath, $export);
        $message = 'Instalação concluída. Acesse o login.';
    } catch (Throwable $e) {
        $message = 'Erro de instalação: ' . $e->getMessage();
    }
}
?><!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalador Pacaembu OS</title><link rel="stylesheet" href="static/style.css"></head><body class="auth-body"><div class="auth-card"><h2>Instalador Pacaembu OS (PHP)</h2><?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?><form method="post" class="grid"><select name="driver" id="driver" onchange="document.getElementById('mysql').style.display=this.value==='mysql'?'block':'none';"><option value="sqlite">SQLite (rápido)</option><option value="mysql">MySQL (hosting)</option></select><div id="mysql" style="display:none"><input name="db_host" placeholder="Host"><input name="db_port" placeholder="Porta" value="3306"><input name="db_name" placeholder="Banco"><input name="db_user" placeholder="Usuário"><input name="db_pass" placeholder="Senha" type="password"></div><input name="admin_name" placeholder="Nome admin" required><input name="admin_email" placeholder="Email admin" type="email" required><input name="admin_password" placeholder="Senha admin" type="password" required><button>Instalar sistema</button></form><p><a href="index.php?page=login">Ir para login</a></p></div></body></html>
