<?php

class Dbase_Wrapper_MySqli extends Dbase_Abstract
{
    // Mysql için varsayılan ayarlar
    private $defaults = array(
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => 'mysql',
        'port' => '3306'
    );

    public function __construct(array $conf) 
    {
        if( ! extension_loaded('mysqli') ){
            throw new Dbase_Exception('The MYSQL(i) extension is not enabled');
        }

        // varsayılan ayarların üzerine yazarak nihai ayarları elde et
        $this->conf = array_merge($this->defaults, $conf);        
    }

    public function connect()
    {
        $conf = (object) $this->conf;
        $this->link = @ mysqli_connect(
            $conf->host,
            $conf->user,
            $conf->pass,
            $conf->name,
            $conf->port
        );
        return $this->link;
    }

    public function select_db($name)
    {
        return mysqli_select_db($this->link, $name);
    }

    public function query($query)
    {
        return @ mysqli_query($this->link, $query);
    }


    public function fetch_assoc($result)
    {
        return mysqli_fetch_array($result, MYSQLI_ASSOC);
    }

    public function free_result($result)
    {
        return mysqli_free_result($result);
    }

 
    public function insert_id()
    {
        return mysqli_insert_id($this->link);
    }

    public function affected_rows()
    {
        return mysqli_affected_rows($this->link);
    }


    public function escape_string($str)
    {
        return mysqli_real_escape_string($this->link, $str);
    }

    public function error()
    {
        if (mysqli_connect_errno()) {
            return mysqli_connect_error($this->link);
        }

        return mysqli_error($this->link);
    }


    public function close()
    {
        if( $this->link )
            return mysqli_close($this->link);
    }

} // end-of-class