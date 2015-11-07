<?php
require '/data/koala.php';
use koala\Koala;
use koala\Config;
use koala\O;
use koala\Mysql;
use koala\Database;
use koala\Response;
use koala\C;
use koala\M;

define('YES',1);
define('NO',0);

require 'route.php';

koala::map('notFound',function(){
    Response::json(['result' => NO]);
});

Koala::init(function(){

    Config::load('config');

    Database::getConnection('db',function(){
        return new Mysql([
            'host' => Config::get('host'),
            'user' => Config::get('user'),
            'passwd' => Config::get('passwd'),
            'database' => Config::get('database'),
            'charset' => Config::get('charset')
        ]);
    });  

});

class Controller extends C{
    /**
     * api输出
     */
    public function apiOutput(Array $data=[]) {
        Response::json($data);
    }
}

class Module extends M {

}


Koala::go([
    'mode' => 'dev',
    'root_dir' => __DIR__
]);

