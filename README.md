# Telegram 讨论组机器人. Telegram Discussion Group Bot. 

本机器人替代了Telegram原生的频道讨论组, 并加以改进.

This bot replaces and improves the original telegram channel discussion group.

## 特性 | Features.
相比于原生的频道讨论组, 额外支持的有:

Compared with the original discussion group, we additionally support:

1. 多对一转发 | Many to one forwarding.

    在原生的讨论组中, 每一个群组只能成为一个频道的讨论组. 使用本机器人即可支持多个频道共用同一个讨论组.
    
    A group can only be one channel's discussion group. Channels using this bot can share a discussion group.

2. 保留对应的回复层级 | Keep the reply information.
    ![image](https://user-images.githubusercontent.com/37735580/73739555-693a4080-4781-11ea-9d3d-d99a2603ab9c.png)
    
3. 当讨论组的人回复了机器人转发的消息时, 通知被转发的消息的发送者. | Mention the poster when the message forwarded by the bot is replied in the discussion group.
    ![image](https://user-images.githubusercontent.com/37735580/73739639-9c7ccf80-4781-11ea-8fa3-766e92e0eb14.png)

4. 目前支持文字, 单一的 Photo, 以及单一的 Video 的内容修改. | Support message edition in texts and caption of photos and videos.

5. 群组中 @admins 功能 | @admins in discussion group.
    ![image](https://user-images.githubusercontent.com/37735580/73739706-b6b6ad80-4781-11ea-9987-f17c9d7f950e.png)

6. 对于不可编辑以及暂不支持转发的消息会分成两条消息转发 | As for polls, video notes and other unmodifiable messages will be directly forward.
    ![image](https://user-images.githubusercontent.com/37735580/73739781-d8179980-4781-11ea-8bef-be4767e3cd67.png)

7. 带有 #noforward 的 文字, 视频, 单个图片将不会被转发. | Text, photo, video with a hash tag #noforward won't be forwarded.

## 缺点 | Unsupported.
如果使用本机器人替代原生讨论组, 失去的功能有:

If you use this bot instead of the original discussion group, you can't:

1. 当源消息被删除时, 自动删除转发的消息 | Automatically delete the forwarded message 
    由于 Telegram 机器人的API的限制, 消息被删除时机器人不会收到通知, 因此无法自动删除转发的消息.
    Due to the limitations of the telegram bot API, the bot isn't notified when a message is deleted, therefor the bot can't delete the forwarded message.

2. 目前, 由多个图片组成的 mediagroup 在转发的时候会分开转发. | For now, mediagroup will be forwarded separately.

3. 原生的一键加入讨论群按钮 | The original "Join discussion group" button in the channel.


## 需求 | Requirements.
1. PHP 7.2 及以上 | PHP 7.2 minimum.
2. Mysql 数据库. | A Mysql database.

## 安装 | Installation && Deployment.

1. 按照 [Laravel](//github.com/laravel/laravel) 准备环境. | Prepare your environment as [Laravel](//github.com/laravel/laravel).
2. clone代码, 并把http根目录设置为public文件夹, 关闭 open_base_dir 防跨站相关选项. 配置https. | Make "public" the base dir of the http server, turn off open_base_dir and apply a https certificate.
3. `composer install`
4. 修改.env中的配置 (仅需最上面的网址, mysql相关, 以及最下面的三个) | Configure the ".env" file as below
5. `php artisan migrate`
6. 将 config/tgbot.php.example 重命名为 config/tgbot.php | Rename config/tgbot.php.example to config/tgbot.php
7. 修改config/tgbot.php中的配置 | Configure the bot as below
8. 访问 /setWebhook 来配置webhook. | Visit /setWebhook to setup webhook.
9. Enjoy!

## 配置 | Configuration.

### ENV
```dotenv
APP_URL=http://localhost # 网站根目录的https://地址.

# MYSQL 相关
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

TELEGRAM_BOT_TOKEN=123456789:xxxxxxx # Bot token
TGBOT_PROXY= # 代理, 不需要可留空 | Proxy used visiting telegram's API. Empty if not needed.
TGBOT_WEBHOOK=/webhook # Webhook地址, 建议随机一点.  | A random address for the webhook.

```

### config/tgbot.php

```php
<?php

return [
    'proxy' => env("TGBOT_PROXY",""),
    'webhook_url' => env("TGBOT_WEBHOOK","/webhook"),
    'channels' => [
        123 => 234, // 频道id => 群组id | Channel ID => Group ID
        ...
    ],
    'groups' => [
        234 => [ // 群组ID | Group ID.
            345,456,567 // 管理员ID | Admins ID
        ]
    ],
    'admins' => [
        "Firstname Lastname" => 345, // 管理员姓名 对应管理员id. | Admin's fullname => admin ID.
        ...
    ],
    'admin_nickname' => [
        345 => "管理员1昵称", // Admin nickname
        ...
    ],
    'self' => 12345678, // 机器人ID | Bot id
    'templates' => [
        'text' => "<a href=\"tg://user?id={userId}\">{user}</a> posted at {channel}:\n{text}",
        'reply' => "Let me help you <a href=\"tg://user?id={userId}\">@{user}</a>.",
    ],
    'pin' => true, // 是否置顶转发的消息 | Pin the forwarded message or not
];
```

可以通过转发频道或群组消息给 @userinfobot 来获取频道或者群组ID.

You can forward a message to @userinfobot to obtain a group or a channel ID.


## 注意 | Attention.
1. 机器人必须有在群组中发送, 修改, pin(如果开启了) 消息的权限.

    The robot must have permission to send, edit, pin (If enabled) message in the group.
    
2. 频道必须开启 Author signature功能.

    The Channel must enable author signature.

3. 配置中的管理员全名必须正确, 否则会无法辨别Channel中消息的发送者.

    The admin's fullname in the configuration must be correct to detect who sent message in the channel.
