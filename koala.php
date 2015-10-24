<?php
namespace koala;

class C{
    const DEV = 'dev';
    const CACHE = 'cache';
}

class Koala{
    private static $_route = []; //路由规则
    private static $_env = []; //环境变量
    private static $_map = []; //打点
    private static $_filter = [];
    private static $_anchor_before = [];
    private static $_anchor_after = [];

    private function __construct() {}
    private function __destruct() {}
    private function __clone() {}

    public static  function route(Array $rule) {
        self::$_route = $rule;
    }

    public static  function go(Array $config=[]) {

        self::$_env['root_dir'] = !isset($config['root_dir']) ? '' : $config['root_dir'];
        self::$_env['controller_dir'] = !isset($config['controller_dir']) ? 'controller' : $config['controller_dir'];
        self::$_env['cli'] = PHP_SAPI === 'cli' ? true : false; //是否命令行
        self::$_env['request_method'] = isset($_SERVER['REQUEST_METHOD']) && !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        self::$_env['view_dir'] = isset($config['view_dir']) ? $config['view_dir'] : 'views';
        self::$_env['mode'] = isset($config['mode']) ? $config['mode'] : C::DEV;
        self::$_env['cache'] = isset($config['cache']) ? $config['cache'] : C::CACHE;


        if(self::$_env['cli'] && self::$_env['root_dir']) {
            set_include_path(get_include_path() . PATH_SEPARATOR . self::$_env['root_dir']);
        }

        if (C::DEV === self::$_env['mode']) {
            ini_set("display_errors", "On");
            error_reporting(E_ALL);
        }else{
            ini_set("display_errors", "Off");
            error_reporting(0);
        }   

        set_exception_handler(function($e){

            $msg = sprintf('<h1>Koala Error</h1>'.
                    '<h3>%s (%s)</h3>'.
                    '<pre>%s</pre>',
                    $e->getMessage(),
                    $e->getCode(),
                    $e->getTraceAsString()
                );
            Response::write($msg,500);
        });

        unset($_REQUEST);

        spl_autoload_register(array(__CLASS__,'classAutoLoadPath'));

        $_fn = koala::getInit();
        if($_fn) $_fn();

        foreach(self::$_route as $filter => $data){
            if(is_array($data)){
                foreach($data as $regular => $class){
                    //判断uri
                    $param = self::_matchControllerMethod($regular);
                    if(is_array($param)){
                        $_fn = self::getFilter($filter);
                        if(!is_callable($_fn)){
                            self::throwing('koalaError',sprintf("Can not find the filter %s:",$filter));
                        }
                        $_fn() && self::_loadController($class,$param);
                        goto after;

                    }
                }
            }else{

                //判断uri
                $param = self::_matchControllerMethod($filter);
                if(is_array($param)){
                    self::_loadController($data,$param);
                    goto after;
                }
            } 
        }

        self::throwing('notFound','Can not find the controller');

after:
        $_fn = koala::getFinish();

        if($_fn) $_fn();
        exit;
    }

    public static function throwing($t,$msg) {

        $_msg = '<html>
<head><title>koala  error </title></head>
<body bgcolor="white">
<center><h1>%s</h1></center>
<hr><center> <a href="https://github.com/nixuehan/koala">koala micro web-framework</a> </center>
</body>
</html>
';

        
        if(!self::getEnv('mode')){
            Response::write(sprintf($_msg,"can't have something to do before koala::go"),500);
        }

        if(self::getEnv('mode') == C::DEV) {
            throw new KoalaException($msg);
        }

        if($fn = self::getMap($t)){
            $fn();exit;
        }

        if($t == 'notFound') {
            throw new KoalaException($msg);
        }else{
            Response::write(sprintf($_msg,"koala internal error"),500);          
        }
    }

    public static function classAutoLoadPath($class) {

        if(strpos($class,'\\') !== false) {
            $class = str_replace('\\','/',$class);
        }

        $file = strtolower($class) . '.php';
    
        !koala::fileExists($file) && self::throwing('koalaError',"No such file or directory : $file");

        require_once($file);
    }

    private static function __fileExists($file){

        if(!file_exists($file) && self::$_env['cli']){ 
            $paths = explode(PATH_SEPARATOR,get_include_path()); 
            
            foreach($paths as $path) {
                if(file_exists(preg_replace('%/$%','',$path)."/$file")) {
                    return true;   
                }
            }
            return false; 
        }else {
            return true; 
        }
    }

    public static function fileExists($file) {
        return self::$_env['cli'] ? self::__fileExists($file)  :  file_exists($file);
    }

    public static function getEnv($var='',$default='') {
        if($var == '') return self::$_env;
        return isset(self::$_env[$var]) && !empty(self::$_env[$var]) ? self::$_env[$var] : $default;
    }

    public static function setEnv($var,$value,$default='') {
        self::$_env[$var] = !empty($value) ? $value : $default;
    }

    public static function getServer($var='',$default='') {
        if($var == '') return $_SERVER;
        return isset($_SERVER[$var]) && !empty($_SERVER[$var]) ? $_SERVER[$var] : $default;
    }

    public static function filter($fr,callable $fn) {
        self::$_filter[$fr] = $fn;
    }

    public static function getFilter($fr) {
        return isset(self::$_filter[$fr]) ? self::$_filter[$fr] : false;
    }    

    public static function init(callable $fn) {
        self::$_anchor_before = $fn;
    }

    public static function getInit() {
        return is_callable(self::$_anchor_before) ? self::$_anchor_before : false;
    }    

    public static function finish(callable $fn) {
        self::$_anchor_after = $fn;
    }

    public static function getFinish() {
        return is_callable(self::$_anchor_after) ? self::$_anchor_after : false;
    }    

    public static function map($ac,callable $fn) {
        if(is_array($ac)){
            foreach($ac as $v){
                self::$_map[$v] = $fn;
            }
        }else{
            self::$_map[$ac] = $fn;
        }
    }

    public static function getMap($ac) {
        return isset(self::$_map[$ac]) ? self::$_map[$ac] : false;
    }


    //分离控制器和方法
    private static function _matchControllerMethod($regular) {
        if(self::$_env['cli']){
           if(empty($_SERVER['argv'])){
                self::throwing('koalaError','register_argc_argv must be on');
           }

           if(!isset($_SERVER['argv'][1]) || empty($_SERVER['argv'][1])){
                self::throwing('koalaError','Missing path');
           }

            self::$_env['request_uri'] = $_SERVER['argv'][1];
        }else{

            self::$_env['request_uri'] = isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        
            $params = [];
            $args = parse_url(self::$_env['request_uri']);
            if (isset($args['query'])) {
                parse_str($args['query'], $params);
            }

            $_ru = strstr(self::$_env['request_uri'],'?',true);
            if($_ru) self::$_env['request_uri'] = $_ru;


            $_GET = &$params;

        }

        //匹配uri
        if(preg_match('/^'.addcslashes($regular,"\57").'$/i',self::$_env['request_uri'],$matchs)){

            array_shift($matchs);

            return $matchs;
        }

        return false;
    }

    //加载控制器
    private static function _loadController($class_method,$param) {
        $data = @explode('->',$class_method,2);
        if(count($data) > 1){
            list($class,$method) = $data;
            $controller_dir = self::getEnv('controller_dir');

            include_once $controller_dir.'/'.$class.'.php';
            $class = $controller_dir.'\\'.$class;
            $class = str_replace("/","\\",$class);
            if(class_exists($class)) {  
                $obj = new $class(); 
            }else{
                self::throwing('koalaError',"Class $class not found");
            }
            call_user_func_array(array($obj,$method),$param);
        }else{
            self::throwing('koalaError','Controller exception');
        }
    }
}

class KoalaException extends \Exception{}


//输出
class _Response {

    public $status = 200;

    public $headers = [];

    public $body;

    public static $codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',

        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',

        226 => 'IM Used',

        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',

        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',

        426 => 'Upgrade Required',

        428 => 'Precondition Required',
        429 => 'Too Many Requests',

        431 => 'Request Header Fields Too Large',

        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',

        510 => 'Not Extended',
        511 => 'Network Authentication Required'
    ];

    public function status($code = null) {
        if ($code === null) {
            return $this->status;
        }

        if (array_key_exists($code, self::$codes)) {
            $this->status = $code;
        }
        else {
            koala::throwing('koalaError','Invalid status code');
        }

        return $this;
    }

    public function header($name, $value = null) {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->headers[$k] = $v;
            }
        }
        else {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    public function write($str) {
        $this->body .= $str;

        return $this;
    }

    public function clear() {
        $this->status = 200;
        $this->headers = [];
        $this->body = '';

        return $this;
    }

    public function cache($expires) {
        if ($expires === false) {
            $this->headers['Expires'] = 'Mon, 26 Jul 1997 05:00:00 GMT';
            $this->headers['Cache-Control'] = [
                'no-store, no-cache, must-revalidate',
                'post-check=0, pre-check=0',
                'max-age=0'
            ];
            $this->headers['Pragma'] = 'no-cache';
        }
        else {
            $expires = is_int($expires) ? $expires : strtotime($expires);
            $this->headers['Expires'] = gmdate('D, d M Y H:i:s', $expires) . ' GMT';
            $this->headers['Cache-Control'] = 'max-age='.($expires - time());
        }
        return $this;
    }

    public function sendHeaders() {

        header(
            sprintf(
                '%s %d %s',
                (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1'),
                $this->status,
                self::$codes[$this->status]),
            true,
            $this->status
        );

        foreach ($this->headers as $field => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header($field.': '.$v, false);
                }
            }
            else {
                header($field.': '.$value);
            }
        }

        if (($length = strlen($this->body)) > 0) {
            header('Content-Length: '.$length);
        }

        return $this;
    }

    public function send() {
        if (ob_get_length() > 0) {
            $this->body = ob_get_contents() . $this->body;
            ob_end_clean();
        }

        if (!headers_sent()) {
            $this->sendHeaders();
        }

        exit($this->body);
    }

    public function output($msg) {
        exit($msg);
    }
    
    public function jsonp($data, $param = 'jsonp', $code = 200, $encode = true) {
        $json = ($encode) ? json_encode($data) : $data;
        $get = Request::get([$param => '']);
        $callback = $get[$param];
        $this->status($code)
             ->header('Content-Type', 'application/javascript')
             ->write($callback.'('.$json.');')
             ->send();
    }

    public function lastModified($time) {
        $this->header('Last-Modified', date(DATE_RFC1123, $time));

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
            strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $time) {
            $this->halt(304);
        }
    }

    public function halt($code = 200, $message = '') {
        $this->status($code)
             ->write($message)
             ->send();
    }
}

class Config{

    private static $_config = [];

    public static function load($filename) {

        if(!koala::fileExists($filename)){
            koala::throwing('koalaError',"Configuration file does not exist:$filename");
        }

        $opt = include_once($filename);

        if(is_array($opt)){
            self::$_config = array_merge(self::$_config,$opt);
        }
    }

    public static function set($var,$default='') {
        self::$_config[$var] = $default;
    }

    public static function get($var='',$default='') {
        if($var == '') return self::$_config;
        return isset(self::$_config[$var]) && !empty(self::$_config[$var]) ? self::$_config[$var] : $default;
    }
}

class Response{

    private static $_this = null;
    private static $_layouts = [];
    private static $_opt = ['layout' => 'main'];

    private static function instance() {
        if(self::$_this == null) {
            self::$_this = new _Response();
        }
        return self::$_this;
    }

    public static function init($options = []) {
        foreach($options as $k => $v){
            self::$_opt[$k] = $v;
        }
    }

    public static function json($data, $code = 200, $encode = true) {

        self::$_this  = self::instance();

        $json = $encode ? json_encode($data) : $data;
        self::$_this->status($code)
             ->header('Content-Type', 'application/json')
             ->write($json)
             ->send();
    }

    public static function layout($tpl,$T=[]) {

        if(!empty($T)){
            $T = Security::htmlVar($T);
            extract($T);
        }

        $_f = koala::getEnv('view_dir') . '/' .$tpl.'.tpl.php';
        self::$_layouts[isset(self::$_opt['layout_contents']) ? self::$_opt['layout_contents'] : "layout_contents"] = self::_include($_f,$T);     

        if(!empty(self::$_layouts)){
            extract(self::$_layouts);
        }

        if(isset(self::$_opt['layout'])){
            
            $_f = koala::getEnv('view_dir') . '/' .self::$_opt['layout'].'.layout.php';
             if(!@include_once($_f)){
                koala::throwing('koalaError',sprintf("Can't find view file %s",$_f));
            }           
        }


    }

    public static function render($tpl,$T=[]) {

        if(!empty($T)){
            $T = Security::htmlVar($T);
            extract($T);
        }

        $_f = koala::getEnv('view_dir') . '/' .$tpl.'.tpl.php';

        if(!@include_once($_f)){
            koala::throwing('koalaError',sprintf("Can't find view file %s",$_f));
        }
    }

    private static function _include($filename,$T=[]) {
        if (is_file($filename)) {
            if(!empty($T)){
                extract($T);
            }
            ob_start();
            include_once($filename);
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        }
        return '';
    }

    public static function redirect($url,$code=303) {

        self::$_this  = self::instance();

        self::$_this->status($code)
                     ->header('Location', $url)
                     ->write($url)
                     ->send();
    }

    //
    public static function fragments($class_method,Array $param=[]) {

        if($data = explode('->',$class_method)){
            list($class,$method) = $data;
            $controller_dir = koala::getEnv('controller_dir');

            include_once $controller_dir.'/'.$class.'.php';
            $class = $controller_dir.'\\'.$class;
            $class = str_replace("/","\\",$class);
            $obj = new $class();
            call_user_func_array(array($obj,$method),$param);

        }else{
            koala::throwing('koalaError','Controller exception');
        }
    }

    public static function write($message,$code = 200) {
        self::$_this  = self::instance();
        self::$_this->halt($code,$message);
    }

}

//注册全局对象
class O{

    private static $_obj = []; //对象容器
    private static $_module = [];

    private function __construct() {}
    private function __destruct() {}
    private function __clone() {}

    public static function register($name,callable $func) {
        if(!isset(self::$_obj[$name])){
            self::$_obj[$name] = $func();
        }
    }

    public static function instance($_class) {
        if(!isset(self::$_obj[$_class])) {
            self::$_obj[$_class] = new $_class;
        }
        return self::$_obj[$_class];
    }

    public static function module($_class) {
        if(!isset(self::$_module[$_class])) {
            $_class = "\module\\" . $_class;
            self::$_module[$_class] = new $_class;
        }
        return self::$_module[$_class];
    }

    public static function __callStatic($className,$params) {

        if(!isset(self::$_obj[$className])){
            koala::throwing('koalaError',"Can not find the object:$className");
        }

        if(method_exists(self::$_obj[$className],'OOO')){
            self::$_obj[$className]->OOO($params); //预定义
        }
        
        return self::$_obj[$className];
    }
}

//全局变量
class G {

    private static $_var = [];

    public static function get($_var='') {
        if(!$_var){
            return self::$_var;
        }else{
            return isset(self::$_var[$_var]) ? self::$_var[$_var] : '';
        }        
    }

    public static function set($var,$value='') {
        if(is_array($var)){
            foreach($var as $k => $v) {
                self::$_var[$k] = $v;
            }
        }else{
            self::$_var[$var] = $value;
        }
    }
}

//请求
class Request {

    private static function _process(&$container,Array $params = []) {

        if(empty($params)){
            return $container;
        }

        foreach($params as $k => $v){

            if(is_numeric($v)){
                $container[$k] = isset($container[$k]) ? intval($container[$k]) : $v;
            }else if(is_string($v)){
                $container[$k] = isset($container[$k]) ? Security::sqlVar($container[$k]) : $v;
            }
        }
        return $container;
    }

    public static function get(Array $params = []) {

        if(empty($params)){
           $_GET = Security::sqlVar($_GET);
            return $_GET;
        }

        return self::_process($_GET,$params);
    }

    public static function post(Array $params = []) {

        if(empty($params)){
           $_POST = Security::sqlVar($_POST);
            return $_POST;
        }

        return self::_process($_POST,$params);
    }

    public static function file() {
        return $_FILES;
    }

    public static function isPost() {
        return koala::getenv("request_method") == 'POST' ? true : false;
    }
}

class Session {
    
    public function start($fn='') {
        if(is_callable($fn)){
            $fn();
        }
        session_start();
    }

    public function get($s) {
        if(is_array($s)){
            $_result = array();
            foreach($s as $v){
                $_result[$v] = isset($_SESSION[$v]) ? $_SESSION[$v] : null;
            }
            return $_result;
        }else{
            return isset($_SESSION[$s]) ? $_SESSION[$s] : null;
        }
    }

    public function set($s) {
        foreach($s as $k =>$v){
            $_SESSION[$k] = $v;
        }
    }

    public function del($s) {
        if(is_array($s)){
            foreach($s as $v){
                unset($_SESSION[$v]);
            }
        }else{
            unset($_SESSION[$s]);
        }
    }

    public function clear() {
        session_destroy();
    }
}

//安全
class Security {

    public $_css = ''; //外部提交 token

    public function __construct(){ }

    public static function sqlVar($var) {
        return self::addslashes($var);
    }

    private static function addslashes($var){
        if(is_array($var)){
            $arrVar = array();
            foreach($var as $k => $v){
                $arrVar[$k] = self::addslashes($v);
            }
            return $arrVar;
        }else{
            return addslashes($var);
        }
    }

    public static function htmlVar($var) {
        return self::htmlspecialchars($var);
    }

    private static function htmlspecialchars($var){
        if(is_array($var)){
            $arrVar = array();
            foreach($var as $k => $v){
                if($k[0] == '_' && $k[1] == '_'){
                    $arrVar[$k] = $v;
                }else{
                    $arrVar[$k] = self::htmlspecialchars($v);
                }
            }

            return $arrVar;
        }else{
            return htmlspecialchars($var);
        }
    }
}

class Cache {

    public static function build($key,Array $data) {
        $toStr = '<?php'.PHP_EOL . 'return ' .var_export($data,TRUE).';';
        $fileSize = file_put_contents(Koala::getenv('cache').'/'.$key.'.php',$toStr,LOCK_EX);
        if(!$fileSize){
            return false;
        }
        return true;
    }

    public static function load($key) {
        return @include_once(Koala::getenv('cache').'/'.$key.'.php');
    }

    public static function delete($key) {
        $cache_file = Koala::getenv('cache').'/'.$key.'.php';

        if(!file_exists($cache_file)) {
            return false;
        }
        return unlink($cache_file);
    }
}


class Mysql {

    private $db = null;
    public static $debug = false;
    protected $tableName = ''; //表名
    protected $_where = '';
    protected $_raw = '';
    private $_params = [];

    public function __construct($params,$identify='default') {
        $this->_params = $params;
        //默认的db link
        $this->db = $this->getConnection($this->_params);
    }

    public function resource() {
        if(is_object($this->db)) {
            return $this->db;
        }
        koala::throwing('koalaError','Database connection does not exist');
    }

    public function getConnection(Array $opt) {
        $_db = @new \mysqli($opt['host'],$opt['user'],$opt['passwd']);
        if (mysqli_connect_errno()) {
            koala::throwing('koalaError','database connect error!');
        }
        
        $_db->set_charset($opt['charset']);
        $_db->select_db($opt['database']); 
        return $_db;
    }
    
    public function selectDb($_db){
        $this->resource()->select_db($_db);
    }

    public function query($sql) {

        if(self::$debug) {
            print_r(debug_backtrace());
            echo("<pre>".$sql."</pre>");
            exit;
        }

        if(!($result = $this->resource()->query($sql))){
            koala::throwing('koalaError',$this->resource()->error);
        }
        return $result;
    }

    public function num_rows($sql) {
        $result = $this->query($sql);   
        return $result->num_rows;
    }


    public function fetch_assoc($resource) {
        return $resource->fetch_assoc();
    }


    public function fetchAll($sql){
        $result = array();
        $rs = $this->query($sql);
        while($row = $rs->fetch_assoc()){
            $result[] = $row;
        }
        return $result;
    }


    public function getOne($sql) {
        $rs = $this->query($sql . ' LIMIT 1');
        $row = $rs->fetch_assoc();
        return $row;
    }
    
    public function getFirstField($sql) {
        $rs = $this->query($sql);
        $row = $rs->fetch_row();
        return $row[0];
    }

    public function insert_id() {
        return $this->resource()->insert_id;
    }

    public function close() {
        $this->db->close();
    }

    public function table($tableName) {
        $this->tableName = $tableName;
        return $this;
    }

    public function getTable() {
        return $this->tableName;
    }

    //重新选择数据库
    public function change($dbName) {
        $this->selectDb($dbName);
        return $this;
    }

    public function insert(Array $data) {

        list($field,$value) = $this->__getWhereField($data);
        
        $this->query(sprintf("INSERT INTO %s (%s) VALUES(%s)",$this->getTable(),$field,$value));
        return $this->insert_id();
    }

    public function where() {
        $args = func_get_args();
        $templet = array_shift($args);

        $this->_where = ' WHERE ' . vsprintf($templet,$args);

        return $this;
    }

    private function __getWhere() {
        return $this->_where ? $this->_where : $this->_raw;
    }

    public function limit($start,$end=0) {
        if($end){
            $_sql = " LIMIT $start,$end";
        }else{
            $_sql = " LIMIT $start";
        }

        $this->_where = $this->__getWhere() . $_sql;
        return $this;
    }

    public function delete() {

        $this->__isThereWhere();
        $this->query(sprintf("DELETE FROM %s %s",$this->getTable(),$this->__getWhere()));
        $this->__clearWhere();
    }

    public function has() {
        $this->__isThereWhere();
        $rowsAmount = $this->num_rows(sprintf("SELECT * FROM  %s %s",$this->getTable(),$this->__getWhere()));
        $this->__clearWhere();  
        return $rowsAmount;
    }

    public function count() {
        $row = $this->getFirstField(sprintf("SELECT Count(*) FROM  %s %s",$this->getTable(),$this->__getWhere()));
        $this->__clearWhere();  
        return $row;        
    }

    public function raw() {

        $args = func_get_args();

        if(func_num_args() > 1){
            $templet = array_shift($args);

            $this->_raw = vsprintf($templet,$args);
        }else{
            $this->_raw = $args[0];
        }

        return $this;
    }

    public function find($fields='') {

        $field = is_array($fields) ? $this->__getField($fields) : '*';

        $result = $this->fetchAll(sprintf("SELECT %s FROM %s %s",$field,$this->getTable(),$this->__getWhere()));
        $this->__clearWhere();

        return $result;
    }

    public function findOne($fields='') {
        $field = is_array($fields) ? $this->__getField($fields) : '*';

        $result = $this->getOne(sprintf("SELECT %s FROM %s %s",$field,$this->getTable(),$this->__getWhere()));
        $this->__clearWhere();

        return $result;
    }

    public function update($data) {

        if(is_array($data)) {
            $this->__isThereWhere();
            $pair = $this->__getFieldValuePair($data);
        }else{
            $args = func_get_args();
            array_shift($args);

            $pair = vsprintf($data,$args);
        }
        
        $this->query(sprintf("UPDATE %s SET  %s %s",$this->getTable(),$pair,$this->__getWhere()));
        $this->__clearWhere();

    }

    private function __isThereWhere() {
        if(!$this->_where){
            koala::throwing('koalaError','皇上~您忘记写 sql的条件了: where');
        }
    }

    private function __clearWhere() {
        $this->_where = '';
    }

    private function __sqlType($param) {
        if(is_string($param)) {
            return "'".$param."'";
        }
        return $param;
    }

    private function __getWhereField($data) {
        $_field = $_value = [];

        foreach($data as $k => $v) {
            $_field[] = $k;
            $_value[] = $this->__sqlType($v);
        }

        $field = implode(',',$_field);
        $value = implode(',',$_value);

        return [$field,$value];
    }

    private function __getFieldValuePair($data) {
        $_pair = [];

        foreach($data as $k => $v) {
            $_pair[] = $k . '=' . $this->__sqlType($v);
        }

        return implode(',', $_pair);
    }

    private function __getField($data) {
        return implode(',',$data);
    }
}

class Database {

    private static $_o = [];

    public static function getConnection($db,callable $func){
        if(!isset(self::$_o[$db])){
            self::$_o[$db] = $func();
        }
        return self::$_o[$db];
    }

    public static function __callStatic($db,$arguments) {

        if(!isset(self::$_o[$db])){
            koala::throwing('koalaError',"No database connection:$db");
        }
        
        return self::$_o[$db];
    }
}

trait dbServer {
    public function db($server='db') {
        return Database::$server();
    }   
}

class Table extends Mysql { 
    public function __construct() { }

    use dbServer;
}

class Module extends Mysql {
    public function __construct() { }

    use dbServer;
}

