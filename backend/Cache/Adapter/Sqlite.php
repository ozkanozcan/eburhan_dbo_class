<?php

class Cache_Adapter_Sqlite extends Cache_Adapter
{
    // aktif SQLite bağlantısını tutar
    private $sqlite = null;

    function __construct(Array $opt)
    {
        if( ! extension_loaded('sqlite') )
            throw new Cache_Exception('SQLITE plugin not installed');

        // varsayılan seçenekler
        $def = array(
            'filename' => 'caches.db', 
            'filemode' => 0666, 
            'persist'  => false
        );

        // seçenekleri birleştir
        $this->_mergeOptions($def, $opt);

        // veritabanı bağlantısı yap
        $this->_connectToSqlite();
    }

    // Sadece bu sınıfa özel Genel metotlar ------------------------- \\
    private function _connectToSqlite()
    {
        // veritabanına bağlan
        $func = $this->persist ? sqlite_popen : sqlite_open;
        $this->sqlite = @func($this->filename, $this->filemode, $errMsg);

        // veritabanı bağlantısını kontrol et
        if (! $this->sqlite) {
            throw new Cache_Exception($errMsg);
        }

        // tablo daha önceden 'records' tablosu yoksa oluştur
        if( ! $this->_tableExist() ) {
            $this->_tableCreate();
        }
    }

    private function _updateData($key, $amount)
    {
        $old = $this->get($key);
        $new = $old + $amount;

        $this->set($key, $new);

        return $new;
    }

    private function _tableCreate()
    {
        // aynı anda birden fazla sorgu gönderiyoruz
        $sql = ('
            CREATE TABLE [records](
                [key] VARCHAR(100) NOT NULL PRIMARY KEY UNIQUE,
                [data] TEXT,
                [end_time] INTEGER(10)
            );
            CREATE UNIQUE INDEX [key_indeksi] ON [records]([key])
        ');

        if (! @sqlite_exec($this->sqlite, $sql, $errMsg)) {
            throw new Cache_Exception($errMsg);
        }
    }

    private function _tableExist()
    {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='records' LIMIT 1";
        $sor = @sqlite_query($this->sqlite, $sql, $errMsg);

        if (! $sor) {
            throw new Cache_Exception($errMsg);
        }

        return (sqlite_num_rows($sor) === 1);
    }


    // abstract metotları implement et ------------------------------ \\
    public function set($key, $data, $time = null)
    {
        // veri serileştirme
        $data = serialize($data);

        // zamanı ayarlayalım
        if (is_int($time) && $time > 0) {
            $time += time();
        } else {
            // 0'a eşitlemezsen silerken sorun çıkar
            $time = 0;
        }

        if ($this->is_set($key)) {
            // key daha önceden varsa
            $sql = sprintf(
                "UPDATE records SET data = '%s', end_time = %u WHERE key = '%s'",
                sqlite_escape_string($data), $time, sqlite_escape_string($key)
            );

            if (! @sqlite_exec($this->sqlite, $sql, $errMsg)) {
                throw new Cache_Exception($errMsg);
            }
        } else {
            // key daha önceden yoksa
            $sql = sprintf(
                "INSERT INTO records (key, data, end_time) VALUES ('%s', '%s', %u)",
                sqlite_escape_string($key), sqlite_escape_string($data), $time
            );

            if (! @sqlite_exec($this->sqlite, $sql, $errMsg)) {
                throw new Cache_Exception($errMsg);
            }
        }
    }

    public function get($key)
    {
        $sql = sprintf(
                "SELECT data,end_time FROM records WHERE key = '%s' LIMIT 1",
                sqlite_escape_string($key)
        );
        $sor = @sqlite_query($this->sqlite, $sql, SQLITE_ASSOC, $errMsg);

        if (! $sor) {
            throw new Cache_Exception($errMsg);
        }

        if( ($row = sqlite_fetch_object($sor)) === false ){
            return NULL;
        }

        // cast etme de gör ebeninkini...
        $end = (int) $row->end_time;

        // zamanı geçtiyse datayı sil
        if( $end !== 0 && $end <= time() ){
            $this->remove($key);
        }

        return unserialize($row->data);
    }

    public function inc($key, $amount = 1)
    {
        return $this->_updateData($key, $amount);
    }

    public function dec($key, $amount = 1)
    {
        return $this->_updateData($key, ($amount * -1));
    }

    public function is_set($key)
    {
        $sql = sprintf("SELECT key FROM records WHERE key = '%s' LIMIT 1", sqlite_escape_string($key));
        $sor = @sqlite_query($this->sqlite, $sql, SQLITE_ASSOC, $errMsg);

        if (! $sor) {
            throw new Cache_Exception($errMsg);
        }

        return (sqlite_num_rows($sor) === 1);
    }

    public function remove($key)
    {
        $sql = sprintf("DELETE FROM records WHERE key = '%s'", sqlite_escape_string($key));
        $sor = @sqlite_exec($this->sqlite, $sql, $errMsg);

        if (! $sor) {
            throw new Cache_Exception($errMsg);
        }

        return true;                        
    }

    public function flush()
    {
        $sor = @sqlite_exec($this->sqlite, 'DELETE FROM records', $errMsg);

        if (! $sor) {
            throw new Cache_Exception($errMsg);
        }

        return true;
    }


    function __destruct()
    {
        // bağlantı kalıcı değilse kapat
        if (! $this->persist && $this->sqlite) {
            sqlite_close($this->sqlite);
            $this->sqlite = null;
        }
    }
} // sınıf sonu
