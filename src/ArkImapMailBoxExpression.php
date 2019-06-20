<?php


namespace sinri\ark\imap;


class ArkImapMailBoxExpression
{
    /**
     * @var string for raw IMAP coding
     */
    protected $completeMailboxNameRaw;
    /**
     * @var string with leading address, for human debug
     */
    protected $completeMailboxNameUtf8;
    /**
     * @var bool|string used for method `searchInMailBox`
     */
    protected $mailboxCode;
    /**
     * @var bool|string no leading address, for human reading
     */
    protected $mailboxName;

    public function __construct($rawFolderName, $prefixLength)
    {
        $this->completeMailboxNameRaw = $rawFolderName;
        $this->completeMailboxNameUtf8 = mb_convert_encoding($rawFolderName, "utf-8", "UTF7-IMAP");
        $this->mailboxCode = substr($rawFolderName, $prefixLength);
        $this->mailboxName = substr($this->completeMailboxNameUtf8, $prefixLength);
    }

    /**
     * @return mixed
     */
    public function getCompleteMailboxNameRaw()
    {
        return $this->completeMailboxNameRaw;
    }

    /**
     * @return mixed
     */
    public function getCompleteMailboxNameUtf8()
    {
        return $this->completeMailboxNameUtf8;
    }

    /**
     * @return mixed
     */
    public function getMailboxCode()
    {
        return $this->mailboxCode;
    }

    /**
     * @return mixed
     */
    public function getMailboxName()
    {
        return $this->mailboxName;
    }
}