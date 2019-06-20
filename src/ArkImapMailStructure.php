<?php


namespace sinri\ark\imap;


use sinri\ark\core\ArkHelper;
use stdClass;

class ArkImapMailStructure
{
    /**
     * Value    Type    Constant
     * 0    text    TYPETEXT
     * 1    multipart    TYPEMULTIPART
     * 2    message    TYPEMESSAGE
     * 3    application    TYPEAPPLICATION
     * 4    audio    TYPEAUDIO
     * 5    image    TYPEIMAGE
     * 6    video    TYPEVIDEO
     * 7    model    TYPEMODEL
     * 8    other    TYPEOTHER
     * @var int
     */
    protected $type;
    /**
     * Value    Type    Constant
     * 0    7bit    ENC7BIT
     * 1    8bit    ENC8BIT
     * 2    Binary    ENCBINARY
     * 3    Base64    ENCBASE64
     * 4    Quoted-Printable    ENCQUOTEDPRINTABLE
     * 5    other    ENCOTHER
     *
     * @var int
     */
    protected $encoding;
    protected $ifsubtype;
    protected $subtype;
    protected $ifdescription;
    protected $description;
    protected $ifid;
    protected $id;
    protected $lines;
    protected $bytes;
    protected $ifdisposition;
    protected $disposition;
    protected $ifdparameters;
    protected $dparameters;
    protected $ifparameters;
    protected $parameters;
    /**
     * @var ArkImapMailStructure[]
     */
    protected $parts;
    /**
     * @var string
     */
    protected $partIndex;

    /**
     * @return string
     */
    public function getPartIndex(): string
    {
        return $this->partIndex;
    }

    public function __construct($basePartIndex = "")
    {
        $this->partIndex = $basePartIndex;
    }

    public static function descType($type)
    {
        switch ($type) {
            case TYPETEXT:
                return "text";
            case TYPEMULTIPART:
                return "multipart";
            case TYPEMESSAGE:
                return "message";
            case TYPEAPPLICATION:
                return "application";
            case TYPEAUDIO:
                return "audio";
            case TYPEIMAGE:
                return "image";
            case TYPEVIDEO:
                return "video";
            case TYPEMODEL:
                return "model";
            case TYPEOTHER:
            default:
                return "other";
        }
    }

    public static function descEncoding($encoding)
    {
        switch ($encoding) {
            case ENC7BIT:
                return "7bit";
            case ENC8BIT:
                return "8bit";
            case ENCBINARY:
                return "Binary";
            case ENCBASE64:
                return "Base64";
            case ENCQUOTEDPRINTABLE:
                return "Quoted-Printable";
            case ENCOTHER:
            default:
                return "other";
        }
    }

    /**
     * @param $imapStream
     * @param $uid
     * @param string $basePartIndex
     * @return ArkImapMailStructure
     */
    public static function loadFromServer($imapStream, $uid, $basePartIndex = "")
    {
        $object = imap_fetchstructure($imapStream, $uid, FT_UID);
        return self::parsedFromLocalObject($object, $basePartIndex);
    }

    /**
     * @param stdClass $object
     * @param string $basePartIndex
     * @return ArkImapMailStructure
     */
    public static function parsedFromLocalObject($object, $basePartIndex = "")
    {
        $structure = new ArkImapMailStructure($basePartIndex);

        foreach (["type", "encoding", "lines", "bytes"] as $key) {
            $structure->$key = ArkHelper::readTarget($object, $key);
        }

        foreach (["subtype", "description", "id", "disposition", "dparameters", "parameters"] as $key) {
            $ifkey = "if" . $key;
            $structure->$ifkey = ArkHelper::readTarget($object, $ifkey);
            if ($structure->$ifkey) {
                switch ($key) {
                    case 'dparameters':
                    case 'parameters':
                        $list = ArkHelper::readTarget($object, $key, []);
                        $map = [];
                        if (!empty($list)) {
                            foreach ($list as $item) {
                                $map[$item->attribute] = $item->value;
                            }
                        }
                        $structure->$key = $map;
                        break;
                    default:
                        $structure->$key = ArkHelper::readTarget($object, $key);
                }
            }
        }

        $parts = ArkHelper::readTarget($object, "parts", []);
        $structure->parts = [];
        if (!empty($parts) && is_array($parts)) {
            foreach ($parts as $part) {
                $partStructure = $structure->parseLocalObjectForPart($part);
                $structure->parts[] = $partStructure;// key $partStructure->partIndex
            }
        }
        return $structure;
    }

    protected function parseLocalObjectForPart($part)
    {
        $x = (count($this->parts) + 1);
        return self::parsedFromLocalObject($part, ($this->partIndex === "" ? "" : ".") . $x);
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @return bool
     */
    public function getIfSubtype()
    {
        return $this->ifsubtype;
    }

    /**
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * @return bool
     */
    public function getIfDescription()
    {
        return $this->ifdescription;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function getIfId()
    {
        return $this->ifid;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * @return int
     */
    public function getBytes()
    {
        return $this->bytes;
    }

    /**
     * @return bool
     */
    public function getIfDisposition()
    {
        return $this->ifdisposition;
    }

    /**
     * @return string
     */
    public function getDisposition()
    {
        return $this->disposition;
    }

    /**
     * @return bool
     */
    public function getIfDParameters()
    {
        return $this->ifdparameters;
    } // [{attribute:XX,value:YY}]

    /**
     * @return array
     */
    public function getDParameters()
    {
        return $this->dparameters;
    }// [ PART ]

    /**
     * @return bool
     */
    public function getIfParameters()
    {
        return $this->ifparameters;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return ArkImapMailStructure[]
     */
    public function getParts()
    {
        return $this->parts;
    }

    public function debugDump()
    {
        echo "Primary body type: " . $this->type . " -> " . self::descType($this->type) . PHP_EOL;
        echo "Body transfer encoding: " . $this->encoding . " -> " . self::descEncoding($this->encoding) . PHP_EOL;
        echo "MIME subtype: " . ($this->ifsubtype ? ("Exists as " . $this->subtype) : "Not Exists") . PHP_EOL;
        echo "Content description string: " . ($this->ifdescription ? ("Exists as " . $this->description) : "Not Exists") . PHP_EOL;
        echo "Identification string: " . ($this->ifid ? ("Exists as " . $this->id) : "Not Exists") . PHP_EOL;
        echo "Number of lines: " . $this->lines . PHP_EOL;
        echo "Number of bytes: " . $this->bytes . PHP_EOL;
        echo "Disposition string: " . ($this->ifdisposition ? ("Exists as " . $this->disposition) : "Not Exists") . PHP_EOL;
        echo "Parameters on the Content-disposition MIME header: " . ($this->ifdparameters ? "Exists:" : "Not Exists") . PHP_EOL;
        if ($this->ifdparameters) {
            foreach ($this->dparameters as $key => $value) {
                echo "\t" . $key . " : " . $value . PHP_EOL;
            }
        }
        echo "Parameters: " . ($this->ifparameters ? "Exists as " : "Not Exists") . PHP_EOL;
        if ($this->ifparameters) {
            foreach ($this->parameters as $key => $value) {
                echo "\t" . $key . " : " . $value . PHP_EOL;
            }
        }
        echo "Parts: " . count($this->parts) . PHP_EOL;
        foreach ($this->parts as $part) {
            echo "--- Part [" . $part->partIndex . "] ---" . PHP_EOL;
            $part->debugDump();
        }
    }
}