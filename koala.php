<?php
namespace koala;

class X{
    const author = '逆雪寒';
    const version = '1.0.1';
    const license = "MIT";
}

define('YES',1);
define('NO',0);

set_exception_handler(function($e){
    $className = get_class($e);

    if($fn = Koala::getErrorhandler('DefaultException')){
        $fn();exit;
    }

    if($fn = Koala::getErrorhandler($className)){
        $fn();exit;
    }

    $msg = sprintf('<h2>Koala Error</h2>'.
            '<pre>%s <br/>%s</pre>',
            $e->getMessage(),
            $e->getTraceAsString()
        );
    Koala::$app->Response->halt(500,$msg);
});

class C { }

class KoalaException extends \Exception{}
class MysqlException extends \PDOException{}

function call($fn) {
    return $fn();
}

class O{

    private static $_object = [];

    public function register($name,callable $func) {
        if(!isset(self::$_object[$name])){
            self::$_object[$name] = $func();
        }
    }

    public function __set($name,$value) {
        if(isset(self::$_object[$name])){
            throw new KoalaException("container variable $name ready exist!");
        }
        self::$_object[$name] = $value;
    }

    public function __get($className) {
        if(!isset(self::$_object[$className])){
            throw new KoalaException("please register $className");
        }
        return self::$_object[$className];        
    }

    public function instance($_class,Array $args=[]) {

        if(!isset(self::$_object[$_class])) {

            if(strpos($_class,'/') !== false) {

                if(!koala::$app->help->fileExists($_class . '.php')){
                    throw new KoalaException("Class file  is not found : $_class.php");
                }
                include $_class . '.php';
                $cls = substr(strrchr($_class, '/'), 1);

                $ref = new \ReflectionClass($cls);
                self::$_object[$_class] = $ref->newInstanceArgs($args);
            }else{
                $ref = new \ReflectionClass($_class);
                self::$_object[$_class] = $ref->newInstanceArgs($args);
            }
        }

        return self::$_object[$_class];
    }

    public function module($_class,Array $args=[]) {
        if(!isset(self::$_object[$_class])) {
            $_class = "\module\\" . $_class;

            $ref = new \ReflectionClass($_class);
            self::$_object[$_class] = $ref->newInstanceArgs($args);
        }
        return self::$_object[$_class];
    }

    public function functions($_class,Array $args=[]) {
        if(!isset(self::$_object[$_class])) {
            $_class = '\functions\\' . $_class;
            $ref = new \ReflectionClass($_class);
            self::$_object[$_class] = $ref->newInstanceArgs($args);
        }
        return self::$_object[$_class];
    }

    public function table($_class,Array $args=[]) {
        if(!isset(self::$_object[$_class])) {
            $_class = '\table\\' . $_class;
            $ref = new \ReflectionClass($_class);
            self::$_object[$_class] = $ref->newInstanceArgs($args);
        }
        return self::$_object[$_class];
    }
}

class G {
    private $_var = [];

    public function get($namespace,$_var='') {
        if(!$_var) return isset($this->_var[$namespace]) ? $this->_var[$namespace] : ''; 
        return isset($this->_var[$namespace][$_var]) ? $this->_var[$namespace][$_var] : '';     
    }

    public function set($namespace,Array $var=[]) {
        foreach($var as $k => $v) {
            $this->_var[$namespace][$k] = $v;
        }
    }
}

class App{

    private static $_obj = [];
    private static $_O = NULL;

    public function __toString() {
        return 'koala manual https://github.com/nixuehan/koala';
    }

    public static function object() {
        if(!is_object(self::$_O)) {
            self::$_O = new self;
        }
        return self::$_O;
    }

    public function __get($className) {
        if(!isset(self::$_obj[$className])){
            $cls = __NAMESPACE__ ."\\".$className;

            self::$_obj[$className] = new $cls;
        }
        return self::$_obj[$className];
    }
}

class Koala{

    private static $_route = [];
    private static $_env = [];
    private static $_exception = [];
    private static $_filter = [];
    private static $_anchor_before = [];
    private static $_anchor_after = [];
    private static $_fn = [];
    private static $_o = [];
    public  static $app = NULL;

    private function __construct() {}
    private function __destruct() {}
    private function __clone() {}

    public static  function route(Array $rule) {
        $_rule = [];
        foreach($rule as $k => $v) {
            if(is_array($v)){
                if(array_key_exists($k,self::$_filter)){

                    self::$_fn[$k] = self::$_filter[$k];
                    self::$_filter[$k] = $v;

                }

                $_rule = $_rule + $v;
            }else{
                $_rule[$k] = $v;
            }
        }

        self::$_route = self::$_route + $_rule;
    }

    public static  function go(Array $config=[]) {
        
        self::$app = App::object();

        self::$_env['root_dir'] = !isset($config['root_dir']) ? __DIR__ : $config['root_dir'];
        self::$_env['controller_dir'] = !isset($config['controller_dir']) ? 'controller' : $config['controller_dir'];
        self::$_env['cli'] = PHP_SAPI === 'cli' ? true : false;
        self::$_env['request_method'] = isset($_SERVER['REQUEST_METHOD']) && !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        self::$_env['view_dir'] = isset($config['view_dir']) ? $config['view_dir'] : 'views';
        self::$_env['mode'] = isset($config['mode']) ? $config['mode'] : 'dev';
        self::$_env['cache'] = isset($config['cache']) ? $config['cache'] : 'cache';
        self::$_env['csrf'] = isset($config['csrf']) ? $config['csrf'] : false;
        self::$_env['timezone'] = isset($config['timezone']) ? $config['timezone'] : 'Asia/Shanghai';
        self::$_env['charset'] = isset($config['charset']) ? $config['charset'] : 'utf-8';

        date_default_timezone_set(self::$_env['timezone']);
        header("Content-Type: text/html; charset=".self::$_env['charset']);

        if(self::$_env['cli'] && self::$_env['root_dir']) {
            set_include_path(get_include_path() . PATH_SEPARATOR . self::$_env['root_dir']);
        }
        
        self::requestUri();
        
        unset($_REQUEST);

        spl_autoload_register(array(__CLASS__,'classAutoLoadPath'));
        
        $_fn = koala::getInit();
        if($_fn) $_fn();

        foreach(self::$_route as $pattern => $class_method){
            $param = self::__matchControllerMethod($pattern);
            if(is_array($param)){

                self::loadController($class_method,$param);
                goto after;
            } 
        }

        $request_uri = trim(self::$_env['request_uri'],"/");
        
        if($request_uri == ""){
            $class = "index" . "->" . "index";
        }else if(strpos($request_uri,"/")){
            $class = substr_replace($request_uri,"->",strrpos($request_uri,"/"),1);
        }else{
            $class = "index" . "->" . $request_uri;
        }

        if(self::autoLoadController($class)){
            goto after;
        }

        throw new KoalaException('Can not find the controller');

after:
        $_fn = koala::getFinish();
        if($_fn) $_fn();
        exit;
    }

    public static function classAutoLoadPath($class) {

        if(strpos($class,'\\') !== false) {
            $class = str_replace('\\','/',$class);
        }

        $file = strtolower($class) . '.php';
    
        if(self::$app->help->fileExists($file)) {
            require_once($file);
            //throw new KoalaException("Class  is not found : $class");
        }        
    }

    public static function __include($file,$once=false) {
        if(self::$app->help->fileExists($file)){
            if($once){
                return include_once($file);
            }else{
                return include($file);
            }
        }

        return false;
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

    public static function filter($name,$fr,$fn = '') {
        self::$_filter[$name] = $fr;
        self::$_fn[$name] = $fn;
    }

    public static function getFilter($class_method) {
        //全局过滤器
        if(!self::getEnv('cli') && isset(self::$_filter['global'])){
            $fn = self::$_filter['global'];
            is_callable($fn) && $fn();
        }

        array_walk(self::$_filter,function($clme,$name) use ($class_method){
            foreach($clme as $cm){
                if($cm[strlen($cm)-1] == '>') {
                    if(stripos($class_method,$cm) === 0) {
                        $fn = self::$_fn[$name];
                        is_callable($fn) && $fn();
                    }     
                }else{
                    if($class_method == $cm) {
                        $fn = self::$_fn[$name];
                        is_callable($fn) && $fn();
                    }                   
                }
            }         
        });
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

    public static function errorhandler($ac) {
        self::$_exception = $ac;
    }

    public static function getErrorhandler($ac) {
        return isset(self::$_exception[$ac]) ? self::$_exception[$ac] : false;
    }
    
    private static function requestUri() {
        if(self::$_env['cli']){

           if(empty($_SERVER['argv'])){
                throw new KoalaException("register_argc_argv must be on");
           }

           if(!isset($_SERVER['argv'][1]) || empty($_SERVER['argv'][1])){
                throw new KoalaException("Missing path");
           }

            self::$_env['request_uri'] = $_SERVER['argv'][1];
        }else{

            self::$_env['request_uri'] = isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
            
        }
        
        $params = [];
        $args = parse_url(self::$_env['request_uri']);

        if (isset($args['query'])) {
            parse_str($args['query'], $params);
        }

        $_ru = strstr(self::$_env['request_uri'],'?',true);
        if($_ru) self::$_env['request_uri'] = $_ru;


        $_GET = &$params;
        
    }

    private static function __matchControllerMethod($regular) {

        if($regular == self::$_env['request_uri']) {
            return [];
        }

        if(preg_match('/^'.addcslashes($regular,"\57").'$/i',self::$_env['request_uri'],$matchs)){
            array_shift($matchs);
            return $matchs;
        }

        return false;
    }
    
    private static function autoLoadController($class_method) {
 
        list($class,$method) = explode('->',$class_method,2);

        self::setEnv('controller',$class,'index');
        self::setEnv('method',$method,'index');

        self::getFilter($class_method);

        $controller_dir = self::getEnv('controller_dir');

        $class_file = $controller_dir.'/'.$class.'.php';

        if(!self::__include($class_file,true)){
            return false;
        }

        $class = $controller_dir.'\\'.$class;
        $class = str_replace("/","\\",$class);
        if(class_exists($class)) {  
            $obj = new $class(); 
        }else{
            return false;
        }
        
        if(!method_exists($obj,$method)) {
            return false;
        }

        if($content = call_user_func_array(array($obj,$method),[])){
            print($content);
        }
       
        return true;
    }

    public static function loadController($class_method,$param) {

        $data = explode('->',$class_method,2);
        if(count($data) > 1){
            list($class,$method) = $data;

            self::setEnv('controller',$class,'index');
            self::setEnv('method',$method,'index');

            self::getFilter($class_method);


            $controller_dir = self::getEnv('controller_dir');

            $class_file = $controller_dir.'/'.$class.'.php';


            if(!self::__include($class_file,true)){
                throw new KoalaException("Class file is not found : $class_file");
            }

            $class = $controller_dir.'\\'.$class;
            $class = str_replace("/","\\",$class);
            if(class_exists($class)) {  
                $obj = new $class(); 
            }else{
                throw new KoalaException("Class $class not found");
            }
            
            if(!method_exists($obj,$method)) {
                throw new KoalaException("method $method not found");
            }

            $pm = [];
            foreach($param as $k => $v){
                if(is_int($k)){
                    $pm[] = $v;
                }
            }
            
            if($content = call_user_func_array(array($obj,$method),$pm)){
                print($content);
            }
        }else{
            self::getFilter($class_method);
            throw new KoalaException("Controller exception");
        }
    }
}

class Help {

    public function fileExists($file) {
        return koala::getEnv('cli') ? $this->__fileExists($file)  :  file_exists($file);
    }

    private function __fileExists($file){
        if(!file_exists($file) && koala::getEnv('cli')){ 
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
}

//日志
class Log {

    public function path($file) {

        $this->path = $file;
        return $this;
    }

    private function init($content) {
        if(is_array($content)){
            $content = var_export($content,true);
        }

        if(!$this->path){
            throw new KoalaException('log file does not exist');
        }

        $front = "===== " . date('Y-m-d h:m:s',time())." =====";
        return $front . PHP_EOL . $content . PHP_EOL;  
    }

    public function info($content) {
        $content = $this->init($content);
        file_put_contents($this->path, $content,FILE_APPEND | LOCK_EX);
    }
}

//输出
class Response {

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
            throw new KoalaException('Invalid status code');
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

    public function output($message = '',$code = 200) {
        $this->status($code)
             ->write($message)
             ->send();
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

    public function json($data, $code = 200, $encode = true) {

        $json = $encode ? json_encode($data) : $data;
        //fix  json php to java
        $json = str_replace(['[]','{}','""'],['null','null','null'],$json);

        $this->status($code)
             ->header('Content-Type', 'application/json')
             ->write($json)
             ->send();
    }


    public function redirect($url,$param=[]) {

        if(strpos($url,"http") !== false) {
            $this->status(303)
                         ->header('Location', $url)
                         ->write($url)
                         ->send();           
        }else{
            koala::loadController($url,$param);
        }
    }
}

class Html{
    private static function hash() {
        list($usec, $sec) = explode(' ', microtime());
        srand((float) $sec + ((float) $usec * 100000));
        return md5(rand());
    }

    public static function csrf() {

        if(Koala::getEnv('csrf')) {
            $token = self::hash();

            koala::$app->cookie->set('__csrf__',$token);

            return sprintf('<input type="hidden" name="__csrf__" value="%s" />',$token);
        }
        return '';
    }
}

class Config{

    private $_config = [];

    public function load($filename) {

        $filename = $filename . '.php';
        
        if(!koala::$app->help->fileExists($filename)){
            throw new KoalaException("Configuration file does not exist:$filename");
        }

        $opt = Koala::__include($filename,true);

        if(is_array($opt)){
            $this->_config = array_merge($this->_config,$opt);
        }
    }

    public function set($section,$var,$value) {
        $this->_config[$section][$var] = $value;
    }

    public function get($section='',$var='',$default='') {
        if($section === '') return $this->_config;
        if($var === '') return isset($this->_config[$section]) ? $this->_config[$section] : [];
        return isset($this->_config[$section][$var]) && !empty($this->_config[$section][$var]) ? $this->_config[$section][$var] : $default;
    }
}

class View{

    private $_layouts = [];
    private $_opt = ['layout' => 'main'];
    private $_view_dir = '';

    public function __construct() {
        $this->_view_dir = koala::getEnv('view_dir');
    }

    public function opt($options = []) {

        foreach($options as $k => $v){
            $this->_opt[$k] = $v;
        }
    }

    public function main($layout) {
        $this->_opt = ['layout' => $layout];
        return $this;
    }

    public function layout($tpl,$T=[]) {

        if(isset($this->_opt['vars'])) {
            $T = $T + $this->_opt['vars'];
        }

        if(!empty($T)){
            $T = Security::htmlVar($T);
            extract($T);
        }

        $_f = $this->_view_dir . '/' .$tpl.'.tpl.php';

        if(!koala::$app->help->fileExists($_f)){
            throw new KoalaException(sprintf("Can't find view file %s",$_f));
        }

        $this->_layouts[isset($this->_opt['layout_content']) ? $this->_opt['layout_content'] : "layout_content"] = $this->__include($_f,$T);     

        if(!empty($this->_layouts)){
            extract($this->_layouts);
        }

        if(isset($this->_opt['layout'])){
            
            $_f = $this->_view_dir . '/' .$this->_opt['layout'].'.layout.php';

            if(!koala::$app->help->fileExists($_f)){
                throw new KoalaException(sprintf("Can't find view file %s",$_f));
            }
            
            include_once($_f);        
        }
    }

    public function render($tpl,$T=[]) {

        if(isset($this->_opt['vars'])) {
            $T = $T + $this->_opt['vars'];
        }

        if(!empty($T)){
            $T = Security::htmlVar($T);
            extract($T);
        }

        $_f = $this->_view_dir . '/' .$tpl.'.tpl.php';

        if(!koala::$app->help->fileExists($_f)){
            throw new KoalaException(sprintf("Can't find view file %s",$_f));
        }

        include_once($_f);
    }

    private function __include($filename,$T=[]) {

        if(!empty($T)){
            extract($T);
        }
        ob_start();
        include_once($filename);
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }

    public function widget($class_method,Array $param=[]) {

        if(strpos($class_method,"->")){
            koala::loadController($class_method,$param);
        }else{
            $this->render($class_method,$param);
        }
    }
}

//请求
class Request {

    private $validOn = false;

    private function __process(&$container,Array $params = []) {

        if(empty($params)){
            return $container;
        }

        foreach($params as $k => $v){

            if($this->validOn == true && is_callable($v)){
                $container[$k] = isset($container[$k]) ? $container[$k] : '';
                $container[$k] = call_user_func($v,$container[$k]);
            }else if(is_numeric($v)){
                $container[$k] = isset($container[$k]) ? intval($container[$k]) : $v;
            }else if(is_string($v)){
                $container[$k] = isset($container[$k]) ? Security::sqlVar($container[$k]) : $v;
            }else if(is_array($v)) {
                $container[$k] = isset($container[$k]) ? Security::sqlVar($container[$k]) : $v;
            }
        }
        return $container;
    }

    public function validateFilePath($path) {
        $this->validOn = true;
        if(!koala::$app->help->fileExists($path)){
            throw new KoalaException("validate file is not found : $path");
        }
        require $path;
    }

    public function get(Array $params = []) {

        if(empty($params)){
           $_GET = Security::sqlVar($_GET);
            return $_GET;
        }

        return $this->__process($_GET,$params);
    }

    public function post(Array $params = []) {

        if(empty($params)){
           $_POST = Security::sqlVar($_POST);
            return $_POST;
        }

        return $this->__process($_POST,$params);
    }

    public function checkCsrf() {

        if(Koala::getEnv('csrf')){
            $token = koala::$app->cookie->get('__csrf__');

            $post = $this->post([
                '__csrf__' => ''
            ]);

            if($post['__csrf__'] != $token) {
                throw new KoalaException("csrf");
            }
        }
    }

    public function file() {
        return $_FILES;
    }

    public function header($var = '') {
        return koala::getServer($var);
    }

    public function __get($name) {
        if(!in_array($name,['isPost','cli','ip'])) {
            throw new KoalaException("$name Method is not allowed to call");
        }

        return $this->$name();
    }

    private function isPost() {
        return koala::getenv("request_method") == 'POST' ? true : false;
    }

    private function cli() {
        return koala::getEnv('cli');
    }

    private function ip() {

    }
}

class Cookie {
    
    public $opt = [
        'domain' => '.test.com',
        'secure' => false,
        'httponly' => true
    ];

    public function opt($options=[]) {
        foreach($options as $k => $v){
            $this->opt[$k] = $v;
        }
    }

    public function get($name = '') {
        if(!$name) {
            return $_COOKIE;
        }

        return isset($_COOKIE[$name]) ? Security::sqlVar($_COOKIE[$name]) : '';
    }

    public function set($name,$value,$expire=0) {
        setcookie($name, $value, $expire, '/',$this->opt['domain'],$this->opt['secure'],$this->opt['httponly']);
    }

    public function del($name) {
        setcookie($name,"",time()-1);
        unset($_COOKIE[$name]);
    }
}

class Session {
    
    public function start($fn='') {
        if(is_callable($fn)){
            $fn();
        }
        if(session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
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

    public function set(Array $s) {
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

    public function __construct() {
        $this->cacheDir = Koala::getenv('cache');
    }

    public function build($key,Array $data) {
        $toStr = '<?php'.PHP_EOL . 'return ' .var_export($data,TRUE).';';
        $fileSize = file_put_contents($this->cacheDir.'/'.$key.'.php',$toStr,LOCK_EX);
        if(!$fileSize){
            return false;
        }
        return true;
    }

    public function get($key) {
        return @include_once($this->cacheDir.'/'.$key.'.php');
    }

    public function delete($key) {
        $cache_file = $this->cacheDir.'/'.$key.'.php';

        if(!file_exists($cache_file)) {
            return false;
        }
        return unlink($cache_file);
    }
}

class DB {
    public $debug = false;
    private $_db = null;
    private $_orm = null;
    private $_params = [];
    protected $tableName = ''; //表名
    protected $_where = '';
    protected $_raw = '';
    private $_original = '';
    private $var = '';
    
    public function __construct(Array $params) {
        $this->_params = $params;
        $this->connect($params);
    }

    private function connect(Array $params) {
        try{
            $dsn = sprintf("mysql:host=%s;dbname=%s;port=%d;charset=%s", $params['host'],$params['database'],3306,$params['charset']);
            $this->_db = new \PDO($dsn, $params['user'], $params['passwd']);
            $this->_db->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
            $this->_db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function __errorInfo($stmt) {
        return implode('|' , $stmt->errorInfo());
    }

    public function currentDb() {
        $stmt = $this->_db->query("select database()");
        return $stmt->fetchColumn();
    }

    //数据库切换
    public function changeDb($dbname) {

        $this->_original = $this->currentDb();
        $this->query("use ".$dbname);
    }

    public function undoDb() {
        $this->query("use ".$this->_original);
    }
    
    public function base() {
        return $this->_db;
    }

    public function transaction(callable $fn) {
        $this->_db->beginTransaction();
        $result = $fn();

        if(!$result){
            $this->_db->rollBack();
            return false;
        }
        return $this->_db->commit();
    }

    public function query($sql,Array $parameters = []) {
        $stmt = $this->_db->prepare($sql);

        if(!$stmt->execute($parameters)){
            //判断超时重新连接数据库
            if(!$this->connect($this->_params)){
                throw new PDOException();
            }
            $stmt->execute($parameters);
        }
        return $stmt;
    }
    
    public function fetchAll($sql) {
        $stmt = $this->query($sql);
        $result = $stmt->fetchAll();
        $stmt->closeCursor();
        return $result;
    }
    
    public function num_rows($sql) {
        $stmt = $this->query($sql);
        $result = $stmt->rowCount();
        $stmt->closeCursor();
        return $result;
    }
    
    public function getOne($sql) {
        $stmt = $this->query($sql . ' LIMIT 1');
        $result = $stmt->fetch();
        $stmt->closeCursor();
        return $result;
    }
    
    public function insert_id() {
        return $this->_db->lastInsertId();
    }
    
    
    public function field(Array $data) {
        $this->data =  $data;
        return $this;
    }

    public function __get($name) {
        $this->var = $name;
        return $this;
    }

    public function _int() {
        return isset($this->data[$this->var]) ? $this->data[$this->var]  : 0;
    }

    public function _string() {
        return isset($this->data[$this->var]) ? $this->data[$this->var]  : '';
    }

    public function table($tableName) {
        $this->tableName = $tableName;
        return $this;
    }

    public function getTable() {
        return $this->tableName;
    }

    public function insert(Array $data) {

        list($field,$value) = $this->__getWhereField($data);

        $sql = sprintf("INSERT INTO %s (%s) VALUES(%s)",$this->getTable(),$field,$value);

        if($this->debug){
            print($sql);exit;
            $this->debug = false;
        }

        $this->query($sql);

        return $this->insert_id();
    }

    public function where() {
        $args = func_get_args();
        $templet = array_shift($args);

        $this->_where = ' WHERE ' . vsprintf($templet,$args);

        return $this;
    }

    public function _and() {
        $args = func_get_args();
        $templet = array_shift($args);

        $this->_where = $this->_where . ' AND ' . vsprintf($templet,$args);

        return $this;
    }

    public function _or() {
        $args = func_get_args();
        $templet = array_shift($args);

        $this->_where = $this->_where . ' OR ' . vsprintf($templet,$args);

        return $this;
    }

    public function in($field,Array $values) {

        $val = '';
        foreach($values as $value) {
            $val .= "'".$value."',";
        }
        $val = substr($val,0,-1);

        $this->_where = ' WHERE '.$field . " IN (".$val.")";
        return $this;
    }

    public function desc() {
        $arr = func_get_args();

        $str = implode(',',array_map(function($k){
            return $k . ' DESC';
        },$arr));

        $this->_where = $this->_where . ' ORDER BY ' . $str;
        return $this;      
    }

    public function asc() {
        $arr = func_get_args();

        $str = implode(',',array_map(function($k){
            return $k . ' ASC';
        },$arr));

        $this->_where = $this->_where . ' ORDER BY ' . $str;
        return $this;  
    }

    public function for_update() {
        $this->_where = $this->_where . ' FOR UPDATE';
        return $this;       
    }

    public function group() {
        $arr = func_get_args();
        $this->_where = $this->_where . ' GROUP BY ' . $this->__getField($arr);
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
        $sql = sprintf("DELETE FROM %s %s",$this->getTable(),$this->__getWhere());
        if($this->debug){
            print($sql);exit;
            $this->debug = false;
        }
        $result = $this->query($sql);
        $this->__clearWhere();
        return $result;
    }

    public function has() {
        $this->__isThereWhere();

        $sql = sprintf("SELECT * FROM  %s %s",$this->getTable(),$this->__getWhere());
        if($this->debug){
            print($sql);exit;
            $this->debug = false;
        }        
        $rowsAmount = $this->num_rows($sql);
        $this->__clearWhere();  
        return $rowsAmount;
    }

    public function count() {
        $sql = sprintf("SELECT Count(*) FROM  %s %s",$this->getTable(),$this->__getWhere());
        if($this->debug){
            print($sql);exit;
            $this->debug = false;
        }  

        $stmt = $this->query($sql);
        $row = $stmt->fetchColumn();
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

        $sql = sprintf("SELECT %s FROM %s %s",$field,$this->getTable(),$this->__getWhere());

        if($this->debug){
            print($sql);exit;
            $this->debug = false;
        }  

        $result = $this->fetchAll($sql);
        $this->__clearWhere();

        return $result;
    }

    public function findOne($fields='') {
        $field = is_array($fields) ? $this->__getField($fields) : '*';

        $sql = sprintf("SELECT %s FROM %s %s",$field,$this->getTable(),$this->__getWhere());
        if($this->debug){
            print($sql);exit;
            $this->debug = false;
        }  

        $result = $this->getOne($sql);
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

        $sql = sprintf("UPDATE %s SET  %s %s",$this->getTable(),$pair,$this->__getWhere());
        if($this->debug){
            print($sql);exit;
            $this->debug = false;
        }  

        $result = $this->query($sql);
        $this->__clearWhere();
        return $result;
    }

    private function __isThereWhere() {
        if(!$this->_where && !$this->_raw){

            throw new MysqlException('sql conditions miss!');
        }
    }

    private function __clearWhere() {
        $this->_where = $this->_raw = '';
    }

    private function __sqlType($param) {
        if(!is_numeric($param)) {
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
   
   public function instance($var,callable $func) {
        if(!isset(self::$_o[$var])){
            self::$_o[$var] = $func();
        }
        return self::$_o[$var];
   }

   public function db($var = 'default') {

        if(!isset(self::$_o[$var])){
            throw new KoalaException("No database connection:$var");
        }
        return self::$_o[$var];
   }
}

class T extends Database {
    public function __construct() { }
}

class M extends Database {
    public function __construct() { }
}