<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Types;


class Gallery extends ContentItem implements ContentItemInterface
{

    public function items(): array
    {
        return array_map(function ($image) {

            return Image::make([
                'title' => $image['name'],
                'body' => $image['file']['name'],
                'type' => 'image',
                'fixed' => false,
                'order' => 0,
                'meta' => [
                    'image_variants' => $image['image_variants']
                ]
            ], $this->cdn
            );

        }, $this->meta('images', []));

    }

}
