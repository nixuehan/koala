<?php

/**
 * 阿里云api访问ak sk
 */

$aliyun = [
    'access_key_id' => 'oyou1o',
    'access_key_secret' => 'ahYVaCPE'
];

return [
    'log' => [
        'endpoint' =>  'cn-bng.sls.aliyuncs.com',
        'project' => 'bee',
        'logstore' => 'kernel',
        'accessKeyId' => $aliyun['access_key_id'],
        'accessKey' => $aliyun['access_key_secret']
    ],
];



