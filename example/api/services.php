<?php
declare(strict_types=1);

$allowedServices = [
    'apache2' => ['label' => 'Apache + PHP', 'port' => '55080'],
    'mariadb' => ['label' => 'MariaDB', 'port' => '3306'],
    'vsftpd' => ['label' => 'FTP / vsftpd', 'port' => '55021'],
];
$action = $_GET['action'] ?? 'status';
$service = $_GET['service'] ?? '';

if ($action !== 'status' && (!isset($allowedServices[$service]) || !in_array($action, ['start', 'stop', 'restart'], true))) {
    http_response_code(400);
    echo json_encode(['error' => 'Servico ou acao nao permitidos.']);
    exit;
}

if ($action !== 'status') {
    $command = sprintf('sudo -n /usr/local/sbin/vrampp-service %s %s 2>&1', escapeshellarg($action), escapeshellarg($service));
    exec($command, $output, $exitCode);
    if ($exitCode !== 0) {
        http_response_code(500);
        echo json_encode(['error' => implode("\n", $output)]);
        exit;
    }
}

$services = [];
foreach ($allowedServices as $name => $definition) {
    $output = [];
    exec('systemctl is-active ' . escapeshellarg($name) . ' 2>/dev/null', $output, $exitCode);
    $online = $exitCode === 0 && ($output[0] ?? '') === 'active';
    $services[] = [
        'name' => $definition['label'],
        'service' => $name,
        'port' => $definition['port'],
        'state' => $online ? 'online' : 'offline',
        'detail' => $online ? 'systemd informa active.' : 'systemd informa ' . ($output[0] ?? 'inactive') . '.',
    ];
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['services' => $services], JSON_UNESCAPED_UNICODE);
