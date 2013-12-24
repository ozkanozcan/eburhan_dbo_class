<?php

abstract class Cache_Adapter
{
    public function __construct(Array $opt){}

    // kullanıcıdan gelen ayarlar ile varsayılan ayarları birleştirir
    // NOT: property olarak eklenenler ALT sınıfın property'leri olur
    protected function _mergeOptions(Array $def, Array $kul)
    {
        $def = array_merge($def, $kul);

        // seçenekleri ALT sınıfa property olarak ekle (public olarak)
        foreach($def as $key => $val){
            $this->{$key} = $val;
        }

        return $def;
    }


    // soyut metotlar
    abstract public function set($key, $data, $time = null);
    abstract public function get($key);
    abstract public function inc($key, $amount = 1); // arttır
    abstract public function dec($key, $amount = 1); // azalt
    abstract public function flush();       // bütün datayı uçur
    abstract public function is_set($key);
    abstract public function remove($key);  // tek bir datayı sil
} //sınıf sonu
