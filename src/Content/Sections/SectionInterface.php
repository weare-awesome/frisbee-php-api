<?php

namespace WeAreAwesome\FrisbeePHPAPI\Content\Sections;

use Illuminate\Support\Collection;
use WeAreAwesome\FrisbeePHPAPI\Content\Types\ContentItemInterface;

interface SectionInterface
{
    /**
     * @param string $title
     * @return ContentItemInterface
     */
    public function content(string $title): ContentItemInterface;

    /**
     * @return bool
     */
    public function isDisplayed(): bool;

    /**
     * @return string
     */
    public function tag();

    /**
     * @return string
     */
    public function tagAttribute(): string;

    /**
     * @return Collection
     */
    public function all() : Collection;


}
