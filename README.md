# koala 不是动物...

经常遇到个很纠结的问题，做项目是用 yii 好呢 还是 laravel好呢.. 但真用起来了又感觉牛刀砍蚊子..不得力呀。为了方便自己项目使用。就弄了那么一个东西。 这个框架~就只有一个文件。因为以前写python 的时候。最喜欢的框架是 [Bottle](http://www.bottlepy.org/docs/dev/index.html)

###建议的目录结构：

controller 控制器

module  数据模块

static 静态文件

views  模板

index.php  入口文件

koala.php  koala框架

首先得有一个启动文件 index.php ，然后只需要几行代码，就可以驱动这个框架：

```php
require './koala.php';
use koala\Koala;

Koala::route([
    '/' => 'demo->test',
]);

Koala::go();
```

# php版本要求

koala 必须 php5.4以上版本。

#License

MIT. 随便拿去玩


#如何运行起来

1\. nginx 下载、安装、配置~ 这些我不想说了。。 


2\. 配置nginx 。

```
server {
    location / {
        try_files $uri $uri/ /index.php;
    }
}
```

3\. 创建一个入口文件  `index.php` 


首先引入 koala 框架

```php
require 'koala.php';
use koala\Koala;
```


然后定义路由和控制器的映射关系

```php
Koala::route([
    '/' => 'demo->test',
]);
```

最后运行这个框架

```
Koala::go();
```

初始化参数可选
```php
Koala::go([
    'controller_dir' => 'mygod',  //自定义控制器目录
    'view_dir'       => '模板目录', //自定义模板目录
    'mode'       => 'dev', // 运行模式 dev or online ...
    'cache'      => 'data/cache', //文件缓存目录 默认 cache
    'root_dir' => __DIR__, //当要使用cli，就要设置网站根目录
    'log' => 'log' //日志存储目录路径
]);

```

#路由

路由分两部分，url地址和它所对应的控制器方法。url支持正则。路由规则建议用独立的文件 比如 route.php 来管理。

'url' => '控制器的类名->类方法'

```php
Koala::route([
    '/' => 'demo->index',
    '/test' => 'demo->test1',
    '/admin/member' => 'admin/member->test1',
    '/test/(?P<doubi>[0-9]+)' => 'demo->test2'
]);
```

带拦截器的路由
```php
Koala::route([
    'auth' => [
        '/test' => 'demo->test1',
        '/admin/member' => 'admin/member->test1',
        '/test/(?P<doubi>[0-9]+)' => 'demo->test2'
    ]
]);


Koala::filter([

    'auth' => function() {
        $get = Koala::$app->Request()->get([
            'user' => ''
        ]);

        if($get['user'] != 'nixuehan') {
            Koala::$app->Response()->halt(500,"不允许");
        }   
    }

]);
```

拦截器可以多个

```php
Koala::route([
    'post >> auth' => [
        '/user/signin' => 'user->signin'
    ]
]);

Koala::filter([

    'post' => function(){
        if(!Koala::$app->Request()->isPost){
            Koala::$app->Response()->halt(500,"不允许");
        }
    },


    'auth' => function() {
        $get = Koala::$app->Request()->get([
            'user' => ''
        ]);

        if($get['user'] != 'nixuehan') {
            Koala::$app->Response()->halt(500,"不允许");
        }   
    }

]);
```

#控制器

路由对应的就是控制器。那么我们看下控制器是怎么写的，就拿两个路由  '/test' 和 '/admin/member' 做为例子

控制器默认目录是 controller。 可以通过下面方式进行一些设置。

```

'/test' 的控制器。 路径在 controller/demo.php  注意：控制器文件名也就是类名。

```php
namespace controller;

use koala\Koala;

/**
 * 用户
 */
class Demo extends \Controller{

    public function __construct() { 
        $this->view = Koala::$app->View();
    }

    /**
     * 登录
     */
    public function signin() {
        echo('ddd');
    }
}
```

'/admin/member' 的控制器。 路径在 controller/admin/member.php

```php
namespace controller\admin;

class Member extends \Controller{

    public function signin() {
        echo('d');
    }
}
```

#过滤器

在路由上，其实我们可以控制得更多。比如我们要控制这个路由，只能是POST请求。我们可以这样做，首先在路由书写上

```php
use koala\Koala;


Koala::filter([

    'post' => function(){
        var_dump(Koala::$app->Request()->isPost);
        exit;
    },


    'auth' => function() {
        $get = Koala::$app->Request()->get([
            'user' => ''
        ]);

        if($get['user'] != 'nixuehan') {
            Koala::$app->Response()->halt(500,"不允许");
        }   
    }

]);
```

在进入 '/check' 之前会先执行'post' 过滤器的匿名函数。这个很好理解是吧。
一个路由是支持多个过滤器的，比如：


```php
Koala::route([

    'post >> xxoo' => [
        '/check' => 'demo->check'
    ]

]);
```

先执行 post 过滤器 再执行 xxoo  过滤器数量不限制


#异常拦截

```php
class NotFoundException extends Exception{ } 

Koala::exception([
    'NotFoundException' => function(){
        include 'errors/404.php';
    }
]);

```
在我们需要抛出错误的地方。抛出自定义的异常
```php
throw new \NotFoundException("找不到用户");
```

#魔术对象变量. 内置的类如下:
```php
var_dump(Koala::$app); //打印下，其实他是个对象

Koala::$app->Container() //实例化容器类 class Container 

Koala::$app->Response() //实例化输出类  class Response

Koala::$app->Request() //实例化请求类 class Request

Koala::$app->Config()   //实例化配置类 class Config

Koala::$app->View() //实例化模板类  class View

Koala::$app->Cookie() //实例化cookie类 class Cookie

Koala::$app->Session() //实例化session类 class Session

Koala::$app->Cache() //实例化文件缓存类 class Cache

Koala::$app->Mysql() //实例化Mysql类 class Mysql
```

#容器对象 class O

有时候我们需要全局注册一个对象，方便我们在任何控制器、模块里进行共享调用。我们可以在 index.php入口文件里进行注册对象

```php
class Member{
    public function update() {
        return 'okay';
    }
}

//全局注册对象
$container = koala::$app->O();

$container->register('member',function(){
    return new Member;;
});


//还可以这样
$container = koala::$app->O();

$container->member = \koala\call(function(){
    return new Member;;
});
```

然后我们可以在其他地方进行调用

```php
koala::$app->O()->member->update();
```

初始化一些对象时候，我们希望单例。可以这样做

```php
$fetch = koala::$app->O()->instance("utils\Fetch");

$sms = koala::$app->O()->instance('utils/sms');
```

Container 容器也提供了一个便捷实例化模块类,表模块类的方法

```php
$member = koala::$app->O()->module("member");
$member->signin('asdfasdf');
```

当然还有表模块

```php
$member = koala::$app->O()->table("member");
$member->signin('asdfasdf');
```

#全局变量容器 class G

全局变量

```php
Koala::$app->G()->set('member',[
    'member_name' => 'yeziqing',
    'member_age' => 18
]);


Koala::$app->G()->get(); //返回所有

Koala::$app->G()->get('member'); 

Koala::$app->G()->get('member','member_age');


#配置文件 class Config

加载配置文件

```php
koala::$app->Config()->load("application");
```

配置文件

```php
return [

    'mysql' => [
        //数据库
        'host' => '10.3.2.1',

        'user' => 'root',

        'passwd' => 'eeess',

        'database' => 'fuddn',

        'charset' => 'utf8',
    ]
];

```
当然我们也可以一次加载多个配置文件

```php
koala::$app->Config()->load("application");
koala::$app->Config()->load("me");
```

获取配置选项 

```php

$host = koala::$app->Config()->get('mysql','host','127.0.0.1')  //加个默认值

$host = koala::$app->Config()->get('mysql','host')

$host = koala::$app->Config()->get('mysql')

$opt = koala::$app->Config()->get()
```

动态添加配置选项

```php
koala::$app->Config()->set('memcache','charset',"t_t!");
```

# GET、POST、Files请求  class Request

做了基本防SQL注入。初始类变量类型很重要，因为最后变量类型就是初始化时候定的。

```php
$get = Koala::$app->Request()->get([
    'userid' => 0,  // $_GET['userid'] 被强制转成数字类型 
    'user' => '', //字符串类型的值
]);
```
上传文件
```php
$file = Koala::$app->Request()->file();
```

获取post 提交

```php
Koala::$app->Request()->validateFilePath('validate.php'); //验证文件路径指定


//validate.php里定义验证函数
namespace Valid;
/**
 * 验证手机号
 */
function mobile($phone) {
    if($phone == '1500778223'){
        exit('不是手机号');
    }
    return $phone;
}


$post = Koala::$app->Request()->post([
    'userid' => "\Valid\mobile",  //验证是否是手机号
    'love' => [] //数组形式传值
]);

//获取头信息
$server = Koala::$app->Request()->header();

$server = Koala::$app->Request()->header('SERVER_ADDR');

Koala::$app->Request()->isPost; //是否是post

Koala::$app->Request()->cli; //是否是cli

Koala::$app->Request()->ip; //请求ip

```

#输出  class Response

```php
Koala::$app->Response()->json(['userid'=>23]); //输出 json
```

跳转

```php
Koala::$app->Response()->redirect('/test'); //内部跳
Koala::$app->Response()->redirect('http://www.baidu.com');
```

一般输出

```php
Koala::$app->Response()->output('wakaka');
```


#模板  class View

内置了一个简单的php模板。支持两种方式。

模板布局模式

1\. 新建一个母布局  默认名字 : main.layout.php    子布局默认变量名: $layout_content 存放的是 子布局的内容。

```php
头
<?=$layout_content?>
尾
```

2\. 在新建一个子布局  demo.tpl.php

```php
我是<?=$myname?>
```

3\. 在逻辑里输出

```php
Koala::$app->View()->layout('kk',[
    'myname' => 'nixuehan'
]);
```

最后输出

```php
头
我是nixuehan
尾
```

想改变默认的母布局文件名或全局子布局的内容变量名:

```php
Koala::$app->View()->opt([
    'layout' => 'base',  //母布局文件名 默认: main.layout.php
    'vars' => [     //全局子布局变量
        'myname' => 'nixuehan'
    ]
]);
```

###传统模板模式

直接输出模板文件

```php
Koala::$app->View()->render("test");

// __myname 这个变量进行 html实体化。 防xss
Koala::$app->View()->render("test",[
    '__myname' => 'nixuehan'   // 带双斜杠开头的模板变量~ 会进行 htmlspecialchars 有效防xss
]);
```

我们用模板模式来实现前面的布局模式的效果

1\. 创建 f1.tpl.php 模板

2\. 设置访问的路由和控制器

```php
Koala::route([
    '/f1' => 'demo->f1'
]);
```

3\. 在控制器类里写方法

```php
public function f1() {
    Koala::$app->View()->render("f1");
}
```

4\. 然后其他模板里面调用

```php
<?php use koala\Koala;?>
test la
<?php Koala::$app->View()->render("jjyy");?>
```

组件模式
```php
Koala::$app->View()->widget('signin>>main->profile'); //  signin::: 意思是，先经过 signin 过滤器再 执行后面的模块
```

csrf防御
```php
Koala::go([
    'csrf' = true
]);
```

模板这里
```php
$csrf = Html::csrf(); //放到表单里
```

控制器判断
```php
$request = Koala::$app->Request()
$request->checkCsrf()
```

#会话 class Session

```php
$session = Koala::$app->Session();
$session->start(function(){
    ini_set('session.save_hander', 'memcache');
    ini_set('session.save_path', 'tcp://127.0.0.1:11211');
});


$session->get('xxoo');

$session->set('xxoo','jjyy');

$session->set('xxoo',[
    'a' => 233,
    'b' => 'sdsd'
]);

$session->del('xxoo');

$session->clear();
```

#cookie class Cookie

```php
Koala::$app->Cookie()->opt([
    'domain' => '.baidu.com',
    'secure' => false,
    'httponly' => true
]);


$all = Koala::$app->Cookie()->get();

$myname = Koala::$app->Cookie()->get('myname');

Koala::$app->Cookie()->set('myname','nixuehan',536000);

Koala::$app->Cookie()->del('myname');
```

#安全方面主要是 sql注入和xss  class Security

```php
$tid = Security::sqlVar($tid);

$data = Security::sqlVar([
    'age' => 19,
    'content' => 'swiddki'
]);

Security::htmlVar($msg);
```

#简单的文件缓存 class Cache

```php

$cache = Koala::$app->Cache();

if(!$cache->build('xxoo',['xx' => 23234])) {
    exit('error');
}

$xxoo = $cache->get('xxoo');

$cache->delete('xxoo');
```

#日志文件 class Log

首先要在 Koala::go([
    'log' => 'log' //日志目录
])


```php
//增量
Koala::$app->Log()
           ->path("core") //日志文件名
           ->write("lllll");

//新建
Koala::$app->Log()
           ->path("core")
           ->put("lllll");  
```

#事件回调

希望在执行 koala::go() 之前初始化和执行完成后执行一些我们的方法。可以这样做

index.php文件
```php
//进入控制器之前
Koala::init(function(){
    //可以在这里做一些初始化的工作
});

//执行完控制器之后
Koala::finish(function(){
    echo('the end');
});
```

我们在index.php 启动文件里经常要初始化一些逻辑。那就要这样做

```php
Koala::init(function(){
    Koala::$app->Database()->getConnection('db',function(){
        $opt = Koala::$app->Config()->get('mysql');
        return new Mysql([
            'host'      => $opt['host'],
            'user'      => $opt['user'],
            'passwd'    => $opt['passwd'],
            'database'  => $opt['database'],
            'charset'   => $opt['charset']
        ]);
    });
});
```

#数据库

1\.首先连接数据库。我们开两个连接 读写分离

```php
Koala::init(function(){
    Koala::$app->Database()->getConnection('db',function(){
        $opt = Koala::$app->Config()->get('mysql');
        return new Mysql([
            'host'      => $opt['host'],
            'user'      => $opt['user'],
            'passwd'    => $opt['passwd'],
            'database'  => $opt['database'],
            'charset'   => $opt['charset']
        ]);
    });
});
```

2\.然后在模块里这样玩

```php
namespace module\bbs;

class Member extends \Module{

    public function signin($access_token) {
      return $this->db()->table('company')
                             ->where("access_token='%s'",$access_token)
                             ->findOne();
      
    }
}

//控制器里调用
Koala::$app->O()
           ->module('member')
           ->infoByMemberid();
```

内置的ORM 各种操作展示

```php
$this->db('readable')->table('company')
                 ->insert([
                    'username' => 'meigui',
                    'age' => 18
                 ]);
```

```php
$this->db('writable')->table('company')
                  ->where("access_token='%s'",$access_token) 
                  ->delete();
```

```php
$this->db('readable')->table('company')
                 ->where("access_token='%s'",$access_token)
                 ->has()
```

```php
$this->db('readable')->table('company')
                 ->where("access_token='%s'",$access_token)
                 ->count()
```

```php
$this->db('writable')->table('company')
                 ->where("access_token='%s'",$access_token)
                 ->find();
```

```php
$this->db('writable')->table('company')
                  ->where("access_token='%s'",$access_token) 
                  ->update([
                    'access_token' => 'asdfe',
                    'age' => 20
                  ]);
```


```php
$this->db('readable')->table('forms')
                 ->raw('ORDER BY pc_pv DESC')
                 ->limit(2, 10)
                 ->find();
```

```php
$this->db('readable')->table('forms')
                 ->raw("Where access_token = '%s' ORDER BY pc_pv DESC",$access_token)
                 ->limit(2, 10)
                 ->find();
```

遇到复杂的SQL 就不适合ORM了。 ps: 记得需要自己防sql注入

```php
Koala::$app->Database()->writable->query("INSERT INTO forms VALUES('a','b')");
Koala::$app->Database()->writable->insert_id();
```

```php
Koala::$app->Database()->writable->query("DELETE FROM forms");
```

```php
Koala::$app->Database()->writable->fetchAll("SELECT * FROM forms WHERE access_token = 'adfawer'");
```

```php
Koala::$app->Database()->writable->getOne("SELECT * FROM forms WHERE access_token = 'adfawer'");
```

```php
Koala::$app->Database()->writable->table('forms_statistics')
                    ->where("fid = %d AND ps_year = %d AND ps_month = %d AND ps_day = %d AND ps_hour = %d",$fid,$year,$month,$day,$hour)
                    ->update(sprintf("%s = %s + 1",$field,$field));
```

遇到更复杂的需求，需要更底层一点的mysqli 方法。比如 事务

```php
$db = $this->db('read')->resource();
$db->autocommit(false);
$db->query("INSERT INTO Language VALUES ('DEU', 'Bavarian', 'F', 11.2)");
$db->commit();
```



#cli

这很简单一样的道理。在服务器上

```php
php -f index.php /test
```
走的是 '/test' 路由
