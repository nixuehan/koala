<?php
use koala\Koala;

Koala::route([

    'signin' => [
        '/' => 'main->index',
        '/admin/member' => 'member->index',
        '/admin/member/credit' => 'member->credit',
        '/admin/comment' => 'member->comment'
    ],

    '/signin' => 'main->signin'
    
]);