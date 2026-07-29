<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Types;


use Illuminate\Support\Collection;

/**
 * Class ComponentRow
 *
 * One authored row of a `Component` repeater. Each of the row's sub-fields is
 * hydrated to its proper runtime type via {@see ContentItemFactory}, so an
 * `image` row-field is a real `Image` (`->url()`/`->variant()`), a `video-file`
 * a real `VideoFile`, a `text-input-select` a real `Select`, and so on — the
 * same classes you get for top-level content.
 *
 * Row fields are addressed by their **title**, which for a component sub-field
 * is the schema `label` (falling back to `name`) — the opposite of top-level
 * fixed content, whose title is the `name`. Lookups are case-insensitive and,
 * like a section, a miss returns a safe `NullContent`.
 *
 * @package WeAreAwesome\FrisbeePHPAPI\Content\Types
 */
class ComponentRow
{
    /**
     * @var Collection<ContentItemInterface>
     */
    private Collection $content;

    /**
     * @param array  $fields The row's sub-field arrays.
     * @param string $cdn    The page CDN base, passed through to media types.
     */
    public function __construct(array $fields, string $cdn = '')
    {
        $this->content = (new Collection($fields))
            ->filter(function ($field) {
                return is_array($field);
            })
            ->map(function ($field) use ($cdn) {
                return ContentItemFactory::make($this->normalise($field), $cdn);
            })
            ->values();
    }

    /**
     * One sub-field by title (case-insensitive). Missing → NullContent (safe).
     *
     * @param string $title
     * @return ContentItemInterface
     */
    public function content(string $title): ContentItemInterface
    {
        $index = $this->content->search(function ($content) use ($title) {
            return strtolower((string) $content->title) === strtolower($title);
        });

        return $index !== false ? $this->content[$index] : new NullContent(strtolower($title));
    }

    /**
     * Every sub-field in the row, sorted by order.
     *
     * @return Collection<ContentItemInterface>
     */
    public function all(): Collection
    {
        return $this->content->sortBy('order')->values();
    }

    /**
     * Row fields arrive without the full key set the type classes expect
     * (no `fixed`, sometimes no `order`/`meta`) — fill the gaps.
     *
     * @param array $field
     * @return array
     */
    private function normalise(array $field): array
    {
        return array_merge(
            ['title' => null, 'body' => '', 'type' => '', 'fixed' => false, 'order' => 0, 'meta' => []],
            $field
        );
    }
}
