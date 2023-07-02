<?php

namespace App\CustomHelper;

class Result
{

    protected $object;

    protected $list;

    protected $msg;

    protected $token;

    protected int $statusCode;

    public function __construct($object, $list, $msg, $token, $statusCode)
    {
        $this->object = $object;

        $this->list = $list;

        $this->msg = $msg;

        $this->token = $token;

        $this->statusCode = $statusCode;
    }


    public static function ReturnMessage($msg, $statusCode)
    {
        $result = [
            "msg" => $msg,
            "StatusCode" => $statusCode,
        ];

        return $result;
    }

    public static function ReturnObject($object, $statusCode, $msg)
    {
        $result = [
            "msg" => $msg,
            "result" => $object,
            "StatusCode" => $statusCode,
        ];

        return $result;
    }

    public static function ReturnList($list, $statusCode, $msg)
    {
        $result = [
            "msg" => $msg,
            "result" => $list,
            "StatusCode" => $statusCode
        ];

        return $result;
    }

    public static function Error($msg, $StatusCode)
    {
        $result = [
            "msg" => $msg,
            "StatusCode" => $StatusCode
        ];

        return $result;
    }
}
