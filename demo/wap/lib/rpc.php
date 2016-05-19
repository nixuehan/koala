<?php
namespace lib;
use koala\Koala;

/**
 * rpc服务
 */
class Rpc {

    const REQUEST_URL = "http://xingqu.api.taobeihai.com/rpc/server/";

    public function cls($do) {
        $client = new \Yar_Client(self::REQUEST_URL.$do);
        $client->SetOpt(YAR_OPT_CONNECT_TIMEOUT, 1000);
        return $client;
    }
}