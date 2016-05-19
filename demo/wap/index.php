<?php
require '/data/www/xingqu/koala.php';
use koala\Koala;
use koala\C;
use koala\M;
use koala\T;
use koala\DB;

require 'filter.php';

Koala::errorhandler([

]);

Koala::init(function(){
    require_once "./lib/func.php";
    Koala::$app->Config->load("application");

    Koala::$app->Request->validateFilePath('validate.php'); //验证文件路径指定

    Koala::$app->O->register('log',function(){
        return  Koala::$app->O->instance('lib\log')->factory();
    });

    Koala::$app->O->register('rpc',function(){
        return Koala::$app->O->instance('lib\rpc');
    });
    
    require_once('./lib/Requests.php');

    \Requests::register_autoloader();

});

class Controller extends C{ }

class Module extends M { }

class Table extends T { }

class NotFoundException extends Exception{ } 
class BeeException extends Exception{ }


final class Http {

    public static function get($url) {
        $response = \Requests::get($url, array('Accept' => 'application/json'));
        if($response->success) {
            return json_decode($response->body,true);
        }
        return false;
    }

    public static function post($url,Array $data) {
        $response = \Requests::post($url, array(),$data);
        if($response->success) {
            return json_decode($response->body,true);
        }
        return false;
    }
}

Koala::go([
    'csrf' => true,
    'root_dir' => __DIR__
]);

