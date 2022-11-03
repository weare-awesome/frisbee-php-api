<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content;

abstract class  ContentResource
{

    public string $dataKey;

    public function setDataKey($key)
    {
        return $this->dataKey = $key;
    }

    public function getDataKey()
    {
        return $this->dataKey ? $this->dataKey : '';
    }
}
