<?php

class GameServer
{

    private int $port;

    private $socket;

    private array $connectedSockets = [];

    /**
     *
     * {@inheritdoc}
     */
    public function __construct(int $port)
    {
        $this->port = $port;
        $this->socket = socket_create_listen($this->port);
    }

    public function getConnectedSockets(): array
    {
        return $this->connectedSockets;
    }
    public function removeConnectedSocket($socket){
        $tempArray = $this->getConnectedSockets();
        foreach ($tempArray as $key => $temp){
            if($temp === $socket){
                unset($tempArray[$key]);
                $this->connectedSockets = $tempArray;
            }
        }
    }
    public function addToConnectedSockets($socket){
        array_push($this->connectedSockets, $socket);
    }
    
    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): void
    {
        $this->port = port;
    }

    public function openNewSocket()
    {
        socket_close($this->socket);
        $this->socket = socket_create_listen($this->port);
    }

    public function getSocket()
    {
        return $this->socket;
    }
}
