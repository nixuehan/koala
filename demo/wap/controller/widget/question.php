<?php
namespace controller\widget;

use koala\Koala;

/**
* 问题组件
*/
class Question extends \Controller{

    /**
    * 热门问题
    */
    public function hot() {
        $hot = Koala::$app->O->rpc->cls('question')->hotQuestion();
        Koala::$app->View->render("widget/question.hot",[
            'hot_data' => $hot
        ]);
    }
}