#HttpSQS (基于HTTP协议的简单队列服务-ps:新公司项目有使用此包，原作者包安装有问题，此为修改) 使用示例

## 安装
    $ composer require youthage/httpsqs

## 初始化操作
```php
<?php
use Httpsqs\HttpSQS;

$httpsqs = new HttpSQS($host, $port, $auth, $charset);

```

## 入队操作

    $httpsqs->put('test','example1'); //true


## 出队操作

可以使用`get()`方法，该方法只返回队列中的值：

```php
//get: 如果队列为空 返回 "HTTPSQS_GET_END"
$result = $httpsqs->get('test'); // example1
```
也可以使用`gets()`方法，该方法不仅返回队列中的值还有该值对应的位置：

```php
//gets: 队列为空返回 array('pos'=>null, 'data'=> "HTTPSQS_GET_END" );

$result = $httpsqs->gets('test'); // array('pos'=>1, 'data'=>'example1');
```

## 查看队列状态

```php
$result = $httpsqs->status('test');
```

默认返回的结果是字符串形式：

```php
//字符串形式
 HTTP Simple Queue Service v1.7 
------------------------------ 
Queue Name: newiep 
Maximum number of queues: 1000000 
Put position of queue (1st lap): 13 
Get position of queue (1st lap): 12 
Number of unread queue: 1 
```

也支持json格式：

```php
//json 格式
$result = $httpsqs->status('test', 'status_json');
```
返回：
```php
{"name":"test","maxqueue":1000000,"putpos":45,"putlap":1,"getpos":6,"getlap":1,"unread":39}
```

## 查看队列指定位置的值

```php
$result = $httpsqs->view('test', 1);  //  example1
```

## 重置指定队列
```php
//将队列重置，从开始位置重新写入

$result = $httpsqs->reset('test'); //true
```

## 更改指定队列的最大队列数量,默认 100万条
```php
//修改的数值需满足条件：（$num >=10 && $num <= 1000000000）

$result = $httpsqs->maxqueue('test', 1000); //true

$result = $httpsqs->maxqueue('test', 1); //false
```

## 不停止服务的情况下，修改定时刷新内存缓冲区内容到磁盘的间隔时间
```php
//　默认间隔时间：5秒

$result = $foo->synctime(10); //true
```