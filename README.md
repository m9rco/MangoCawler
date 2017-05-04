 MangoCawler - 基于Swoole实现的多进程爬虫方案
===============

> 知行合一，学以致用

## What I Do
- 基于Swoole 多进程的爬虫方案

![img](http://i4.buimg.com/567571/f37c0f0cf61d0ecc.jpg)

## Requirement
- [PHP 7 +](http://php.net/manual/zh/migration71.new-features.php)
- [Swoole](https://www.zhihu.com/question/41832866)
- [Composer 1.0](http://pkg.phpcomposer.com/)

## SQL

```
CREATE TABLE `damai_list` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(200) DEFAULT '' COMMENT 'Url',
  `province` varchar(20) DEFAULT '' COMMENT '省份',
  `city` varchar(20) DEFAULT '' COMMENT '城市',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5915 DEFAULT CHARSET=utf8;

```

## 更改配置文件
```
# \drive\CrawlerInit.php

define('M_CRAWLER_URL', 'https://venue.damai.cn/search.aspx?cityID=0&k=0&keyword=&pageIndex=\d');
define('M_DB_HOST', '127.0.0.1');
define('M_DB_NAME', '');
define('M_DB_USER', '');
define('M_DB_PWD' , '');
```

## 使用方式

```
composer install

// 首先开启服务端启用连接池
php \drive\worker\Server.php

// 开始爬吧
php index.php

```

## 纠错

如果大家发现有什么不对的地方，可以发起一个[issue](https://github.com/PuShaoWei/Mango16/issues)或者[pull request](https://github.com/PuShaoWei/Mango16/pulls),我会及时纠正

> 补充:发起pull request的commit message请参考文章[Commit message 和 Change log 编写指南](http://www.ruanyifeng.com/blog/2016/01/commit_message_change_log.html)


