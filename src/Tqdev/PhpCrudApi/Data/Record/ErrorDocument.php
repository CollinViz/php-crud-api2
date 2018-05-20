<?php
namespace Tqdev\PhpCrudApi\Data\Record;

use Tqdev\PhpCrudApi\Data\ErrorCode;

class ErrorDocument
{

    public $code;

    public $message;

    public function __construct(ErrorCode $errorCode, String $argument)
    {
        $this->code = $errorCode->getCode();
        $this->message = $errorCode->getMessage($argument);
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getMessage(): String
    {
        return $this->message;
    }

}
