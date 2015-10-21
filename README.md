# koala 不是动物...

经常遇到个很纠结的问题，做项目是用 yii 好呢 还是 laravel好呢.. 但真用起来了又感觉牛刀砍蚊子..不得力呀。为了方便自己项目使用。就弄了那么一个东西。 这个框架~就只有一个文件。因为以前写python 的时候。最喜欢的框架是 [Bottle](http://www.bottlepy.org/docs/dev/index.html)

###建议的目录结构：

controller 控制器

module  数据模块

views  模板

index.php  入口文件

koala.php  koala框架

首先得有一个启动文件 index.php ，然后只需要几行代码，就可以驱动这个框架：

```php
use koala\Koala;

require 'koala.php';

Koala::route([
    '/' => 'demo->test',
]);

Koala::go();
```

# php版本要求

koala 必须 php5.4以上版本。建议使用 php5.6以上版本

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

#控制器

路由对应的就是控制器。那么我们看下控制器是怎么写的，就拿上面的 两个路由  '/test' 和 '/admin/member' 做为例子

控制器默认目录是 controller。 可以通过下面方式进行一些设置。

```php
Koala::go([
    'controller_dir' => 'mygod',  //控制器目录
    'view_dir' 		 => '模板目录', //模板目录
    'mode'		 => 'dev', // 运行模式 dev or online ... 
]);

```

'/test' 的控制器。 路径在 controller/demo.php  注意：控制器文件名也就是类名。

```php
namespace controller;
use koala\Response;

class Demo{

    public function index() {
        Response::write('index');
    }

    public function test1() {
        Response::write('test1');
    }

    public function test2($age) {
        Response::write('test2' . $age);
    }
}
```

'/admin/member' 的控制器。 路径在 controller/admin/member.php

```php
namespace controller\admin;
use koala\Response;

class Member{

    public function signin() {
        Response::write('signin');
    }
}
```

#过滤器

在路由上，其实我们可以控制得更多。比如我们要控制这个路由，只能是POST请求。我们可以这样做，首先在路由书写上

```php
Koala::route([
    '/' => 'demo->index',

    'post' => [
        '/check' => 'demo->check'
    ]

]);
```

然后在  koala::go() 之前，进行 'post' 的 过滤器注册:

```php
koala::filter('post',function(){
    return Request::isPost();
});
```

在进入 '/check' 之前会先执行'post' 过滤器的匿名函数。这个很好理解是吧。 过滤器返回要注意，
如果返回是 true 那么程序会继续往下执行，如果返回是false 那程序到此中止。


#异常拦截

提供一个重写内置异常的办法。 notFound 、koalaError

常见我们需要一个好看的 404页面。 其他的异常，在  Koala::go(['mode' => 'online']) 时，而又没有重置方法的时候会直接中止程序运行并输出内置错误页面

```php
koala::map('notFound',function(){
    include 'errors/404.html';
});
```

```php
koala::map('koalaError',function(){
    include 'errors/koalaError.html';
});
```

#全局注册对象 class O

有时候我们需要全局注册一个对象，方便我们在任何控制器、模块里进行共享调用。我们可以在 index.php入口文件里进行注册对象

```php
use koala\O;

O::register('fetch',function(){
    include 'utils/fetch.php';
    return new \Fetch();
});
```

然后我们可以在其他地方进行调用

```php
O::fetch()->get();
```

初始化一些对象时候，我们希望单例。可以这样做

```php
$member = O::instance('\module\bbs\member');
```

O 类也提供了一个便捷实例化模块类的方法

```php
$member = O::module(bbs\member);
$member->signin('asdfasdf');
```

#事件回调

希望在执行 koala::go() 之前初始化和执行完成后执行一些我们的方法。可以这样做

```php
Koala::init(function(){
    Config::load("config.php");
});

Koala::finish(function(){
    echo('xx');
});
```

我们在index.php 启动文件里经常要初始化一些逻辑。那就要这样做

```php
Koala::init(function(){
    Config::load("config.php");

    Database::getConnection('write',function(){
        return new Mysql([
            'host' => Config::get('host'),
            'user' => Config::get('user'),
            'passwd' => Config::get('passwd'),
            'database' => Config::get('database'),
            'charset' => Config::get('charset')
        ]);
    });

    Database::getConnection('read',function(){
        return new Mysql([
            'host' => Config::get('host'),
            'user' => Config::get('user'),
            'passwd' => Config::get('passwd'),
            'database' => Config::get('database'),
            'charset' => Config::get('charset')
        ]);
    });
});
```

#配置文件 class Config

加载配置文件

```php
Config::load("config.php");
```

配置文件

```php
return [

    //数据库连接
    'host' => 'rdseb7bsdsd.qq.om',

    'database' => 'koala',

    'user' => 'koala',

    'passwd' => 'asdkoalaxdeoi',

    'charset' => 'utf8'
];
```
当然我们也可以一次加载多个配置文件

```php
Config::load("database.php");
Config::load("default.php");
```

获取配置选项 

```php
$host = Config::get('host');

$conf = Config::get();
```

动态添加配置选项

```php
Config::set('salt','asdfwerwqe');
```

#共享变量 class G

模块间传递变量

```php
G::set([
    'member_name' => 'yeziqing',
    'member_age' => 18
]);


G::set('member_name','yeziqi');

G::get();

G::get('member_age');
```

# GET、POST请求  class Request

做了基本防SQL注入。初始类变量类型很重要，因为最后变量类型就是初始化时候定的。

```php
$post = Request::post([
    'age' => 0,  // 那么传递过来的 $_POST['age'] = 'test' 也好。类型最后会转成 int
    'access_token' => ''
]);

$access_token = $post['access_token'];


// /test?access_token=afdadsf

$get = Request::get([
    'contents' => '',
    'access_token' => ''
]);

$access_token = $get['access_token'];
```

获取上传的文件信息,是没有做安全防护的。最好对 $file['name'] 、和 $file['type'] 进行有害字符过滤和转移

```php
$file = Request::file();
```

获取原始的 GET 或者 POST 。 ps: 没做任何安全处理，自行做处理

```php
$get = Request::get();

$post = Request::post();
```

#安全过滤  class Security

主要两个方法

针对sql处理的

```php
$get = Security::sqlVar($access_token);

$get = Security::sqlVar([
    'age' => 19,
    'content' => 'swiddki'
]);
```

针对模板输出的

```php
$content = Security::htmlVar($content);
```

#模板  class Response

内置了一个简单的php模板。支持两种方式。

模板布局模式

1\. 新建一个母布局  默认名字 : main.layout.php    子布局默认变量名: $layout_contents 存放的是 子布局的内容。

```php
我是头罗
<?=$layout_contents?>
我是尾巴
```

2\. 在新建一个子布局  demo.tpl.php

```php
我是<?=$myname?>
```

3\. 在逻辑里输出

```php
Response::layout('demo',[
    'myname' => 'nixuehan'
]);
```

最后输出

```php
我是头罗
我是nixuehan
我是尾巴
```

想改变默认的母布局文件名或子布局的内容变量名。可以这样做

```php
Response::init([
    'layout' => 'base',
    'layout_contents' => 'layout_var'
]);
```

模板模式

直接输出模板文件

```php
Response::render('demo',[
    'myname' => 'nixuehan'
]);

// __myname 这个变量进行 html实体化。 防xss
Response::render('demo',[
    '__myname' => 'nixuehan'
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
    Response::render('f1');
}
```

4\. 然后模板里面调用

```php
<?php use koala\Response?>

<?php Response::fragments('demo->f1')?>

我是<?=$myname?>
```


输出json

```php
Response::json(['myname' => 'yeziqing','age' => 18]);
```

跳转

```php
Response::redirect('/test');
```

一般输出

```php
Response::write('wakaka');
```

#会话 class Session

```php
$session = new Session;
$session->start(function(){
    ini_set('session.save_hander', 'memcache');
    ini_set('session.save_path', 'tcp://127.0.0.1:11211');
});


$session->get('xxoo');

$session->set('xxoo','jjyy');
```

#数据模块

一般我们是不会在控制器里面写数据库操作逻辑的。推荐这样

在 module 目录里建一个数据模块文件。 注意: 文件名和类名要相同
```php
namespace module;

use koala\Config;
use koala\Module;

class Company extends Module{

    public function authorization($access_token) {
      return $this->db()->table('company')
                             ->where("access_token='%s'",$access_token)
                             ->findOne();
      
    }
}
```

然后我们就可以在控制器里调用，不需要 提前include 数据模块文件。 ps:其他任何符合这样规则的类文件，都可以这样直接调用

```php
$company = new \module\company();
```

但如果能单例那更好哦。所以，我们一般这样用。这样永远只有一个实例

```php
$session = O::instance('\module\Company');
```

#数据库

1\.首先连接数据库。我们开两个连接 读写分离

```php
Koala::init(function(){

    Database::getConnection('writable',function(){
        return new Mysql([
            'host' => Config::get('host'),
            'user' => Config::get('user'),
            'passwd' => Config::get('passwd'),
            'database' => Config::get('database'),
            'charset' => Config::get('charset')
        ]);
    });

    Database::getConnection('readable',function(){
        return new Mysql([
            'host' => Config::get('host'),
            'user' => Config::get('user'),
            'passwd' => Config::get('passwd'),
            'database' => Config::get('database'),
            'charset' => Config::get('charset')
        ]);
    });
});
```

2\.然后在模块里这样玩

```php
namespace module\bbs;

use koala\Config;
use koala\Module;

class Member extends Module{

    public function signin($access_token) {
      return $this->db('readable')->table('company')
                             ->where("access_token='%s'",$access_token)
                             ->findOne();
      
    }
}
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
Database::writable()->query("INSERT INTO forms VALUES('a','b')");
Database::writable()->insert_id();
```

```php
Database::writable()->query("DELETE FROM forms");
```

```php
Database::readable()->fetchAll("SELECT * FROM forms WHERE access_token = 'adfawer'");
```

```php
Database::readable()->getOne("SELECT * FROM forms WHERE access_token = 'adfawer'");
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
