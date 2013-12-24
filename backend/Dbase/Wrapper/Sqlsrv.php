<?php

class Dbase_Wrapper_Sqlsrv extends Dbase_Abstract
{
    // Microsoft SQL Server için varsayılan ayarlar
    private $defaults = array(
        'name' => 'master',
        'user' => 'sa',
        'pass' => '123456',
        'host' => 'PCNAME\\SQLEXPRESS',
        'info' => array(
            'ReturnDatesAsStrings' => false,
            'ConnectionPooling' => 1,
            'CharacterSet' => 'UTF-8',
            'LoginTimeout' => 30,
            'Encrypt' => 0,
        )
    );

    // yapılan sorgu bir INSERT veya REPLACE sorgusu mu?
    // 'insert_id' değerini bulmak için gerekli
    private $insertOrReplace = false;
    private $lastResult = null;


    public function __construct(array $conf) 
    {
        if( ! extension_loaded('sqlsrv') ){
            throw new Dbase_Exception('The Sqlsrv extension is not enabled');
        }

        // varsayılan ayarların üzerine yazarak nihai ayarları elde et
        $this->conf = array_merge($this->defaults, $conf);
    }

    public function connect()
    {
        $conf = (object) $this->conf;
        $connectionInfo = array(
            'Database' => $conf->name,
            'UID' => $conf->user,
            'PWD' => $conf->pass,
        );
        $connectionInfo = array_merge($connectionInfo, $conf->info);
        $this->link = sqlsrv_connect($conf->host, $connectionInfo);

        return $this->link;
    }

    public function select_db($name)
    {
        return null;
    }

    public function query($query)
    {
        $this->insertOrReplace = (bool) preg_match('/^(INSERT|REPLACE)/i', $query);
        $this->lastResult = @ sqlsrv_query($this->link, $query);

        // sorgu başarısız ise...
        if( false === $this->lastResult ){
            return false;
        }

        // insert veya replace sorgularıysa...
        if( true === $this->insertOrReplace ){
            return true;
        }

        // select veya show sorgularıysa...
        return $this->lastResult;
    }

    public function fetch_assoc($result)
    {
        return sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    }

    public function free_result($result)
    {
        return sqlsrv_free_stmt($result);
    }


    public function insert_id()
    {
        if( ! $this->insertOrReplace ) return 0;

        // http://stackoverflow.com/questions/574851/how-to-get-insert-id-in-mssql-in-php \\
        $result = $this->query('SELECT SCOPE_IDENTITY() AS insert_id');

        if( is_bool($result) ) return 0;

        list($id) = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC);
        sqlsrv_free_stmt($result);

        return $id;
    }

    public function affected_rows()
    {
        if( null === $this->lastResult ) return 0;
        return sqlsrv_rows_affected($this->lastResult);
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
        // oluşan hatalardan en sonuncusunu göster
        $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
        $errors = end($errors);

        return $errors['message'];
    }


    public function close()
    {
        if( $this->link ) return sqlsrv_close($this->link);
    }

} // end-of-class