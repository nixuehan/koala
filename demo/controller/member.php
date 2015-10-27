<?php
namespace controller;
use koala\Response;

class Member{

    public function index() {
        Response::layout('member');
    }

    public function credit() {
        Response::layout('credit');
    }

    public function comment() {
        Response::layout('comment');
    }

 }
?>