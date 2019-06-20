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
    protected $useSSL;
    protected $skipValidateCert;
    protected $imapAddress;
    /**
     * @var string
     */
    protected $lastError;

    /**
     * ArkImapWorker constructor.
     * @param $host
     * @param $port
     * @param $username
     * @param $password
     * @param bool $useSSL Note, your port should be right with this option.
     * @param bool $skipValidateCert An actually dangerous option. You may only need to use this as TRUE when IMAP Server causes cert error, such as `Certificate failure for imap.xxx.com: unable to get local issuer certificate: ...`
     */
    public function __construct($host, $port, $username, $password, $useSSL = true, $skipValidateCert = false)
    {
        $this->useSSL = $useSSL;
        $this->skipValidateCert = $skipValidateCert;
        $this->imapHost = $host;
        $this->imapPort = $port;
        $this->imapUsername = $username;
        $this->imapPassword = $password;
        $this->logger = ArkLogger::makeSilentLogger();

        $this->imapAddress = "{{$this->imapHost}:{$this->imapPort}/imap" . ($this->useSSL ? "/ssl" . ($this->skipValidateCert ? "/novalidate-cert" : "") : "") . "}";
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

    /**
     * @return ArkImapMailBoxExpression[]|bool
     */
    public function listMailBoxes()
    {
        $imapStream = imap_open($this->imapAddress, $this->imapUsername, $this->imapPassword);
        if (!$imapStream) {
            $this->logger->error("IMAP ERROR: " . imap_last_error());
            return false;
        }
        $folders = imap_list($imapStream, $this->imapAddress, "*");//"{{$this->imapHost}:{$this->imapPort}}"

        $prefixLength = strlen($this->imapAddress);

        $list = [];
        foreach ($folders as $rawFolderName) {
            $expression = new ArkImapMailBoxExpression($rawFolderName, $prefixLength);
            $list[] = $expression;

//            $decodedFolderName = mb_convert_encoding($rawFolderName, "utf-8", "UTF7-IMAP");
//            $list[] = [
//                "raw" => $rawFolderName,// for raw IMAP coding
//                "utf8" => $decodedFolderName,// with leading address, for human debug
//                "code" => substr($rawFolderName, $prefixLength),// used for method `searchInMailBox`
//                "name" => substr($decodedFolderName, $prefixLength),// no leading address, for human reading
//            ];
        }

        imap_close($imapStream);
        return $list;
    }

    /**
     * @param string $boxRawName ArkImapMailBoxExpression::getMailboxCode
     * @param string $criteria
     * @param null|callable $callback function($imapStream, $messageUID, &$innerList,$logger) fetch mail through stream with UID and filter them into list
     * @return ArkImapMail[]|bool
     */
    public function searchInMailBox($boxRawName, $criteria = 'ALL', $callback = null)
    {
        $imapStream = imap_open($this->imapAddress . $boxRawName, $this->imapUsername, $this->imapPassword);
        if (!$imapStream) {
            $this->logger->error("IMAP ERROR: " . imap_last_error());
            return false;
        }
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
                $item = ArkImapMail::loadBodyLessMail($imapStream, $messageUID);
                $this->logger->debug("→ " . $item->getSender() . " sent " . $item->getSubject() . " on " . $item->getDate());
                $list[] = $item;
            } else {
                call_user_func_array($callback, [$imapStream, $messageUID, &$list, $this->logger]);
            }
        }
        imap_close($imapStream);
        return $list;
    }
}