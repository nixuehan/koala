<?php
require '/data/koala.php';
use koala\Koala;
use koala\O;
use koala\Response;
use koala\Config;

require 'route.php';

koala::map('notFound',function(){
    include 'views/404.php';
});

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

Koala::init(function(){
    Config::load('config');
});


Koala::go([
    'mode' => 'online',
    'root_dir' => __DIR__
]);
?>