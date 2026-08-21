<?php
declare(strict_types=1);

$pdo = new PDO(
    'mysql:host=localhost;dbname=curso_exemplo;charset=utf8mb4',
    'curso',
    'curso-local',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$products = $pdo->query('SELECT id, name, category FROM products ORDER BY id')->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exemplo LAMP</title>
</head>
<body>
    <h1>Exemplo LAMP</h1>
    <p>Apache, PHP, MariaDB, phpMyAdmin e FTP instalados diretamente na VM Vagrant.</p>
    <p><a href="/phpmyadmin">Abrir phpMyAdmin</a></p>
    <p>FTP local: host <code>localhost</code>, porta <code>2121</code>, usuario <code>vagrant</code>.</p>
    <table>
        <thead><tr><th>ID</th><th>Nome</th><th>Categoria</th></tr></thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= htmlspecialchars((string)$product['id'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>