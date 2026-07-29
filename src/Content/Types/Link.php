<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Types;


/**
 * Class Link
 *
 * A `link` field: a URL plus optional display text. The authored URL is stored
 * in the item body (an absolute `https://…` or a root-relative `/path`); the
 * display text lives in `meta.display_text`.
 *
 *   <a href="{{ $link->url() }}">{{ $link->displayText() }}</a>
 *
 * @package WeAreAwesome\FrisbeePHPAPI\Content\Types
 */
class Link extends ContentItem
{
    /**
     * The link target (href) — the authored URL, returned as-is (absolute URLs
     * and root-relative paths both pass through unchanged).
     *
     * @return string
     */
    public function url(): string
    {
        return $this->raw();
    }

    /**
     * The display text for the link. Falls back to the URL when no display text
     * was set, so a bare link still renders with something visible.
     *
     * @return string
     */
    public function displayText(): string
    {
        $text = $this->meta('display_text', '');

        return is_string($text) && $text !== '' ? $text : $this->url();
    }

    /**
     * Whether the author set explicit display text (vs. falling back to the URL).
     *
     * @return bool
     */
    public function hasDisplayText(): bool
    {
        $text = $this->meta('display_text', '');

        return is_string($text) && $text !== '';
    }
}
