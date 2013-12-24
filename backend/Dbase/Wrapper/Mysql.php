<?php

class Dbase_Wrapper_Mysql extends Dbase_Abstract
{
    // Mysql için varsayılan ayarlar
    private $defaults = array(
        'name' => 'mysql',
        'host' => 'localhost',
        'port' => '3306',
        'user' => 'root',
        'pass' => '',
        'open' => false
    );

    public function __construct(array $conf) 
    {
        if( ! extension_loaded('mysql') ){
            throw new Dbase_Exception('The MYSQL extension is not enabled');
        }

        // varsayılan ayarların üzerine yazarak nihai ayarları elde et
        $this->conf = array_merge($this->defaults, $conf);        
    }

    public function connect()
    {
        $conf = (object) $this->conf;
        $func = $conf->open ? 'mysql_pconnect' : 'mysql_connect';

        $this->link = @ $func($conf->host.':'.$this->port, $conf->user, $conf->pass);

        if( $this->link )
            $this->select_db($conf->name, $this->link);

        return $this->link;
    }

    public function select_db($name)
    {
        return mysql_select_db($name, $this->link);
    }

    public function query($query)
    {
        return @ mysql_query($query, $this->link);
    }


    public function fetch_assoc($result)
    {
        return mysql_fetch_assoc($result);
    }

    public function free_result($result)
    {
        return mysql_free_result($result);
    }


    public function insert_id()
    {
        return mysql_insert_id($this->link);
    }

    public function affected_rows()
    {
        return mysql_affected_rows($this->link);
    }


    public function escape_string($str)
    {
        return mysql_real_escape_string($str, $this->link);
    }

    public function error()
    {
        if( $this->link )
            return mysql_error($this->link);

        return mysql_error();
    }


    public function close()
    {
        if( $this->conf['open'] ) return; // kalıcı bağlantıysa...
        if( $this->link ) return mysql_close($this->link);
    }

} // end-of-class