# koala 不是动物...

经常遇到个很纠结的问题，做项目是用 yii 好呢 还是 laravel好呢.. 但真用起来了又感觉牛刀砍蚊子..不得力呀。为了方便自己项目使用。就弄了那么一个东西。 这个框架~就只有一个文件。因为以前写python 的时候。最喜欢的框架是 Bottle http://www.bottlepy.org/docs/dev/index.html 。

首先得有一个启动文件 index.php ，然后只需要几行代码，就可以驱动这个框架：

```php
use koala\Koala;

require 'koala.php';

Koala::route([
    '/test' => 'demo->test',
]);

Koala::go();

```
