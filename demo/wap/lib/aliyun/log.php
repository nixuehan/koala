<?php
namespace lib\aliyun;

use koala\Koala;

class Log {

    private $accessKeyId = '';
    private $accessKey = '';
    private $endpoint = '';
    private $client = NULL;

    public $project = '';
    public $logstore = '';
 
    public function __construct() {
        require_once 'lib/aliyun/log/Log_Autoload.php';
    }

    public function init(Array $conf) {
        $this->project = $conf['project'];
        $this->logstore = $conf['logstore'];
        $this->endpoint = $conf['endpoint'];
        $this->accessKeyId = $conf['accessKeyId'];
        $this->accessKey = $conf['accessKey'];

        $this->client = new \Aliyun_Log_Client($this->endpoint, $this->accessKeyId, $this->accessKey);
    }

    public function put($topic,Array $data) {

        $contents = $data;
        $logItem = new \Aliyun_Log_Models_LogItem();
        $logItem->setTime(time());
        $logItem->setContents($contents);
        $logitems = array($logItem);
        $request = new \Aliyun_Log_Models_PutLogsRequest($this->project, $this->logstore,$topic, null, $logitems);
        
        try {
            $this->client->putLogs($request);
        } catch (\Aliyun_Log_Exception $ex) {

        } catch (\Exception $ex) {

        }
    }

}