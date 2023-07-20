<?php

namespace App\CustomHelper;

use Illuminate\Support\Facades\Log;

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


    public static function ReturnMessage($msg, $statusCode)
    {
        $result = [
            "msg" => $msg,
            "StatusCode" => $statusCode,
            "success" => true
        ];

        return response()->json($result, $statusCode);
    }

    public static function ReturnObject($object, $statusCode, $msg)
    {
        $result = [
            "msg" => $msg,
            "result" => $object,
            "StatusCode" => $statusCode,
            "success" => true
        ];

        return response()->json($result, $statusCode);
    }

    public static function ReturnList($list, $statusCode, $msg)
    {
        $result = [
            "msg" => $msg,
            "result" => $list,
            "StatusCode" => $statusCode,
            "success" => true
        ];

        return response()->json($result, $statusCode);
    }

    public static function Error($msg, $statusCode)
    {
        $result = [
            "msg" => $msg,
            "StatusCode" => $statusCode,
            "success" => false
        ];

        return response()->json($result, $statusCode);
    }

    public static function InternalServerError($exp)
    {
        Log::error($exp->getMessage());
        return response()->json('Service is temporarily Unavailable', 500);
    }
}
