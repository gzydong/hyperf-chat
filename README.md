# Lumen-IM

## 1、简介

Lumen-IM 是一个网页版在线即时聊天项目，前端使用 Element-ui + Vue ，后端使用 PHP + Swoole 进行开发。项目后端采用 Hyperf 框架。

- 基于 Swoole WebSocket 服务做消息即时推送
- 支持私聊及群聊
- 支持聊天消息类型有文本、代码块、图片及其它类型文件，并支持文件下载
- 支持聊天消息撤回、删除或批量删除、转发消息（逐条转发、合并转发）及群投票功能
- 支持编写个人笔记、支持笔记分享(好友或群)

## 2、项目DEMO

- 地址： [http://im.gzydong.club](http://im.gzydong.club)
- 账号： 18798272054 或 18798272055
- 密码： admin123

## 3、环境要求

- PHP >= 7.3
- MySQL >= 5.7
- Redis >= 5.0
- Swoole >= 4.5
- OpenSSL
- JSON
- PDO

## 4、相关文档

[Hyperf 框架](https://hyperf.wiki/2.1/#/README)

## 5、项目安装

1. 下载源码包
2. 安装框架依赖包执行 `composer install` 命令 [项目根目录下执行]
2. 拷贝项目根目录下 .env.example 文件为 .env 并正确配置相关参数（mysql、redis）
3. 执行项目安装命令(安装数据库及测试数据)  `php bin/hyperf.php system:install`
4. 启动运行项目 `php bin/hyperf.php start`

注 ：[项目运行之前请确保 MySQL、Redis 及 Nginx 服务]

## Nginx 相关配置(代理 swoole 服务)

##### 配置 Http 服务

```
upstream hyperf_http {
    # Hyperf HTTP Server 的 IP 及 端口
    server 127.0.0.1:9503;
}

server {
    # 监听端口
    listen 80;
    # 绑定的域名，填写您的域名
    server_name im-serve.xxx.com;

    location / {
        client_max_body_size    20m;
        # 将客户端的 Host 和 IP 信息一并转发到对应节点
        proxy_set_header Host $http_host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

        # 转发Cookie，设置 SameSite
        proxy_cookie_path / "/; secure; HttpOnly; SameSite=strict";

        # 执行代理访问真实服务器
        proxy_pass http://hyperf_http;
    }
}
```

##### 配置 WebSocket 服务

```
# 至少需要一个 Hyperf 节点，多个配置多行
upstream hyperf_websocket {
    server 127.0.0.1:9504;
}

server {
    listen 80;
    server_name im-socket.xxx.com;

    location / {
        # WebSocket Header
        proxy_http_version 1.1;
        proxy_set_header Upgrade websocket;
        proxy_set_header Connection "Upgrade";

        # 将客户端的 Host 和 IP 信息一并转发到对应节点
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $http_host;

        # 客户端与服务端无交互 60s 后自动断开连接，请根据实际业务场景设置
        proxy_read_timeout 60s ;

        # 执行代理访问真实服务器
        proxy_pass http://hyperf_websocket;
    }
}
```

##### 配置图片域名

```
server {
    listen 80;
    server_name  im-img.xxx.xxx;
    index  index.html;

    # 默认禁止访问
    location / {
        deny all;
    }

    # 只允许 访问 images 文件夹下的文件
    location ^~ /media/{
        # 设置目录别名（确保是项目上传文件目录）
        # 例如 upload_dir = /www/data/lumenim
        # 此时应配置 alias /www/data/lumenim/media/
        
        alias /www/data/lumenim/media/;

        # 设置缓存时间(3天)
        expires 3d;

        # 关闭访问日志
        access_log off;
    }
}
```

### 前端地址

https://github.com/gzydong/LumenIM

### 注意事项

1. 请确保 PHP 安装 openssl、redis 扩展
2. 请确保 Swoole 扩展开启 openssl 扩展

```
[root@iZuf6cs69fbc86cwpu9iv3Z vhost]# php --ri swoole
swoole
Swoole => enabled
Author => Swoole Team <team@swoole.com>
Version => 4.5.9
...
openssl => OpenSSL 1.0.2k-fips  26 Jan 2017 (请确保此处开启)
```
