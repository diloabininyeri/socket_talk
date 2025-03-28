### Talk with the web socket

```console
composer require zeus/socket_talk
```
**using**

```php

require_once 'vendor/autoload.php';

$webSocket=new \Zeus\SocketTalk\WebSocket('wss://echo.websocket.org');

$webSocket->connect();
//$webSocket->isConnected();
$webSocket->send(json_encode(['type'=>'ping']));
echo $webSocket->read();
//$webSocket->disconnect();
```