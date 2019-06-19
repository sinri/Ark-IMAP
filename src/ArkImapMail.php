<?php


namespace sinri\ark\imap;


class ArkImapMail
{

    public $uid;
    public $sno;

    public $date;
    public $subject;
    public $from;
    public $sender;

    public $entireRawBody;

    public $text_body;
    public $mp_text_body;
    public $mp_html_body;

    /**
     * @param $imapStream
     * @param $messageUID
     * @return ArkImapMail
     */
    public static function loadMail($imapStream, $messageUID)
    {
        $item = self::loadBodyLessMail($imapStream, $messageUID);

        //$structure = imap_fetchstructure($imapStream, $messageUID, FT_UID);
        //var_dump($structure);

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
        $item->subject = ImapExcavator::mimeDecode($mime->subject);
        $item->from = $mime->from[0]->mailbox . "@" . $mime->from[0]->host;
        $item->sender = $mime->sender[0]->mailbox . "@" . $mime->sender[0]->host;

        return $item;
    }

    public function loadEntireRawBody($imapStream)
    {
        $body = imap_fetchbody($imapStream, $this->uid, "", FT_UID | FT_PEEK);
        $this->entireRawBody = $body;
    }

    public function loadTextBody($imapStream)
    {
        $body = imap_fetchbody($imapStream, $this->uid, "1", FT_UID | FT_PEEK);
        $this->text_body = $body;//base64_decode($body);
    }

    public function loadMpTextBody($imapStream)
    {
        $body = imap_fetchbody($imapStream, $this->uid, "1.1", FT_UID | FT_PEEK);
        $this->mp_text_body = $body;//base64_decode($body);
    }

    public function loadMpHtmlBody($imapStream)
    {
        $body = imap_fetchbody($imapStream, $this->uid, "1.2", FT_UID | FT_PEEK);
        $this->mp_html_body = $body;//base64_decode($body);
    }
}