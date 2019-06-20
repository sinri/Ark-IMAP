<?php

use sinri\ark\core\ArkLogger;
use sinri\ark\imap\ArkImapMail;
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

$logger = new ArkLogger();

$IE = new ArkImapWorker(
    $config['host'],
    $config['port'],
    $config['username'],
    $config['password'],
    true,
    true
);

$IE->setLogger($logger);

$list = $IE->searchInMailBox(
    "&UXZO1mWHTvZZOQ-/&X6FXzmyz-", // Commonly "INBOX", or like this code for "其他文件夹/御城河"
    ArkImapWorker::createCriteriaWithDateRange(
    //date('d-M-Y', time() - 86400),(date('d-M-Y')) // this is for yesterday
        "17-Jun-2019", "18-Jun-2019" // this is for your set
    ),
    /**
     * @param $imapStream
     * @param $messageUID
     * @param ArkImapMail[] $innerList
     * @param ArkLogger $logger
     */
    function ($imapStream, $messageUID, &$innerList, $logger) {
        $item = ArkImapMail::loadBodyLessMail($imapStream, $messageUID);
        $item->loadTextBody($imapStream);
        $logger->info("Subject : " . $item->getSubject() . " From " . $item->getFrom() . " On " . $item->getDate());
        $innerList[] = $item;
    }
);

if (is_array($list)) {
    $logger->info("Fin: " . count($list) . " Mail(s) Found.");
    foreach ($list as $item) {
        $logger->info("Text Body:" . PHP_EOL . $item->textBody);
    }
} else {
    $logger->error("ERROR: " . json_encode($list));
}