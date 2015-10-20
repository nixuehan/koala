# koala 不是动物...

经常遇到个很纠结的问题，做项目是用 yii 好呢 还是 laravel好呢.. 但真用起来了又感觉牛刀砍蚊子..不得力呀。为了方便自己项目使用。就弄了那么一个东西。 这个框架~就只有一个文件。因为以前写python 的时候。最喜欢的框架是 [Bottle](http://www.bottlepy.org/docs/dev/index.html)

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
    'mode'		 => '运行模式', // dev or online ... 
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

#全局注册对象

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

我们经常打交道的数据库连接

```php
O::register('db',function(){
    return new Mysql([
        'host' => Config::get('host'),
        'user' => Config::get('user'),
        'passwd' => Config::get('passwd'),
        'database' => Config::get('database'),
        'charset' => Config::get('charset')
    ]);
});
```

这样调用。 ps:当然对于数据库操作来说不建议这样使用。

```php
O::db()->query(sql);
```

#预执行

希望在执行 koala::go() 之前和之后 执行一些我们的方法。可以这样做

```php
Koala::before('go',function(){
    Config::load("config.php");
});

Koala::after('go',function(){
    echo('xx');
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

# GET 、 POST  class Request

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
















