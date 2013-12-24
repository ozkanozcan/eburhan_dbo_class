<?php

class Dbase_Wrapper_Sqlite extends Dbase_Abstract
{
    // oluşan hataları tutan değişken
    private $errorMsg = null;

    // Sqlite için varsayılan ayarlar
    private $defaults = array(
        'name' => 'sqlite.test.db',
        'mode' => 0666, // integer
        'open' => true
    );

    public function __construct(array $conf) 
    {
        if( ! extension_loaded('sqlite') ){
            throw new Dbase_Exception('The SQLITE extension is not enabled');
        }
        
        // varsayılan ayarların üzerine yazarak nihai ayarları elde et
        $this->conf = array_merge($this->defaults, $conf);      
    }

    public function connect_db()
    {
        $conf = (object) $this->conf;
        $func = $conf->open ? 'sqlite_popen' : 'sqlite_open';

        $this->link = @ $func($conf->name, $conf->mode, $this->errorMsg);
        return $this->link;
    }

    public function select_db($name)
    {
        $this->conf['name'] = $name;
        $this->connect();
    }

    public function query($query)
    {
        return @ sqlite_exec($this->link, $query, $this->errorMsg);
    }


    public function fetch_assoc($result)
    {
        return sqlite_fetch_array($result, SQLITE_ASSOC);
    }

    public function free_result($result)
    {
        return null;
    }

 
    public function insert_id()
    {
        return sqlite_last_insert_rowid($this->link);
    }

    public function affected_rows()
    {
        return sqlite_changes($this->link);
    }


    public function escape_string($str)
    {
        return sqlite_escape_string($str);
    }

    public function error()
    {
        if( $this->errorMsg === null ){
            $lastError = sqlite_last_error($this->link);
            return sqlite_error_string($lastError);
        }

        return $this->errorMsg;
    }


    public function close()
    {
        if( $this->conf['open'] ) return; // kalıcı bağlantıysa...
        if( $this->link ) return sqlite_close($this->link);
    }

} // end-of-class