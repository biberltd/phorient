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
    private $defaultOpts = [];
    private $language = 'sql';
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
        $this->setAuthenticationOptions();
    }
    /**
     * @return $this
     */
    private function setAuthenticationOptions(int $code = null){
        $authenticated = true;
        if($code == 401){
            $authenticated = false;
            $this->token = null;
        }
        if(isset($_COOKIE['OSESSIONID']) && $authenticated){
            $this->token = $_COOKIE['OSESSIONID'];
        }
        if(is_null($this->token)) {
            $opts = [
                'auth'      =>      [$this->username, $this->password]
            ];
        }
        else{
            $opts = [
                'headers'   =>      [
                    'OSESSIONID' => $this->token
                ]
            ];
        }
        $opts['headers']['Content-Type'] = 'application/json';
        $opts['headers']['Accept-Encoding'] = 'gzip,deflate';
        $this->defaultOpts = $opts;
        return $this;
    }
    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function connect(){
        if(is_null($this->database)) return;
        $endPoint = '/connect/'.$this->database;
        start:
        $options = $this->defaultOpts;
        try{
            $response = $this->client->request(
                'GET',
                $this->connectionStr.$endPoint,
                $options
            );
        }
        catch(\Exception $e){
            if($e->getCode() == 401){
                $this->token = null;
                $this->setAuthenticationOptions($e->getCode());
                goto start;
            }
            return null;
        }
    }
    /**
     * @param null $database
     * @return $this|mixed|\Psr\Http\Message\ResponseInterface
     */
    public function dbOpen($database = null){
        if(!is_null($database)){
            $this->database = $database;
            return $this->connect();
        }
        return $this;
    }
    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function disconnect(){
        $endPoint = '/disconnect';
        start:
        $options = $this->defaultOpts;
        try{
            $response = $this->client->request(
                'GET',
                $this->connectionStr.$endPoint,
                $options
            );
        }
        catch(\Exception $e){
            if($e->getCode() == 401){
                $this->token = null;
                $this->setAuthenticationOptions($e->getCode());
                goto start;
            }
            return null;
        }
    }
    /**
     * @param string $query
     * @param int $limit
     * @param string $fetchPlan
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function query(string $query, array $params = [], int $limit = -1, string $fetchPlan = "*:0")
    {
        foreach ($params as $key => $value){
            if(substr($key, 0, 1) != ':' || !is_string($value)){
                continue;
            }
            $query = str_replace($key, $value, $query);
        }
        $query = urlencode($query);
        $endPoint = '/query/'.$this->database.'/'.$this->language.'/'.$query.'/'.$limit.'/'.$fetchPlan;
        start:
        $options = $this->defaultOpts;
        try{
            $response = $this->client->request(
                'GET',
                $this->connectionStr.$endPoint,
                $options
            );
        }
        catch(\Exception $e){
            if($e->getCode() == 401){
                $this->token = null;
                $this->setAuthenticationOptions($e->getCode());
                goto start;
            }
            return null;
        }
        $dataResult = json_decode($response->getBody()->getContents(),true);
        return array_key_exists('result',$dataResult) ? $dataResult['result'] : [];
    }
    /**
     * @param string $query
     * @param array $params
     * @param int $limit
     * @param string $fetchPlan
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function queryAsync(string $query, array $params = [], int $limit = -1, string $fetchPlan = "*:0")
    {
        return $this->query($query, $params, $limit, $fetchPlan);
    }
    /**
     * @param string $query
     * @param array $params
     * @param int $limit
     * @param string $fetchplan
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function command(string $query, array $params = [], int $limit = 20, string $fetchplan = '*:0')
    {
        $endPoint = '/command/'.$this->database.'/'.$this->language.'/'.$limit.'/'.$fetchplan;
        start:
        $options = $this->defaultOpts;
        $body = new \stdClass();
        $body->command = $query;
        $body->params = count($params) == 0 ? null : (object) $params;
        $options['body'] = json_encode($body);
        try{
            $response = $this->client->request(
                'POST',
                $this->connectionStr.$endPoint,
                $options
            );
        }
        catch(\Exception $e){
            if($e->getCode() == 401){
                $this->token = null;
                $this->setAuthenticationOptions($e->getCode());
                goto start;
            }
            return null;
        }
        $dataResult = json_decode($response->getBody()->getContents(),true);
        return array_key_exists('result',$dataResult) ? $dataResult['result'] : [];
    }
}