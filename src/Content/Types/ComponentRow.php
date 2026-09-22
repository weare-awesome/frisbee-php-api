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
 * Row fields are addressed by their **name** — the sub-field's key in the
 * schema, the same as top-level fixed content. The name is made from the label
 * when the sub-field is created and never changes; the label is display text
 * and can change at any time.
 *
 * Rows used to be titled by the label, and code written then reads them by
 * label: `->content('CTA 1 Text')`. That still works. Each entry carries the
 * sub-field's current label, and a lookup that matches no name falls back to
 * it — until the label is renamed, which is why the name is what to use.
 * Lookups are case-insensitive and, like a section, a miss returns a safe
 * `NullContent`.
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
     * Each entry's label, by the same index as $content. Kept apart because
     * the content item types have nowhere to hold it.
     *
     * @var Collection<string>
     */
    private Collection $labels;

    /**
     * @param array  $fields The row's sub-field arrays.
     * @param string $cdn    The page CDN base, passed through to media types.
     */
    public function __construct(array $fields, string $cdn = '')
    {
        $fields = (new Collection($fields))
            ->filter(function ($field) {
                return is_array($field);
            })
            ->values();

        $this->content = $fields->map(function ($field) use ($cdn) {
            return ContentItemFactory::make($this->normalise($field), $cdn);
        });

        $this->labels = $fields->map(function ($field) {
            return strtolower((string) ($field['label'] ?? ''));
        });
    }

    /**
     * One sub-field by name, or by its current label for code written before
     * rows were titled by name (both case-insensitive). Missing → NullContent
     * (safe).
     *
     * @param string $name
     * @return ContentItemInterface
     */
    public function content(string $name): ContentItemInterface
    {
        $wanted = strtolower($name);

        $index = $this->content->search(function ($content) use ($wanted) {
            return strtolower((string) $content->title) === $wanted;
        });

        if ($index === false && $wanted !== '') {
            $index = $this->labels->search($wanted, true);
        }

        return $index !== false ? $this->content[$index] : new NullContent($wanted);
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
