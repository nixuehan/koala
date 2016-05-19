<?php
namespace lib;

use koala\Koala;

/**
* 应用日志类型
*/
class logTopic {
    const COMMOM = 'common'; //普通错误
}

/**
 * 日志
 */
class Log {

	private $log = NULL;
	public $topic = '';

    public function factory() {
        $this->log = Koala::$app->O->instance('lib\aliyun\log');
        $conf = Koala::$app->Config->get('log');
        $this->log->init([
        	'endpoint' => $conf['endpoint'],
        	'project' => $conf['project'],
        	'logstore' => $conf['logstore'],
        	'accessKeyId' => $conf['accessKeyId'],
        	'accessKey' => $conf['accessKey']
        ]);
        return $this;
    }
   
	private function put($topic,Array $data) {
		$this->log->put($topic,$data);
	}

    /**
     * 普通日志
     */
    public function notice($msg,$other='') {
        $this->log->put(logTopic::COMMOM,[
            "message" => $msg,
            "other" => is_array($other) ? json_encode($other) : $other
        ]);
    }
}