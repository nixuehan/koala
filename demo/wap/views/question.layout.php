<?php use koala\Koala;?>
头
<?=$layout_content?>
热门问题:
<?php Koala::$app->View->widget('widget/question->hot');?>