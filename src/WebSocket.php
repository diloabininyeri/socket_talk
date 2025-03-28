<?php

namespace Zeus\SocketTalk;


use JsonException;

/**
 *
 */
class WebSocket
{
    /**
     * @var HandShake
     */
    private HandShake $handshake;

    /**
     * @var WebSocketCodec
     */
    private WebSocketCodec $codec;

    /**
     * @param string $uri
     */
    public function __construct(private readonly string $uri)
    {
        $this->handshake = new HandShake($uri);
        $this->codec = new WebSocketCodec();
    }

    /**
     * @return bool
     */
    public function connect(): bool
    {
        $this->handshake->initiateConnection();
        $isConnect = $this->handshake->isConnectionSuccessful();
        if ($isConnect) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function disconnect(): bool
    {
        return fclose($this->handshake->getConnectionSocket());
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->handshake->isConnectionSuccessful();
    }

    /**
     * @param string $data
     * @return bool
     */
    public function send(string $data): bool
    {
        if (!$this->handshake->isConnectionSuccessful()) {
            return false;
        }

        return fwrite($this->handshake->getConnectionSocket(), $this->codec->encode($data));
    }


    /**
     * @return string|null
     */
    public function read(): ?string
    {
        if (!$this->handshake->isConnectionSuccessful()) {
            return null;
        }

        $data = WebSocketFrameReader::read($this->handshake->getConnectionSocket());

        if ($data === '') {
            return  null;
        }

        return $this->codec->decode($data)['payload'];

    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }
}
