<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Types;


/**
 * Class Image
 * @package App\Lib\Content\Page\Types
 */
class Image extends ContentItem
{

    /**
     *
     */
    const SMALL = 'sm';

    /**
     *
     */
    const MEDIUM = 'md';

    /**
     *
     */
    const LARGE = 'lg';



    /**
     * @param string $size
     * @return string
     */
    public function variant($size = self::SMALL) : string
    {
        $variants = $this->meta('image_variants', []);

        if(array_key_exists($size, $variants)) {
            return $this->stringToUrl($variants[$size]);
        }

        return $this->stringToUrl($this->body);
    }

    /**
     * @param $string
     * @return string
     */
    private function stringToUrl($string) : string
    {
        if(str_contains($string, 'http')) {
            return $string;
        }

        return $this->cdn . '/images/' . $string;
    }


    /**
     * @return string
     */
    public function url() : string
    {
        return $this->stringToUrl($this->body);
    }
}
