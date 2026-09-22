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
 * and can change at any time. Each stored entry carries the name in its `name`
 * key.
 *
 * An entry's `title` is its label, as it always has been, so code that reads
 * rows by label — `->content('CTA 1 Text')` — keeps working: a lookup tries the
 * name, then the title, then the entry's current label. Rows saved before
 * entries carried a name are found by title. Lookups are case-insensitive and,
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
     * Each entry's name and label, by the same index as $content. Kept apart
     * because the content item types have nowhere to hold them.
     *
     * @var Collection<string>
     */
    private Collection $names;

    /** @var Collection<string> */
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

        $this->names = $fields->map(function ($field) {
            return strtolower((string) ($field['name'] ?? ''));
        });

        $this->labels = $fields->map(function ($field) {
            return strtolower((string) ($field['label'] ?? ''));
        });
    }

    /**
     * One sub-field by name — or by title or label, for code that reads rows
     * by label (all case-insensitive). Missing → NullContent (safe).
     *
     * @param string $name
     * @return ContentItemInterface
     */
    public function content(string $name): ContentItemInterface
    {
        $wanted = strtolower($name);

        if ($wanted === '') {
            return new NullContent($wanted);
        }

        $index = $this->names->search($wanted, true);

        if ($index === false) {
            $index = $this->content->search(function ($content) use ($wanted) {
                return strtolower((string) $content->title) === $wanted;
            });
        }

        if ($index === false) {
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
