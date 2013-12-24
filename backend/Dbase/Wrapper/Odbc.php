<?php
/**
 * ÖNEMLİ UYARI: bu adaptör şimdilik deneyseldir.
 * çıkabilecek sorunlar sizin sorumluluğunuzdadır.
 */
class Dbase_Wrapper_Odbc extends Dbase_Abstract
{
    // ODBC için varsayılan ayarlar
    private $defaults = array(
        'name' => 'DataSourceName',
        'user' => 'root',
        'pass' => '',
        'open' => false
    );

    public function __construct(array $conf) 
    {
        if (! extension_loaded('odbc')) {
            throw new Dbase_Exception('The ODBC extension is not enabled');
        }

        // varsayılan ayarların üzerine yazarak nihai ayarları elde et
        $this->conf = array_merge($this->defaults, $conf);
    }

    public function connect()
    {
        $conf = (object) $this->conf;
        $func = $conf->open ? 'odbc_pconnect' : 'odbc_connect';

        $this->link = @ $func($conf->name, $conf->user, $conf->pass, SQL_CUR_USE_ODBC);
        return $this->link;
    }

    public function select_db($name)
    {
        return false;
    }

    public function query($query)
    {
        return @ odbc_exec($this->link, $query);
    }


    public function fetch_assoc($result)
    {
        /**
         * Hata Önleme (@) sembolünü kaldırırsak eğer Insert/Update
         * sorgularında 'No tuples available at this result index'
         * uyarısı ekranda görüntüleniyor. Bu uyarı gizliyoruz.
         */
        return @ odbc_fetch_array($result);
    }

    public function free_result($result)
    {
        return odbc_free_result($result);
    }


    public function insert_id()
    {
        return NULL;
    }

    public function affected_rows()
    {
        return NULL;
    }


    public function escape_string($str)
    {
        if (! get_magic_quotes_gpc()) {
            $str = addslashes($str);
        }

        return $str;
    }

    public function error()
    {
        if( $this->link )
            return odbc_errormsg($this->link);

        return odbc_errormsg();
    }


    public function close()
    {
        if( $this->conf['open'] ) return; // kalıcı bağlantıysa...
        if( $this->link ) return odbc_close($this->link);
    }

} // end-of-class