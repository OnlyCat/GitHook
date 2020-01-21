# GIT HOOK 上线程序
## 一、介绍
### 1. 项目介绍
    通过git hook进行代码上线，通过配置githook, 用户在进行git操作时可以进行代码同步操作。

### 2. 目录介绍
<pre>
├── README.md
├── hook.php            # 项目启动入口
├── action              # 入口目录
│   └── BootStrap.php   # 引导文件
├── config              # 配置目录
│   ├── env.ini         # 全局配置
│   ├── local.ini       # 本地配置
│   ├── mail.ini        # 邮件配置
│   └── remote.ini      # 远程配置
├── library             # 扩展目录
│   ├── Apply.php       # 应用文件
│   ├── Curl.php        # Curl封装
│   ├── Git.php         # Git处理
│   ├── Mail.php        # 邮件处理
|── vendor              # 扩展目录
│   └── mailer          # 邮件扩展
│       ├── Mailer.php
│       └── Smtp.php
└── script              # 脚本目录
    └── sync_code.sh
</pre>    



## 二、项目配置

### 0. Nginx配置
```ini
server {
    listen       80;
    server_name  127.0.0.1;
    index index.html index.php;

    location ~ \.php$ {
	root  /www/tools/tools/ci;
    	fastcgi_pass unix:/run/php/php7.3-fpm.sock;
    	fastcgi_param  SCRIPT_FILENAME    $document_root$fastcgi_script_name;
   	include         fastcgi_params;
    }
}
```


### 1. 主配置
```ini
; schema:master/slave
; master 模式会将githook消息进行扩散到removte.ini中配置的机器
; slave 只做接收处理
schema=master
[security]
token=12345678901234567890
user=admin@163.com
password=abcd1234

```




### 2. 本地配置
```ini
; 项目概况
[base:common]
resources.project.name = "github"
resources.project.url = "https://github.com/OnlyCat/GitHook.git"
resources.project.path = "/www/git"
; git hook 监听事件
[base:common:event]
resources.event.push=allow
resources.event.tag_push=deny
resources.event.issue=deny
resources.event.note=deny
resources.event.merge_request=deny
resources.event.wiki_page=deny
resources.event.pipeline=deny
```

### 3.远程配置
```ini
[removte_1]
resources.project.name = "github"
resources.project.url = "http://10.0.0.1:80/hook.php"

[removte_2]
resources.project.name = "github"
resources.project.url = "http://10.0.0.1:80/hook.php"

[removte_3]
resources.project.name = "github"
resources.project.url = "http://10.0.0.1:80/hook.php"

[removte_4]
resources.project.name = "github"
resources.project.url = "http://10.0.0.1:80/hook.php"
```

### 4. 邮件配置
```ini
[transport]
host=smtp.exmail.qq.com
user=admin@163.com
passwd=13456
port=465
[source]
address=admin@163.com
title=上线服务通知
[target]
address=1235@163.com
```