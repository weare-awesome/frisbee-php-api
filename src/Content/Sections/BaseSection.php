<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Sections;


use Illuminate\Support\Collection;

class BaseSection
{


    /**
     * @param string $name
     * @return string
     */
    public function tagEncode(string $name) :string
    {
        return base64_encode('section' . $name);
    }

    public function generateTagAttribute(string $tag) : string
    {
        return 'data-frisbee-tag=' .$tag;
    }

    public function contents()
    {
        return $this->content ?? Collection::make([]);
    }

}
