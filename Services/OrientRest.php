<?php

namespace BiberLtd\Bundle\Phorient\Services;

use GuzzleHttp\Client;
use PhpOrient\Protocols\Binary\Data\Record;

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
    private $token = null;

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
        if(is_null($this->token))
        {
            $res = $this->client->request('GET', $this->connectionStr, [
                'auth' => [$this->username, $this->password]
            ]);

            // echo $res->getBody();die;

            $response = $this->client->get($this->connectionStr.$endPoint);
            dump($response);exit;
        }
    }

    public function query(string $query, $limit=-1,$fetchPlan="*:0")
    {
        $params[ 'limit' ]      = ( !stripos( $query, ' limit ' ) ? $limit : -1 );
        $params[ 'fetch_plan' ] = $fetchPlan;
        return $this->driver->queryAsync($query);
    }

    public function queryAsync(string $query,$params)
    {
        return $this->driver->command($query,$params);
    }

    public function command(string $query,$params)
    {
        $endPoint = '/command/'.$this->database.'/sql/-/-1?format=rid,type,version,class,graph';
        $response = $this->client->get($this->connectionStr.$endPoint);

        $resultSet = (json_decode($response))->result;

        $recordSet = [];
        foreach($resultSet as $row)
        {
            $recordSet[] = (new Record())->configure($row);
        }

        return $recordSet;
    }
}
