<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.local.php';
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['name']),
    $config['user'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$products = $pdo->query('SELECT id, name, category FROM products ORDER BY id')->fetchAll();
$ftpSocket = @fsockopen('127.0.0.1', 21, $ftpError, $ftpMessage, 1);
$ftpOnline = is_resource($ftpSocket);
if (is_resource($ftpSocket)) {
    fclose($ftpSocket);
}
$phpmyadminHeaders = @get_headers('http://127.0.0.1/phpmyadmin', true, stream_context_create([
    'http' => ['timeout' => 1]
]));
$phpmyadminOnline = is_array($phpmyadminHeaders) && count($phpmyadminHeaders) > 0;
$services = [
    ['name' => 'Apache + PHP', 'service' => 'apache2', 'port' => '55080', 'state' => 'online', 'detail' => 'Esta página foi renderizada pelo servidor web.'],
    ['name' => 'MariaDB', 'service' => 'mariadb', 'port' => '3306', 'state' => 'online', 'detail' => 'PDO executou SELECT em curso_exemplo.products.'],
    ['name' => 'phpMyAdmin', 'service' => '', 'port' => '55080', 'state' => $phpmyadminOnline ? 'online' : 'offline', 'detail' => $phpmyadminOnline ? 'Resposta HTTP recebida em /phpmyadmin.' : 'Nenhuma resposta HTTP recebida em /phpmyadmin.'],
    ['name' => 'FTP / vsftpd', 'service' => 'vsftpd', 'port' => '55021', 'state' => $ftpOnline ? 'online' : 'offline', 'detail' => $ftpOnline ? 'Socket local respondeu na porta 21.' : 'Socket local não respondeu na porta 21.']
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>vrampp | laboratório DevOps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --ink: #172331; --muted: #657384; --paper: #f5f1e8; --card: #fffdf8; --teal: #087f8c; --coral: #e76f51; --line: #d9d4ca; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--paper); font-family: Georgia, 'Times New Roman', serif; }
        a { color: inherit; }
        .shell { max-width: 1120px; margin: 0 auto; padding: 28px 24px 64px; }
        .topbar { display: flex; justify-content: space-between; gap: 24px; align-items: center; font: 700 13px/1.2 Arial, sans-serif; letter-spacing: .08em; text-transform: uppercase; }
        .mark { color: var(--teal); }
        .links { display: flex; gap: 18px; color: var(--muted); }
        .links a { text-decoration: none; }
        .hero { display: grid; grid-template-columns: 1.2fr .8fr; gap: 48px; align-items: end; padding: 92px 0 72px; }
        .eyebrow { color: var(--coral); font: 700 12px/1.2 Arial, sans-serif; letter-spacing: .16em; text-transform: uppercase; }
        h1 { max-width: 680px; margin: 14px 0 20px; font-size: clamp(3.2rem, 8vw, 6.8rem); line-height: .88; letter-spacing: -.04em; }
        .intro { max-width: 560px; margin: 0; color: var(--muted); font-size: 1.2rem; line-height: 1.55; }
        .hero-note { border-top: 3px solid var(--ink); padding-top: 16px; font: 700 14px/1.5 Arial, sans-serif; }
        .hero-note strong { display: block; color: var(--teal); font-size: 2.4rem; line-height: 1; }
        .band { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; margin: 0 0 72px; background: var(--line); border: 1px solid var(--line); }
        .stat { min-height: 130px; padding: 22px; background: var(--card); }
        .stat b { display: block; margin-bottom: 10px; color: var(--teal); font: 700 1.8rem/1 Arial, sans-serif; }
        .stat span { color: var(--muted); font: 700 12px/1.4 Arial, sans-serif; text-transform: uppercase; }
        .content { display: grid; grid-template-columns: .8fr 1.2fr; gap: 56px; }
        h2 { margin: 0 0 14px; font-size: 2.3rem; line-height: 1; }
        .copy { color: var(--muted); font-size: 1.05rem; line-height: 1.6; }
        .stack { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .stack-item { padding: 18px; background: var(--card); border-left: 4px solid var(--teal); }
        .stack-item b { display: block; margin-bottom: 5px; font: 700 14px Arial, sans-serif; }
        .stack-item span { color: var(--muted); font: 13px/1.4 Arial, sans-serif; }
        .status-section { margin-top: 72px; padding: 28px; color: #fff; background: var(--ink); }
        .status-section h2 { color: #fff; }
        .status-intro { color: #b7c3ce; font: 14px/1.5 Arial, sans-serif; }
        .status-card { height: 100%; padding: 20px; color: var(--ink); background: var(--card); border: 0; border-radius: 0; }
        .status-led { display: inline-block; width: 11px; height: 11px; margin-right: 8px; border-radius: 50%; background: #26a269; box-shadow: 0 0 0 4px rgba(38, 162, 105, .16); }
        .status-led.offline { background: #d64545; box-shadow: 0 0 0 4px rgba(214, 69, 69, .16); }
        .status-name { font: 700 16px/1.2 Arial, sans-serif; }
        .status-meta { margin-top: 12px; color: var(--muted); font: 12px/1.5 Arial, sans-serif; }
        .status-port { display: inline-block; padding: 4px 8px; color: var(--teal); background: #e6f2f1; font: 700 11px Arial, sans-serif; }
        .status-proof { margin: 8px 0 0; color: var(--muted); font: 12px/1.4 Arial, sans-serif; }
        .service-actions { display: flex; gap: 6px; margin-top: 16px; }
        .service-actions button { border: 1px solid var(--line); padding: 6px 9px; color: var(--ink); background: transparent; font: 700 10px Arial, sans-serif; text-transform: uppercase; }
        .service-actions button:hover { color: #fff; background: var(--teal); }
        .license { margin-top: 20px; color: #b7c3ce; font: 12px Arial, sans-serif; }
        .data { margin-top: 72px; padding-top: 24px; border-top: 1px solid var(--line); }
        .data-head { display: flex; justify-content: space-between; gap: 20px; align-items: end; margin-bottom: 18px; }
        .data-head p { margin: 0; color: var(--muted); font: 13px Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; background: var(--card); font: 15px Arial, sans-serif; }
        th, td { padding: 15px 16px; border-bottom: 1px solid var(--line); text-align: left; }
        th { color: var(--muted); font-size: 11px; letter-spacing: .1em; text-transform: uppercase; }
        footer { display: flex; justify-content: space-between; gap: 20px; margin-top: 48px; color: var(--muted); font: 12px/1.5 Arial, sans-serif; }
        @media (max-width: 760px) { .hero, .content { grid-template-columns: 1fr; gap: 30px; } .hero { padding: 62px 0 48px; } .band { grid-template-columns: 1fr 1fr; margin-bottom: 48px; } .links { gap: 10px; } .stack { grid-template-columns: 1fr; } .data-head, footer { display: block; } .data-head p { margin-top: 8px; } footer span { display: block; margin-top: 8px; } }
    </style>
</head>
<body>
<main class="shell">
    <nav class="topbar"><span class="mark">vrampp / 01</span><span class="links"><a href="/phpmyadmin">phpMyAdmin</a><a href="ftp://localhost:55021">FTP local</a></span></nav>
    <header class="hero">
        <div><span class="eyebrow">Laboratório DevOps do Prof. Rold Jr.</span><h1>Infraestrutura que dá para ver.</h1><p class="intro">Uma VM Ubuntu, uma stack LAMP e uma pequena consulta ao banco. O primeiro passo do vrampp é tornar cada camada concreta antes de levar o projeto para containers.</p></div>
        <div class="hero-note"><strong>Vagrant</strong>Uma entrega reproduzível com Apache, PHP, MariaDB, phpMyAdmin e FTP instalados no guest.</div>
    </header>
    <section class="band" aria-label="Resumo da infraestrutura"><div class="stat"><b>01</b><span>versão do laboratório</span></div><div class="stat"><b>5</b><span>serviços instalados</span></div><div class="stat"><b>VM</b><span>Ubuntu Jammy local</span></div><div class="stat"><b>55</b><span>prefixo das portas</span></div></section>
    <section class="content"><div><span class="eyebrow">O que está funcionando</span><h2>Uma stack pequena, mas inteira.</h2><p class="copy">Esta página é também o teste integrado: o Apache serve o arquivo, o PHP executa, o PDO consulta o MariaDB e os registros aparecem abaixo. O resultado é simples de inspecionar e fácil de recriar.</p></div><div class="stack"><div class="stack-item"><b>Vagrant</b><span>cria e controla a VM</span></div><div class="stack-item"><b>Apache + PHP</b><span>entregam a aplicação web</span></div><div class="stack-item"><b>MariaDB</b><span>guarda dados reais de exemplo</span></div><div class="stack-item"><b>phpMyAdmin + FTP</b><span>apoiam inspeção e arquivos locais</span></div></div></section>
    <section id="service-app" class="status-section" aria-labelledby="status-title">
        <span class="eyebrow">Prova de funcionamento</span>
        <h2 id="status-title">Status dos serviços</h2>
        <p class="status-intro">Os indicadores abaixo foram montados pelo PHP no momento desta resposta e atualizados pelo Vue.</p>
        <div class="row g-3">
            <div v-for="service in services" :key="service.name" class="col-12 col-md-6 col-xl-3">
                <article class="status-card">
                    <div><span class="status-led" :class="{ offline: service.state !== 'online' }"></span><span class="status-name">{{ service.name }}</span></div>
                    <div class="status-meta">Porta <span class="status-port">{{ service.port }}</span></div>
                    <p class="status-proof">{{ service.detail }}</p>
                    <div v-if="service.service" class="service-actions">
                        <button type="button" @click="control(service.service, 'start')">Subir</button>
                        <button type="button" @click="control(service.service, 'stop')">Descer</button>
                        <button type="button" @click="control(service.service, 'restart')">Reiniciar</button>
                    </div>
                </article>
            </div>
        </div>
        <p class="license">MIT License · Prof. Rold Jr. · prof.roldjunior@gmail.com</p>
    </section>
    <section class="data"><div class="data-head"><div><span class="eyebrow">Consulta executada agora</span><h2>Produtos no banco</h2></div><p>PDO + SELECT em <strong>curso_exemplo.products</strong></p></div><table><thead><tr><th>ID</th><th>Nome</th><th>Categoria</th></tr></thead><tbody><?php foreach ($products as $product): ?><tr><td><?= htmlspecialchars((string)$product['id'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></section>
    <footer><span>Primeira entrega: VM tradicional antes dos containers.</span><span>HTTP :55080 · FTP :55021 · <a href="/phpmyadmin">inspecionar banco</a></span></footer>
</main>
<script src="https://cdn.jsdelivr.net/npm/vue@3.5.13/dist/vue.global.prod.min.js"></script>
<script>
Vue.createApp({
    data: () => ({ services: <?= json_encode($services, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> }),
    methods: {
        async refresh() {
            const response = await fetch('/api/services.php');
            const result = await response.json();
            if (response.ok) this.services = result.services;
        },
        async control(service, action) {
            await fetch(`/api/services.php?service=${encodeURIComponent(service)}&action=${action}`);
            await this.refresh();
        }
    },
    mounted() {
        this.refresh();
        window.setInterval(() => this.refresh(), 10000);
    }
}).mount('#service-app');
</script>
</body>
</html>