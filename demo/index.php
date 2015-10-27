<?php
require '/data/koala.php';
use koala\Koala;
use koala\O;
use koala\Response;
use koala\Config;

//加载路由规则
require 'route.php';

//映射 404事件
koala::map('notFound',function(){
    include 'views/404.php';
});

//路由过滤器
koala::filter('signin',function(){
    
    $session = O::instance('koala\Session');
    $session->start();

    if(!$session->get('admin_account')) {
        Response::redirect('/signin');
    }

    Response::view([
        'globals' => [
            'admin' => $session->get('admin_account')
        ]
    ]);

});

//入口文件初始化
Koala::init(function(){
    Config::load('config');
});


Koala::go([
    'mode' => 'online',  //线上模式  dev or online
    'root_dir' => __DIR__  // cli必须
]);
?>