<?php
namespace controller;

use koala\O;

class Member extends \Controller{

    /**
     * 用户注册
     */
    public function register() {

        if(O::module('member')->register(['sd'=>23])){
            $this->apiOutput(['adsfasdf'=>'d']);
        }else{
            $this->apiOutput();
        }
    }
    
}

?>