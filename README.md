# Server Sent Event 协程客户端

Wheakerd 提供了对 Server Sent Event Client 的封装，可基于 [wheakerd/sse-client](https://github.com/wheakerd/sse-client) 组件对
SSE (server sent event) 进行访问。

## 安装

```bash
composer require wheakerd/sse-client
```

## 使用

组件提供了一个 `Wheakerd\SseClient\Client` 来创建客户端，我们直接通过代码来演示一下：

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Wheakerd\SseClient\Client;


final class IndexController
{
    public function index()
    {
        $client = new Client('https://www.example.com', 0.5);
        
        if (!$client->isConnected()) {
            return '无法连接到服务器';
        }
        
        //  如果第三方要求鉴权
        $sseClient->setHeader('Authorization', 'Bearer your token');
        
        $res = $sseClient->send(
			[
				'data' => '...',
			],
		);
		
		//  如果发送失败
		if (false === $send) {
		    //  获取错误原因
			return $client->sta();
		}
		
		while (true) {
			$chunk = $client->recv();
			
			//  全等于空 直接关闭连接
			if ('' === $chunk) {
			    $client->close();
			}
			
			$chunkData = $client->decode($chunk);

			if (null === $chunkData) {
				continue;
			}
			if (false === $chunkData) {
				break;
			}

			$eventStreamResponse->success('message', $chunk);
		}

		$eventStreamResponse->close();
    }
}
```