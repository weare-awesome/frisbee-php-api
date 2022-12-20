<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content;


class DistributionPage extends Page
{


    /**
     * @param array $params
     * @return Page
     */
    public static function make(array $params = []): Page
    {
        $page = new static();

        foreach ($params as $key => $value) {
            $page->{$key} = $value;
        }

        return $page;
    }

}
