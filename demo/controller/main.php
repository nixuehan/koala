<?php
namespace controller;
use koala\Response;
use koala\Request;
use koala\Config;
use koala\O;

class Main{

    public function index() {
        Response::layout('index');
    }

    public function signin() {

        if(!Request::isPost()){

            Response::render('signin');

        }else{

            $post = Request::post([
                        'admin' => '',
                        'passwd' => ''
                    ]);


            if(Config::get('admin_account') == $post['admin']) {
                
                $session = O::instance('koala\Session');
                $session->start();

                $session->set([
                    'admin_account' => $post['admin'],
                ]);

                Response::redirect('/');

            }else{

                Response::redirect('/signin');

            }
        }
    }

    public function profile() {
        Response::render('profile');
    }
}

?>