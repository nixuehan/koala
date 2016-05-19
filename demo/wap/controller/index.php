<?php
namespace controller;

use koala\Koala;

/**
* 问题
*/
class Index extends \Controller{

    /**
    * 问题详情
    */
    public function question() {

        $get = Koala::$app->Request->get([
            'qid' => '\Valid\required'
        ]);

        $question = Koala::$app->O->rpc->cls('question')->detail($get['qid']);
        $answerList = Koala::$app->O->rpc->cls('question')->answerList($get['qid']);

        Koala::$app->View->main('question')->layout('question.detail',[
            'question' => $question,
            'answerList' => $answerList
        ]);
    }

    /**
    * 回答详情
    */
    public function answer() {

        $get = Koala::$app->Request->get([
            'qid' => '\Valid\required'
        ]);

        $answer = Koala::$app->O->rpc->cls('question')->answer($get['qid']);
        $answerList = Koala::$app->O->rpc->cls('question')->answerList($get['qid']);

        Koala::$app->View->main('question')->layout('answer.detail',[
            'answer' => $question,
            'answerList' => $answerList
        ]);
    }
}