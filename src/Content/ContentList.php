<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content;


use Illuminate\Support\Collection;

class ContentList extends ContentResource implements ListResource
{
    /**
     * @var Collection
     */
    protected Collection $items;
    /**
     * @var array
     */
    protected array $pagination;


    /**
     * ContentList constructor.
     */
    public function __construct(Collection $items, array $pagination)
    {
        $this->items = $items;
        $this->pagination = $pagination;
    }

    /**
     * @return Collection
     */
    public function content(): Collection
    {
        return $this->items;
    }

    public function pagination()
    {
        return $this->pagination;
    }


    /**
     * @param array $items
     * @param array $pagination
     * @return static
     */
    public static function makeFromArray(array $items = [], array $pagination = [])
    {

        $a = new Collection();
        foreach ($items as $item) {
            $a = $a->add(Page::make(array_merge($item,['cdn_url' => $pagination['cdn_url']])));
        }

        return new static($a, $pagination);
    }


}
