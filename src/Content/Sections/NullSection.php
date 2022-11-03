<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Sections;


use Illuminate\Support\Collection;
use WeAreAwesome\FrisbeePHPAPI\Content\Types\ContentItemInterface;
use WeAreAwesome\FrisbeePHPAPI\Content\Types\NullContent;

class NullSection extends BaseSection implements SectionInterface
{
    /**
     * @var string
     */
    protected string $name;


    /**
     * NullSection constructor.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $title
     * @return ContentItemInterface
     */
    public function content(string $title): ContentItemInterface
    {
        return  new NullContent(strtolower($title));
    }

    /**
     * @return bool
     */
    public function isDisplayed(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function tag()
    {
        return $this->tagEncode($this->name);
    }

    /**
     * @return string
     */
    public function tagAttribute(): string
    {
        return $this->generateTagAttribute($this->tag());
    }

    public function all(): Collection
    {
        return Collection::make([]);
    }
}
