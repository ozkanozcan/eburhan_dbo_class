<?php

class Cache_Adapter_File extends Cache_Adapter
{
    public function __construct(Array $opt)
    {
        // varsayılan seçenekler
        $def = array(
            'maxTime' => 0, // 0 : sonsuz
            'keyFunc' => 'md5', 
            'dirPath' => './log/cache/', // yazılabilir olmalı !
            'fileExt' => '.cache'
        );

        // seçenekleri birleştir
        $this->_mergeOptions($def, $opt);
    }


    // sadece bu sınıfa özel metotlar ------------------------------- \\
    private function _filePath($key)
    {
        $tmp = null;
        $tmp = is_null($this->keyFunc) ? $key : call_user_func($this->keyFunc, $key);
        $tmp = $this->dirPath . $tmp . $this->fileExt;
        return $tmp;
    }

    private function _updateData($key, $amount)
    {
        $filePath = $this->filePath($key);
        $fileSize = 0;
        $result = 0;
        $data = null;

        // dosyayı açmayı dene
        if (! $fp = fopen($filePath, 'r+') )
            return false;

        // dosyayı kilitle
        flock($fp, LOCK_EX);
        clearstatcache();
        $fileSize = filesize($filePath);

        // dosya içerisindeki datayı güncelle (zamana dokunmadan)
        $data = fread($fp, $fileSize);
        $data = unserialize($data);
        $data['context'] += $amount;
        $result = $data['context'];
        $data = serialize($data);

        // güncellenen datayı yaz
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $data);

        // dosya kilidini aç ve dosyayı kapat
        flock($fp, LOCK_UN);
        fclose($fp);

        return $result;
    }


    // abstract metotları implemente et ----------------------------- \\
    public function set($key, $data, $time = null)
    {
        // cache dizini yoksa oluşturmayı dene
        if (!file_exists($this->dirPath)) {
            if (!mkdir($this->dirPath, 0644)) {
                throw new Cache_Exception("'{$this->dirPath}' dizini oluşturulamadı");
            }
        }

        // cache "dizini" yazılabilir değilse yazılabilir yap, yoksa hata göster
        if (!is_writable($this->dirPath)) {
            if (!chmod($this->dirPath, 0644)) {
                throw new Cache_Exception("'{$this->dirPath}' dizini yazılabilir değil");
            }
        }

        // cache zamanını ata
        if (is_null($time)) {
            $time = $this->maxTime;
        }

        if ($time > 0) {
            $time = time() + $time;
        }


        // cache dosyasına yazılacak olan FINAL veri
        $data = array(
            'endTime' => $time,     // cache zamanı ne zaman sona erecek?
            'context' => $data      // cache dosyasında saklanacak içerik
        );
        $data = serialize($data);

        // cache dosyasını oluştur ( fh: file handler )
        $fh = fopen($this->_filePath($key), 'w');
        if (!$fh) {
            throw new Cache_Exception('cache dosyası oluşturulamadı');
        }

        // cache dosyasına veriyi yaz
        if (flock($fh, LOCK_EX)) {
            if (fputs($fh, $data) === false) {
                throw new Cache_Exception('cache dosyasına veri yazılamadı');
            }
            if (flock($fh, LOCK_UN) === false) {
                throw new Cache_Exception('cache dosyasının kilidi serbest bırakılamadı');
            }
        } else {
            throw new Cache_Exception('cache dosyası kilitlenemediği için veri yazılamadı');
        }

        // cache dosyasını kapat
        if (fclose($fh) === false) {
            throw new Cache_Exception('cache dosyası, veri yazıldıktan sonra kapatılamadı');
        }

        unset($fh);
    }

    public function get($key)
    {
        $filePath = $this->_filePath($key);

        // cache dosyası yoksa boş döndür
        if (!file_exists($filePath)) {
            return null;
        }

        // cache dosyasını oku
        $data = null;
        $data = file_get_contents($filePath);
        $data = unserialize($data);

        // kalan zamanı buradan görebiliriz :)
        // echo $data['endTime'] - time();
        // echo '<br />';

        // cache zamanı geçtiyse dosyayı sil
        if ( $data['endTime'] > 0 && $data['endTime'] <= time()) {
            unlink($filePath);
            $data = null;
        }

        return $data['context'];
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
        // cache dosyası yoksa hata döndür
        return file_exists($this->_filePath($key));
    }

    public function remove($key)
    {
        $filePath = $this->_filePath($key);

        if (file_exists($filePath))
            return unlink($filePath);

        return false;
    }

    public function flush()
    {
        // {$this->fileExt} uzantısına sahip bütün dosyaları uçur :)
        foreach (glob($this->dirPath.'*'.$this->fileExt) as $file) {
           unlink($file);
        }
    }

} // sınıf sonu