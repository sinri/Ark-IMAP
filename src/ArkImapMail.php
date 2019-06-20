<?php


namespace sinri\ark\imap;


use sinri\ark\core\ArkHelper;

class ArkImapMail
{

    protected $uid;
    protected $sno;

    protected $date;
    protected $subject;
    protected $from;
    protected $sender;

    /**
     * @var ArkImapMailStructure
     */
    protected $structure;
    protected $bodyPartDict = [];

    /**
     * @param $imapStream
     * @param $messageUID
     * @return ArkImapMail
     * @deprecated
     */
    public static function loadMail($imapStream, $messageUID)
    {
        $item = self::loadBodyLessMail($imapStream, $messageUID);

        $item->loadMailStructure($imapStream);

        /**
         * With an email message that only has a text body and does not have any mime attachments, imap-fetchbody() will return the following for each requested part number:
         *
         * (empty) - Entire message
         * 0 - Message header
         * 1 - Body text
         *
         * With an email message that is a multi-part message in MIME format, and contains the message text in plain text and HTML, and has a file.ext attachment, imap-fetchbody() will return something like the following for each requested part number:
         *
         * (empty) - Entire message
         * 0 - Message header
         * 1 - MULTIPART/ALTERNATIVE
         * 1.1 - TEXT/PLAIN
         * 1.2 - TEXT/HTML
         * 2 - file.ext
         *
         * Now if you attach the above email to an email with the message text in plain text and HTML, imap_fetchbody() will use this type of part number system:
         *
         * (empty) - Entire message
         * 0 - Message header
         * 1 - MULTIPART/ALTERNATIVE
         * 1.1 - TEXT/PLAIN
         * 1.2 - TEXT/HTML
         * 2 - MESSAGE/RFC822 (entire attached message)
         * 2.0 - Attached message header
         * 2.1 - TEXT/PLAIN
         * 2.2 - TEXT/HTML
         * 2.3 - file.ext
         */

        $item->loadTextBody($imapStream);
        $item->loadMpTextBody($imapStream);
        $item->loadMpHtmlBody($imapStream);

        return $item;
    }

    public static function loadBodyLessMail($imapStream, $messageUID)
    {
        $item = new ArkImapMail();

        $messageSequenceNumber = imap_msgno($imapStream, $messageUID);

        $item->uid = $messageUID;
        $item->sno = $messageSequenceNumber;

        $mime = imap_headerinfo($imapStream, $messageSequenceNumber);

        $item->date = $mime->date;
        $item->subject = ArkImapWorker::mimeDecode($mime->subject);
        $item->from = $mime->from[0]->mailbox . "@" . $mime->from[0]->host;
        $item->sender = $mime->sender[0]->mailbox . "@" . $mime->sender[0]->host;

        return $item;
    }

    public function loadMailStructure($imapStream)
    {
        $this->structure = ArkImapMailStructure::loadFromServer($imapStream, $this->uid);
    }

    public function readBodyStructure($imapStream, $section)
    {
        return imap_bodystruct($imapStream, $this->sno, $section);
    }

    public function loadTextBody($imapStream)
    {
        $this->loadBodyWithPartIndex($imapStream, "1");
    }

    public function loadBodyWithPartIndex($imapStream, $partIndex)
    {
        $this->bodyPartDict[$partIndex] = imap_fetchbody($imapStream, $this->uid, $partIndex, FT_UID | FT_PEEK);
    }

    public function loadMpTextBody($imapStream)
    {
        $this->loadBodyWithPartIndex($imapStream, "1.1");
    }

    public function loadMpHtmlBody($imapStream)
    {
        $this->loadBodyWithPartIndex($imapStream, "1.2");
    }

    /**
     * @return mixed
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @return mixed
     */
    public function getSno()
    {
        return $this->sno;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @return ArkImapMailStructure
     */
    public function getStructure(): ArkImapMailStructure
    {
        return $this->structure;
    }

    /**
     * @return array
     */
    public function getBodyPartDict()
    {
        return $this->bodyPartDict;
    }

    public function loadEntireBody($imapStream)
    {
        $this->loadBodyWithPartIndex($imapStream, "");
        //$this->bodyPartDict[""] = imap_fetchbody($imapStream, $this->uid, "", FT_UID | FT_PEEK);
        //$this->entireRawBody = $body;
    }

    public function getEntireBody()
    {
        $partIndex = "";
        return $this->getBodyByPartIndex($partIndex);
    }

    public function getBodyByPartIndex($partIndex)
    {
        return ArkHelper::readTarget($this->bodyPartDict, $partIndex);
    }

    public function getTextBody()
    {
        $partIndex = "1";
        return $this->getBodyByPartIndex($partIndex);
    }

    public function getMpTextBody()
    {
        $partIndex = "1.1";
        return $this->getBodyByPartIndex($partIndex);
    }

    public function getMpHtmlBody()
    {
        $partIndex = "1.2";
        return $this->getBodyByPartIndex($partIndex);
    }
}