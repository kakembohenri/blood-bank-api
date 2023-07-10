<?php

namespace App\CustomHelper;

class Result
{

    protected $object;

    protected $list;

    protected $msg;

    protected $token;

    protected int $statusCode;

    protected $success;

    public function __construct($object, $list, $msg, $token, $statusCode, $success)
    {
        $this->object = $object;

        $this->list = $list;

        $this->msg = $msg;

        $this->token = $token;

        $this->statusCode = $statusCode;

        $this->success = $success;
    }


    public static function ReturnMessage($msg, $statusCode, $success)
    {
        $result = [
            "msg" => $msg,
            "StatusCode" => $statusCode,
            "success" => $success

        ];

        return $result;
    }

    public static function ReturnObject($object, $statusCode, $msg, $success)
    {
        $result = [
            "msg" => $msg,
            "result" => $object,
            "StatusCode" => $statusCode,
            "success" => $success

        ];

        return $result;
    }

    public static function ReturnList($list, $statusCode, $msg, $success)
    {
        $result = [
            "msg" => $msg,
            "result" => $list,
            "StatusCode" => $statusCode,
            "success" => $success
        ];

        return $result;
    }

    public static function Error($msg, $StatusCode, $success)
    {
        $result = [
            "msg" => $msg,
            "StatusCode" => $StatusCode,
            "success" => $success
        ];

        return $result;
    }
}
