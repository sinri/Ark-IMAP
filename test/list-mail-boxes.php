<?php

use sinri\ark\imap\ArkImapWorker;

require_once __DIR__ . '/../src/autoload.php';

// To TESTERS: it is a sample format, override it in the next required file
$config = [
    "username" => 'name@doma.in',
    "password" => 'password',
    "host" => 'imap.doma.in',
    "port" => 993,
];
require __DIR__ . '/../debug/0.php';

$IE = new ArkImapWorker(
    $config['host'],
    $config['port'],
    $config['username'],
    $config['password']
);

$mailBoxes = $IE->listMailBoxes();
foreach ($mailBoxes as $mailBox) {
    echo "> " . json_encode($mailBox) . PHP_EOL;
}
