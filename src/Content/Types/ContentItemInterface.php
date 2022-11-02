<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Types;


interface ContentItemInterface
{
    /**
     * @return string
     */
    public function raw(): string;

    /**
     * @param array $params
     * @return string
     */
    public function render(array $params = []): string;

    /**
     * @return string
     */
    public function tag(): string;

    /**
     * @return string
     */
    public function tagAttribute() : string;


    /**
     * @return string
     */
    public function variant($name = '') : string;


    /**
     * @return string
     */
    public function url() :string;

    public function items() : array;

    public function empty() : bool;


}
