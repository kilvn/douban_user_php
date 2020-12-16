# douban_user_php
php采集豆瓣用户资料：id、姓名、头像

代码在 douban.php 文件

**laravel ^8.0**

**querylist ^4.2**

# 为什么会有这个需求？
做游戏项目刚开始是没有用户的，使用这些真实资料来创建机器人用户

# 依赖包

```shell
# querylist 主库
composer require jaeger/querylist
#  Curl多线程采集插件
composer require jaeger/querylist-curl-multi
# 转换URL相对路径到绝对路径插件
composer require jaeger/querylist-absolute-url
```
