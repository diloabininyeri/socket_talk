<?php

namespace Zeus\SocketTalk;


use Random\RandomException;

class HandShake
{
    /**
     * @var resource|null $socket
     */
    private $socket;

    private bool $isConnected = false;

    /**
     * @param string $url
     */
    public function __construct(private readonly string $url)
    {}


    
    public function initiateConnection(): self
    {
        [$scheme, $host, $port, $path] = $this->parseUrl();

        $errorNo = 0;
        $errorString = '';
        $this->createSocket($scheme, $host, $port, $errorNo, $errorString);

        if (!$this->socket) {
            throw new SocketUnableConnectException("Unable to connect to WebSocket server: $errorString ($errorNo)");
        }
        
        fwrite($this->socket, $this->generateHeaders($path, $host));
        $response = fread($this->socket, 1500);

        if (str_contains($response, ' 101 ')) {
            $this->isConnected = true;
        }

        return $this;
    }

    /***
     * @return bool
     */
    public function isConnectionSuccessful(): bool
    {
        return $this->isConnected;
    }

    /***
     * @return resource|null
     */
    public function getConnectionSocket()
    {
        return $this->socket;
    }

    /**
     * @return array
     */
    private function parseUrl(): array
    {
        $urlParts = parse_url($this->url);
        $scheme = $urlParts['scheme'];
        $host = $urlParts['host'];
        $port = $urlParts['port'] ?? ($scheme === 'wss' ? 443 : 80);
        $path = $urlParts['path'] ?? '/';
        return [$scheme, $host, $port, $path];
    }

    /***
     * @param mixed $path
     * @param mixed $host
     * @return string
     * @throws RandomException
     */
    private function generateHeaders(mixed $path, mixed $host): string
    {
        $key=base64_encode(random_bytes(16));
        $headers = [
            "GET $path HTTP/1.1",
            "Host: $host",
            "Upgrade: websocket",
            "Connection: Upgrade",
            "Sec-WebSocket-Key: $key",
            "Sec-WebSocket-Version: 13",
            "\r\n",
        ];

        return implode("\r\n", $headers);
    }

    /**
     * @param mixed $scheme
     * @param mixed $host
     * @param mixed $port
     * @param int $errorNo
     * @param string $errorString
     * @return void
     *
     */
    private function createSocket(mixed $scheme, mixed $host, mixed $port, int &$errorNo, string &$errorString): void
    {
        $this->socket = stream_socket_client(
            ($scheme === 'wss' ? 'ssl://' : 'tcp://') . $host . ':' . $port,
            $errorNo,
            $errorString,
            timeout: 30,
            flags: STREAM_CLIENT_CONNECT,
            context: stream_context_create([
                'socket' => [
                    'timeout' => 10,
                ],
            ])
        );
    }

    /**
     * @return bool
     */
    public function disconnect(): bool
    {
        if ($this->isConnected) {
            $close = fclose($this->socket);
            $this->socket = null;
            $this->isConnected = false;
            return $close;
        }
        return false;
    }
}
