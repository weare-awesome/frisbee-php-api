<?php

namespace WeAreAwesome\FrisbeePHPAPI\Content\Types;

use WeAreAwesome\FrisbeePHPAPI\Content\Exceptions\FrisbeeMalformedContentException;

class ContentItem implements ContentItemInterface
{
    /**
     * @var string
     */
    public ?string $title;
    /**
     * @var string
     */
    public string $body;
    /**
     * @var string
     */
    public string $type;
    /**
     * @var bool
     */
    public bool $fixed;
    /**
     * @var int
     */
    public int $order;
    /**
     * @var array
     */
    public array $meta;
    protected string $cdn;


    /**
     * ContentItem constructor.
     */
    public function __construct(?string $title, string $body, string $type, bool $fixed, int $order, array $meta = [], $cdn = '')
    {
        $this->title = $title;
        $this->body = $body;
        $this->type = $type;
        $this->fixed = $fixed;
        $this->order = $order;
        $this->meta = $meta;
        $this->cdn = $cdn;
    }

    /**
     * @param string $name
     * @return string
     */
    public function __get(string $name)
    {
        return $this->{$name} ? $this->{$name} : '';
    }

    /**
     * @param array $a
     * @return static
     * @throws FrisbeeMalformedContentException
     */
    public static function make(array $a = [], $cdn = '')
    {

        try {
            return new static($a['title'], self::handleBodyInParams($a), $a['type'], $a['fixed'], $a['order'], $a['meta'], $cdn);
        } catch (\Exception $exception) {
            throw new FrisbeeMalformedContentException($exception->getMessage());
        }
    }

    /**
     * @param array $a
     * @return string
     */
    private static function handleBodyInParams(array $a = [])
    {
        if(array_key_exists('body', $a) === false) {
            return '';
        }

        return !is_string($a['body']) ? '' : $a['body'];
    }


    /**
     * @param string $name
     * @param string $default
     * @return mixed|string
     */
    public function meta(string $name, $default = '')
    {
        if(array_key_exists($name, $this->meta)) {
            return $this->meta[$name];
        }

        return $default;
    }

    /**
     * @return string
     */
    public function tag(): string
    {
        return base64_encode('content-item' . $this->title);
    }

    /**
     * @return string
     */
    public function raw(): string
    {
        return $this->body ? $this->body : '';
    }


    /**
     * @return bool
     */
    public function notEmpty()
    {

        return !empty($this->body);
    }

    /**
     * @param array $params
     * @return string
     */
    public function generateAttributesString(array $params = [])
    {
        $string = '';

        foreach ($params as $key => $value) {
            $string .= ' ' . "$key='$value'";
        }
        $string .= ' ' . $this->tagAttribute();
        return $string;
    }

    /**
     * @param array $params
     * @return string
     */
    public function render(array $params = []): string
    {
        $body = $this->raw();
        $attributes = $this->generateAttributesString($params);
        return "<span $attributes >$body</span>";
    }

    /**
     * @return string
     */
    public function tagAttribute(): string
    {
        return 'data-seam-tag=' . $this->tag() ;
    }

    /**
     * @return string
     */
    public function variant($name = ''): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function url(): string
    {
       return '';
    }

    /**
     * @return array
     */
    public function items() : array
    {
        return [];
    }


    /**
     * @return bool
     */
    public function empty(): bool
    {
        return empty($this->raw());
    }
}
