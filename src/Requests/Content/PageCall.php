<?php

namespace WeAreAwesome\FrisbeePHPAPI\Content;



use WeAreAwesome\FrisbeePHPAPI\Content\Exceptions\FrisbeeMalformedContentException;
use WeAreAwesome\FrisbeePHPAPI\Content\Menus\Menu;
use WeAreAwesome\FrisbeePHPAPI\Content\Menus\MenuInterface;
use WeAreAwesome\FrisbeePHPAPI\Content\Menus\NullMenu;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use WeAreAwesome\FrisbeePHPAPI\Content\Sections\NullSection;
use WeAreAwesome\FrisbeePHPAPI\Content\Sections\Section;
use WeAreAwesome\FrisbeePHPAPI\Content\Sections\SectionInterface;

class Page extends BasePage
{

    /**
     * @var string[]
     */
    private $allowed = [
        'cached_at',
        'cdn_url',
        'slug',
        'title',
        'description',
        'published',
        'content_type',
        'content_version',
        'menus',
        'distribution',
        'distribution_settings',
        'meta',
        'tags'
    ];


    /**
     * @var Collection $sections
     */
    protected $sections;


    /**
     * @var Collection $additionalContent
     */
    protected $additionalContent;


    /**
     * @param string $name
     * @param $value
     */
    public function __set(string $name, $value): void
    {

        if (in_array($name, $this->allowed)) {
            $this->{$name} = $value;
        }
    }

    /**
     * @param string $name
     * @return null
     */
    public function __get(string $name)
    {
        return isset($this->{$name}) ? $this->{$name} : null;
    }


    /**
     * @param $format
     * @return string
     */
    public function publishedFormatted($format = 'd/m/Y')
    {
        return Carbon::make($this->published)->format($format);
    }

    /**
     * @return ?string
     */
    public function contentTypeName(): ?string
    {
        return array_key_exists('name', $this->content_type) ? strtolower($this->content_type['name']) : null;
    }



    /**
     * @param string $name
     * @return ContentResource|null
     */
    public function getAdditionalContent(string $name): ContentResource|null
    {
        $index = $this->additionalContent->search(function (ContentResource $resource) use ($name) {
            return $resource->getDataKey() === $name;
        });

        return $index !== false ? $this->additionalContent[$index] : null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function additionalIsContentList(string $name): bool
    {
        if (!$this->additionalContentExists($name)) {
            return false;
        }

        return $this->getAdditionalContent($name) instanceof ListResource;
    }


    /**
     * @param string $name
     * @return bool
     */
    public function additionalContentExists(string $name): bool
    {
        return $this->getAdditionalContent($name) !== null;
    }


    /**
     * @param string $name
     * @return MenuInterface
     */
    public function menu(string $name) : MenuInterface
    {

        if(!isset($this->menus) && !is_array($this->menus)) {
            return new NullMenu();
        }

        $name = strtolower($name);

        $index = Collection::make($this->menus)->search(function ($menu) use ($name) {
            return strtolower($menu['name']) === $name;
        });

        return $index !== false ? Menu::make($this->menus[$index]) : new NullMenu();
    }



    /**
     * @return bool
     */
    public function hasAdditionalContent(): bool
    {
        if ($this->additionalContent) {
            return $this->additionalContent->count() >= 1;
        }

        return false;
    }

    /**
     *
     */
    public function generate()
    {
        $this->generateSections();
    }

    /**
     * @param string $name
     * @return SectionInterface
     */
    public function section(string $name): SectionInterface
    {
        $index = $this->sections->search(function ($section) use ($name) {
            return $section->name === $name;
        });

        return $index !== false ? $this->sections[$index] : new NullSection($name);

    }


    /**
     * @return array
     */
    public function availableLanguages(): array
    {

        if(isset($this->distribution) && array_key_exists('distribution_language_maps', $this->distribution)) {
            return array_filter(array_map(function ($map) {
                if(array_key_exists('language', $map)) {
                    return $map['language'];
                }
                return false;
            }, $this->distribution['distribution_language_maps']));
        }

        return [];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function sectionDisplayable(string $name): bool
    {
        if ($section = $this->section($name)) {
            return $section->isDisplayed();
        }
        return true;
    }


    /**
     * @throws FrisbeeMalformedContentException
     */
    private function generateSections()
    {
        $this->sections = new Collection();
        foreach ($this->content_version['body']['sections'] as $section) {
            $this->sections = $this->sections->add(Section::make($section, $this->cdn_url));
        }
    }


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

        $page->additionalContent = new Collection();

        $page->generate();

        return $page;
    }

    /**
     * @param $key
     * @param $default
     * @return string
     */
    public function metaWithCDN($key, $default = '')
    {
        $value = $this->getMeta($key, $default);
        if(!str_contains($value, 'http')) {
            return $this->cdn_url . '/images/' . $value;
        }

        return $default;
    }

}
