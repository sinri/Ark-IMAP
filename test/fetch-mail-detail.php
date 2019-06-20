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
        "17-Jun-2019", "21-Jun-2019" // this is for your set
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

        $item->loadMailStructure($imapStream);
        $item->getStructure()->debugDump();

        $parts = $item->getStructure()->getParts();

        if (count($parts) === 0) {
            // only one part
            $item->loadBodyWithPartIndex($imapStream, "1");
            $logger->info("BODY PART [1]:" . PHP_EOL . $item->getBodyByPartIndex("1"));
        } else {
            foreach ($parts as $index => $part) {
                $partIndex = $part->getPartIndex();
                $logger->info("BODY PART TYPE [{$partIndex}]");
                if (count($part->getParts()) > 0) {
                    // more details
                    $logger->warning("It has deeper parts! IGNORED.");
                } else {
                    $item->loadBodyWithPartIndex($imapStream, $partIndex);
                    $logger->info("PART[{$partIndex}]:" . PHP_EOL . $item->getBodyByPartIndex($partIndex));
                }
            }
        }

//        $logger->info('-----');
//        foreach (["0","1","1.1","1.2","2","2.0","2.1","2.2","2.3"] as $section) {
//            $logger->info("Section ".$section, ['struct' => $item->readBodyStructure($imapStream, $section)]);
//        }


//        $logger->info('-----');
//        $item->loadEntireBody($imapStream);
//        $logger->info("RAW: ".PHP_EOL.$item->getEntireBody());

        $innerList[] = $item;
    }
);
