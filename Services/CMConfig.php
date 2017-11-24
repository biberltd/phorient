<?php
namespace BiberLtd\Bundle\Phorient\Services;
class CMConfig
{
    private $host;
    private $port;
    private $token;
    private $dbUser;
    private $dbPass;
    private $protocol;
    /**
     * CMConfig constructor.
     * @param string $host
     * @param string $port
     * @param string $token
     * @param string $dbUser
     * @param string $dbPass
     * @param string $protocol
     */
    public function __construct(
        string $host = 'localhost', string $port = '2424', string $token = '',
        string $dbUser = 'root', string $dbPass = 'root',
        string $protocol = 'binary'
    )
    {
        $this->host     = $host;
        $this->port     = $port;
        $this->token    = $token;
        $this->dbUser   = $dbUser;
        $this->dbPass   = $dbPass;
        $this->protocol = $protocol;
    }
    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }
    /**
     * @param string $host
     * @return $this
     */
    public function setHost(string $host)
    {
        $this->host = $host;
        return $this;
    }
    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }
    /**
     * @param int $port
     * @return $this
     */
    public function setPort(int $port)
    {
        $this->port = $port;
        return $this;
    }
    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
    /**
     * @param string $token
     * @return $this
     */
    public function setToken(string $token)
    {
        $this->token = $token;
        return $this;
    }
    /**
     * @return string
     */
    public function getDbUser()
    {
        return $this->dbUser;
    }
    /**
     * @param string $dbUser
     * @return $this
     */
    public function setDbUser(string $dbUser)
    {
        $this->dbPass = $dbUser;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getDbPass()
    {
        return $this->dbPass;
    }
    /**
     * @param string $dbPass
     * @return $this
     */
    public function setDbPass(string $dbPass)
    {
        $this->dbPass = $dbPass;
        return $this;
    }
    /**
     * @return string
     */
    public function getProtocol(){
        return $this->protocol;
    }
    /**
     * @param string $protocol
     */
    public function setProtocol(string $protocol){
        $this->protocol = $protocol;
    }
}