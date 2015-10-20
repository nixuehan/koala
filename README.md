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

1

```php
Koala::go([
    'controller_dir' => 'mygod',  //控制器目录
    'view_dir' 		 => '模板目录', //模板目录
    'online'		 => '运行模式', // true or false 
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





