<?php

class Dbase_Wrapper_PostgreSql extends Dbase_Abstract
{
    // PostgreSQL için varsayılan ayarlar
    private $defaults = array(
        'name' => 'postgres',       // veritabanı adı
        'host' => 'localhost',      // veritabanı adresi
        'port' => '5432',           // veritabanı portu
        'user' => 'postgres',       // kullanıcı adı
        'pass' => '',               // kullanıcı parolası
        'open' => false             // bağlantı açık kalsın mı (persist)?
    );

    // Insert Id ve Affected Rows için gerekiyor
    private $last_result = null;

    public function __construct(array $conf) 
    {
        if( ! extension_loaded('pgsql') ){
            throw new Dbase_Exception('The POSTGRESQL extension is not enabled');
        }

        // varsayılan ayarların üzerine yazarak nihai ayarları elde et
        $this->conf = array_merge($this->defaults, $conf);
    }

    public function connect()
    {
        $conf = (object) $this->conf;
        $cstr = 'host=%s port=%s dbname=%s user=%s password=%s';
        $cstr = sprintf($cstr, $conf->host, $conf->port, $conf->name, $conf->user, $conf->pass);
        $func = $conf->open ? 'pg_pconnect' : 'pg_connect';

        $this->link = $func($cstr);
        return $this->link;
    }

    public function select_db($name)
    {
        throw new Dbase_Exception('The select_db isn\'t supported');
    }

    public function query($query)
    {
        $this->last_result = @ pg_query($this->link, $query);
        return $this->last_result;
    }


    public function fetch_assoc($result)
    {
        return pg_fetch_array($result, NULL, PGSQL_ASSOC);
    }

    public function free_result($result)
    {
        return pg_free_result($result);
    }

 
    public function insert_id()
    {
        return pg_last_oid($this->last_result);
    }

    public function affected_rows()
    {
        return pg_affected_rows($this->last_result);
    }


    public function escape_string($str)
    {
        return pg_escape_string($str);
    }

    public function error()
    {
        if( $this->link )
            return pg_last_error($this->link);

        return 'Unable to connect to PostgreSQL server';
    }


    public function close()
    {
        if( $this->conf['open'] ) return; // kalıcı bağlantıysa...
        if( $this->link ) return pg_close($this->link);
    }

} // end-of-class