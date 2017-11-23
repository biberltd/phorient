<?php

namespace BiberLtd\Bundle\Phorient\Services;

use GuzzleHttp\Client;

class OrientRest
{
    /**
     * @var Client $client
     */
	public $client;
	public $host;
	public $port;
	public $database;
	public $username;
	public $password;
	public $connectionStr;

    /**
     * OrientRest constructor.
     * @param string|null $host
     * @param int $port
     * @param string|null $database
     * @param array|null $auth
     * @param bool $secure
     */
	public function __construct(string $host = null, int $port = 2480, string $database = null, array $auth = null, bool $secure = false){
        $this->host = $host ?? 'localhost';
        $this->database = $database ?? 'localhost';
        $auth ?? ['username' => 'root', 'password' => 'root'];
        $this->username = $auth['username'];
        $this->password = $auth['password'];
        $this->port = $port;
        $protocol = $secure == true ? 'https://' : 'http://';
        $this->connectionStr = $protocol.$this->host.':'.$this->port;
        $this->client = new Client();
	}

    /**
     *
     */
	public function connect(){
	    $endPoint = '/connect/'.$this->database;
        $response = $this->client->get($this->connectionStr.$endPoint);
    }
}
