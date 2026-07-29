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
     * Note: the variant key is 'med' (not 'md') — that is what Frisbee stores
     * in image_variants for every image, top-level or nested.
     */
    const MEDIUM = 'med';

    /**
     *
     */
    const LARGE = 'lg';

    /**
     * Candidate image_variants keys for each requested size. Medium tolerates
     * both spellings so a legacy 'md' request — or legacy 'md' data — still
     * resolves; the canonical key Frisbee writes is 'med'.
     */
    const SIZE_KEYS = [
        self::SMALL  => ['sm'],
        self::MEDIUM => ['med', 'md'],
        'md'         => ['med', 'md'],
        self::LARGE  => ['lg'],
    ];


    /**
     * @param string $size
     * @return string
     */
    public function variant($size = self::SMALL) : string
    {
        $variants = $this->meta('image_variants', []);

        foreach (self::SIZE_KEYS[$size] ?? [$size] as $key) {
            if (array_key_exists($key, $variants) && $variants[$key] !== '' && $variants[$key] !== null) {
                return $this->stringToUrl($variants[$key]);
            }
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
