<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Types;


/**
 * Class ContentItemFactory
 *
 * The single source of truth for turning a raw content-item array into its
 * concrete runtime type, keyed on the item's `type` string. Used both by
 * `Section` (top-level items) and by `Component` (a repeater's nested row
 * fields) so a field of a given type always hydrates to the same class,
 * wherever it appears.
 *
 * @package WeAreAwesome\FrisbeePHPAPI\Content\Types
 */
class ContentItemFactory
{
    /**
     * @param array  $content A content-item array (title, body, type, order, meta, …).
     * @param string $cdn     The page CDN base, needed by media types.
     * @return ContentItemInterface
     */
    public static function make(array $content, string $cdn = ''): ContentItemInterface
    {
        switch ($content['type'] ?? '') {
            case 'text-box':
                return Text::make($content);
            case 'image':
                return Image::make($content, $cdn);
            case 'gallery':
                return Gallery::make($content, $cdn);
            case 'video-file':
                return VideoFile::make($content, $cdn);
            case 'text-input-select':
                return Select::make($content);
            case 'component':
                return Component::make($content, $cdn);
            default:
                return ContentItem::make($content, $cdn);
        }
    }
}
