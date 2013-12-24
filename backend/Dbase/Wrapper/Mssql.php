<?php

class Dbase_Wrapper_Mssql extends Dbase_Abstract
{
    // Microsoft SQL Server için varsayılan ayarlar
    private $defaults = array(
        'name' => 'mysql',
        'user' => 'root',
        'pass' => '',
        'host' => 'localhost',
        'open' => false
    );

    // yapılan sorgu bir INSERT veya REPLACE sorgusu mu?
    // 'insert_id' değerini bulmak için gerekli
    private $insertOrReplace = false;


    public function __construct(array $conf) 
    {
        if( ! extension_loaded('mssql') ){
            throw new Dbase_Exception('The MSSQL extension is not enabled');
        }

        // varsayılan ayarların üzerine yazarak nihai ayarları elde et
        $this->conf = array_merge($this->defaults, $conf);        
    }

    public function connect()
    {
        $conf = (object) $this->conf;
        $func = $conf->open ? 'mssql_pconnect' : 'mssql_connect';

        $this->link = @ $func($conf->host, $conf->user, $conf->pass);

        if( $this->link )
            $this->select_db($conf->name, $this->link);

        return $this->link;
    }

    public function select_db($name)
    {
        return mssql_select_db($name, $this->link);
    }

    public function query($query)
    {
        $this->insertOrReplace = (bool) preg_match('/^(INSERT|REPLACE)/i', $query);

        return @ mssql_query($query, $this->link);
    }


    public function fetch_assoc($result)
    {
        return mssql_fetch_assoc($result);
    }

    public function free_result($result)
    {
        return mssql_free_result($result);
    }


    public function insert_id()
    {
        if( ! $this->insertOrReplace ) return 0;

        // http://stackoverflow.com/questions/574851/how-to-get-insert-id-in-mssql-in-php \\
        $result = $this->query('SELECT SCOPE_IDENTITY() AS insert_id');
        list($id) = mssql_fetch_row($result);
        mssql_free_result($result);

        return $id;
    }

    public function affected_rows()
    {
        return mssql_affected_rows($this->link);
    }


    public function escape_string($str)
    {
        // http://www.php.net/manual/en/function.mssql-query.php#70078
        $str = stripslashes($str);
        $str = str_replace("'", "''", $str);
        $str = str_replace("\0", "[NULL]", $str);

        return $str;
    }

    public function error()
    {
        return mssql_get_last_message();
    }


    public function close()
    {
        if( $this->conf['open'] ) return; // kalıcı bağlantıysa...
        if( $this->link ) return mssql_close($this->link);
    }

} // end-of-class