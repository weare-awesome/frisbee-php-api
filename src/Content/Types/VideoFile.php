<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Types;


/**
 * Class VideoFile
 *
 * A self-hosted, multi-format HTML5 video. Unlike the embed-based `video`
 * type (YouTube/Vimeo id), a `video-file` carries one uploaded file per
 * format plus an optional poster image and fallback text:
 *
 *   body            preferred/canonical source filename
 *   meta.sources    { "webm": "clip.webm", "mp4": "clip.mp4", "ogg": "clip.ogg" }
 *   meta.preferred  format key browsers should try first (default "webm")
 *   meta.poster     poster image filename (resolved under /images)
 *   meta.fallback   text shown when no source can be played
 *
 * Video files resolve against the page CDN under `/videos/`; the poster under
 * `/images/` (mirroring Image). Absolute `http…` values pass through unchanged.
 *
 * @package WeAreAwesome\FrisbeePHPAPI\Content\Types
 */
class VideoFile extends ContentItem
{
    /**
     *
     */
    const WEBM = 'webm';

    /**
     *
     */
    const MP4 = 'mp4';

    /**
     *
     */
    const OGG = 'ogg';

    /**
     * Supported formats, in author-preference order, mapped to their MIME type.
     * The preferred format is floated to the front by orderedSources().
     */
    const MIME_TYPES = [
        self::WEBM => 'video/webm',
        self::MP4  => 'video/mp4',
        self::OGG  => 'video/ogg',
    ];

    /**
     * The format the browser should try first.
     *
     * @return string
     */
    public function preferredFormat(): string
    {
        $preferred = $this->meta('preferred', self::WEBM);

        return is_string($preferred) && $preferred !== '' ? $preferred : self::WEBM;
    }

    /**
     * The uploaded filename for a given format (empty string if not set).
     *
     * @param string $format
     * @return string
     */
    public function sourceFile(string $format): string
    {
        $sources = $this->meta('sources', []);

        return is_array($sources) && !empty($sources[$format]) ? $sources[$format] : '';
    }

    /**
     * Every available `<source>`, preferred format first, each resolved to a URL.
     * Shape: [ ['format' => 'webm', 'mime' => 'video/webm', 'file' => 'clip.webm', 'url' => '…'], … ]
     *
     * @return array
     */
    public function sources(): array
    {
        $preferred = $this->preferredFormat();

        $ordered = array_keys(self::MIME_TYPES);
        usort($ordered, function ($a, $b) use ($preferred) {
            if ($a === $preferred) {
                return -1;
            }
            if ($b === $preferred) {
                return 1;
            }
            return 0;
        });

        $sources = [];
        foreach ($ordered as $format) {
            $file = $this->sourceFile($format);
            if ($file === '') {
                continue;
            }
            $sources[] = [
                'format' => $format,
                'mime'   => self::MIME_TYPES[$format],
                'file'   => $file,
                'url'    => $this->videoUrl($file),
            ];
        }

        return $sources;
    }

    /**
     * URL of the canonical source: the authored `body` if present, otherwise
     * the first available source (preferred format first). Empty when none set.
     *
     * @return string
     */
    public function url(): string
    {
        if ($this->body !== '') {
            return $this->videoUrl($this->body);
        }

        $sources = $this->sources();

        return empty($sources) ? '' : $sources[0]['url'];
    }

    /**
     * URL for a specific format; falls back to url() when that format is absent.
     *
     * @param string $format Image::WEBM|MP4|OGG
     * @return string
     */
    public function variant($format = self::WEBM): string
    {
        $file = $this->sourceFile($format);

        return $file !== '' ? $this->videoUrl($file) : $this->url();
    }

    /**
     * Poster image URL (resolved under /images like an Image). Empty if none.
     *
     * @return string
     */
    public function poster(): string
    {
        $poster = $this->meta('poster', '');

        if (!is_string($poster) || $poster === '') {
            return '';
        }

        return $this->imageUrl($poster);
    }

    /**
     * Text shown when the browser cannot play any source.
     *
     * @return string
     */
    public function fallback(): string
    {
        $fallback = $this->meta('fallback', '');

        return is_string($fallback) ? $fallback : '';
    }

    /**
     * A video-file is empty only when it resolves to no playable file at all
     * (no canonical body and no formatted source).
     *
     * @return bool
     */
    public function empty(): bool
    {
        return $this->url() === '';
    }

    /**
     * @return bool
     */
    public function notEmpty()
    {
        return !$this->empty();
    }

    /**
     * @param string $file
     * @return string
     */
    private function videoUrl(string $file): string
    {
        if (str_contains($file, 'http')) {
            return $file;
        }

        return $this->cdn . '/videos/' . $file;
    }

    /**
     * @param string $file
     * @return string
     */
    private function imageUrl(string $file): string
    {
        if (str_contains($file, 'http')) {
            return $file;
        }

        return $this->cdn . '/images/' . $file;
    }
}
