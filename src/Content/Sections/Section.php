<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Sections;

use Illuminate\Support\Collection;
use WeAreAwesome\FrisbeePHPAPI\Content\Exceptions\FrisbeeMalformedContentException;
use WeAreAwesome\FrisbeePHPAPI\Content\Types\ContentItem;
use WeAreAwesome\FrisbeePHPAPI\Content\Types\ContentItemInterface;
use WeAreAwesome\FrisbeePHPAPI\Content\Types\Gallery;
use WeAreAwesome\FrisbeePHPAPI\Content\Types\Image;
use WeAreAwesome\FrisbeePHPAPI\Content\Types\NullContent;
use WeAreAwesome\FrisbeePHPAPI\Content\Types\Text;

class Section extends BaseSection implements SectionInterface
{
    /**
     * @var string
     */
    public string $name;
    /**
     * @var int
     */
    public int $order;
    /**
     * @var Collection
     */
    public Collection $content;
    /**
     * @var bool
     */
    public bool $displayed;
    /**
     * @var array
     */
    public array $contents;

    public string $cdnUrl;


    /**
     * Section constructor.
     */
    public function __construct(string $name, int $order, array $content, bool $displayed, $cdnUrl = '')
    {
        $this->name = $name;
        $this->order = $order;
        $this->displayed = $displayed;

        $this->cdnUrl = $cdnUrl;

        $this->content = $this->generate($content);
    }

    /**
     * @param array $contents
     * @return Collection
     * @throws FrisbeeMalformedContentException
     */
    private function generate(array $contents = [])
    {
        $c = new Collection();
        foreach ($contents as $content) {
            switch ($content['type']) {
                case('text-box'):
                {
                    $c = $c->add(Text::make($content));
                    break;
                }
                case('image'):
                {
                    $c = $c->add(Image::make($content, $this->cdnUrl));
                    break;
                }
                case('gallery'):
                    $c = $c->add(Gallery::make($content, $this->cdnUrl));
                    break;


                default:
                {
                    $c = $c->add(ContentItem::make($content));
                }
            }

        }
        return $c;
    }

    /**
     * @param string $title
     * @return ContentItemInterface
     */
    public function content(string $title) : ContentItemInterface {
        $index = $this->content->search(function ($content) use($title) {

            return strtolower($content->title) === strtolower($title);
        });

        return $index !== false ? $this->content[$index] : new NullContent(strtolower($title));
    }

    /**
     * @return bool
     */
    public function isDisplayed() : bool
    {
        return $this->displayed === true;
    }

    /**
     * @return string
     */
    public function tag() : string
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

    /**
     * @param array $a
     * @return static
     * @throws FrisbeeMalformedContentException
     */
    public static function make(array $a = [], $cdUrl = '')
    {
        try {
            $section = new static($a['name'], $a['order'], $a['contents'], $a['displayed'], $cdUrl);
            return $section;
        } catch (\Exception $exception) {
            throw new FrisbeeMalformedContentException($exception->getMessage());
        }
    }

    /**
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->content;
    }
}
