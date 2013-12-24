<?php

abstract class Dbase_Abstract
{
    protected $link = null;
    protected $conf = array();

    // ortak fonksiyonlar
    public function getLink(){
        return $this->link;
    }

    // sınıfın ayarlarını set eder
    public function setConf(array $conf){
        if( ! empty($conf) )
            $this->conf = $conf;
    }

    // şu an geçerli olan ayarları alır
    public function getConf($key = null){
        if( $key === null )
            return $this->conf;

        if( array_key_exists($key, $this->conf) )
            return $this->conf[$key];

        return null; 
    }

    // şu an geçerli olan ayarların üzerine,
    // dışarıdan gelen ayarların yazılmasını sağlar
    public function mixConf(array $conf){
        $this->conf = array_merge($this->conf, $conf);
        return $this->conf;
    }

    // soyut metotlar
    abstract public function __construct(array $conf);
    abstract public function connect();
    abstract public function select_db($name);
    abstract public function query($query);

    abstract public function fetch_assoc($result);
    abstract public function free_result($result);
    abstract public function insert_id();
    abstract public function affected_rows();
    abstract public function escape_string($str);

    abstract public function error();
    abstract public function close();

} // end-of-class