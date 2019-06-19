<?php


namespace sinri\ark\imap;


use sinri\ark\core\ArkLogger;

class ArkImapWorker
{
    /**
     * @var ArkLogger
     */
    public $logger;
    protected $imapHost;
    protected $imapPort;
    protected $imapUsername;
    protected $imapPassword;
    /**
     * @var string
     */
    protected $lastError;

    public function __construct($host, $port, $username, $password)
    {
        $this->imapHost = $host;
        $this->imapPort = $port;
        $this->imapUsername = $username;
        $this->imapPassword = $password;
        $this->logger = ArkLogger::makeSilentLogger();
    }

    public static function createCriteriaWithDateRange($sinceDate = null, $beforeDate = null)
    {
        $criteria = "";
        if ($sinceDate !== null) {
            $criteria .= 'SINCE "' . $sinceDate . '" ';
        }
        if ($beforeDate !== null) {
            $criteria .= 'BEFORE "' . $beforeDate . '" ';
        }
        if ($criteria === '') $criteria = 'ALL';
        return $criteria;
    }

    public static function mimeDecode($string)
    {
        $x = imap_mime_header_decode($string);
        //var_dump($x);
        $s = "";
        foreach ($x as $y) {
            if ($y !== 'utf-8')
                $s .= mb_convert_encoding($y->text, "UTF-8", $y->charset);
            else
                $s .= $y->text;
        }
        return $s;
    }

    /**
     * @param ArkImapMail[] $list
     * @param callable $filterCallback function($mail)
     * @return ArkImapMail[]
     */
    public static function filterSubject($list, $filterCallback)
    {
        $matchedList = [];
        foreach ($list as $item) {
            $matched = call_user_func_array($filterCallback, [$item]);
            if (!$matched) continue;
            $matchedList[] = $item;
        }
        return $matchedList;
    }

    /**
     * @param ArkLogger $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return mixed
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    public function listMailBoxes()
    {
        $imapStream = imap_open("{{$this->imapHost}:{$this->imapPort}/imap/ssl}", $this->imapUsername, $this->imapPassword);
        $folders = imap_list($imapStream, "{{$this->imapHost}:{$this->imapPort}}", "*");

        $list = [];
        foreach ($folders as $rawFolderName) {
            $decodedFolderName = mb_convert_encoding($rawFolderName, "utf-8", "UTF7-IMAP");

            $list[] = [
                "raw" => $rawFolderName,
                "utf8" => $decodedFolderName,
                "mailbox" => "{{$this->imapHost}:{$this->imapPort}/imap/ssl}" . $rawFolderName,
            ];
        }

        imap_close($imapStream);
        return $list;
    }

    /**
     * @param $boxRawName
     * @param string $criteria
     * @param null|callable $callback function($imapStream, $messageUID, &$list,$logger) fetch mail through stream with UID and filter them into list
     * @return ArkImapMail[]|bool
     */
    public function searchInMailBox($boxRawName, $criteria = 'ALL', $callback = null)
    {
        $imapStream = imap_open("{{$this->imapHost}:{$this->imapPort}/imap/ssl}" . $boxRawName, $this->imapUsername, $this->imapPassword);
        $messageUIDs = imap_search($imapStream, $criteria, SE_UID);

        if ($messageUIDs === false) {
            $this->lastError = imap_last_error();
            return false;
        }

        $list = [];

        $this->logger->debug("FETCHED " . count($messageUIDs) . " MAILS");
        foreach ($messageUIDs as $messageUID) {
            $this->logger->debug("MESSAGE #" . $messageUID);
            if ($callback === null) {
                $item = ArkImapMail::loadMail($imapStream, $messageUID);
                $this->logger->debug("→ " . $item->sender . " sent " . $item->subject . " on " . $item->date);
                $list[] = $item;
            } else {
                call_user_func_array($callback, [$imapStream, $messageUID, &$list, $this->logger]);
            }
        }
        imap_close($imapStream);
        return $list;
    }
}