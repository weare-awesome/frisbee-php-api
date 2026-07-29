<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Types;


use Illuminate\Support\Collection;

/**
 * Class Component
 *
 * A native repeater field (`type: "component"`): the author adds any number of
 * rows, each a small group of sub-fields defined by the content type's
 * `config.items`. The authored rows live in `meta.items` (an array of rows;
 * each row an array of sub-field arrays).
 *
 * `->rows()` returns a `ComponentRow` per authored row, and each row hydrates
 * its sub-fields to their real runtime types (`Image`, `VideoFile`, `Select`,
 * `Text`, …) — so nested media resolve URLs and variants just like top-level
 * content, no raw-array plumbing:
 *
 *   @foreach($page->section('Cards')->content('cards')->rows() as $row)
 *       <x-card
 *           title="{{ $row->content('Title')->raw() }}"
 *           image="{{ $row->content('Image')->url() }}" />
 *   @endforeach
 *
 * @package WeAreAwesome\FrisbeePHPAPI\Content\Types
 */
class Component extends ContentItem
{
    /**
     * The authored rows, each as a {@see ComponentRow}.
     *
     * @return Collection<ComponentRow>
     */
    public function rows(): Collection
    {
        $rows = $this->meta('items', []);

        if (!is_array($rows)) {
            return new Collection();
        }

        return (new Collection($rows))
            ->filter(function ($fields) {
                return is_array($fields);
            })
            ->map(function ($fields) {
                return new ComponentRow($fields, $this->cdn);
            })
            ->values();
    }

    /**
     * A component is empty when it has no authored rows.
     *
     * @return bool
     */
    public function empty(): bool
    {
        return $this->rows()->isEmpty();
    }

    /**
     * @return bool
     */
    public function notEmpty()
    {
        return !$this->empty();
    }
}
