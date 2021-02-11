<?php

namespace Vendor\App;

use Vendor\App\SingletonTrait;

class SFTPService
{
    use SingletonTrait;
    private $conn; // SSH2 connection
    private $sftp; // SFTP subsystem from an already connected SSH2 server
    private $host = '';
    private $port = '';
    private $user = '';
    private $pass = '';

    private function __construct()
    {
        $this->conn = @ssh2_connect($this->host, $this->port);

        if (!$this->conn)
            throw new \Exception("Could not connect to $this->host on port $this->port.");

        $this->login();
    }

    private function login()
    {
        if (!@ssh2_auth_password($this->conn, $this->user, $this->pass))
            throw new \Exception("Could not authenticate with username and password.");

        $this->sftp = @ssh2_sftp($this->conn);

        if (!$this->sftp)
            throw new \Exception('Could not initialize SFTP subsystem.');
    }

    public function getConn()
    {
        return $this->conn;
    }

    public function getSFTP()
    {
        return $this->sftp;
    }
}
