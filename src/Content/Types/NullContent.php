<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Types;


class NullContent extends ContentItem implements ContentItemInterface
{
    public ?string $title;

    /**
     * NullContent constructor.
     */
    public function __construct(?string $title = '')
    {
        parent::__construct($title , '' , '', true, 0, []);
        $this->title = $title;
    }

    public function __get(string $name)
    {
        return $this->{$name} ? $this->{$name} : '';
    }


    /**
     * @return string
     */
    public function raw(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function render(array $params = []): string
    {
        return  $this->raw();
    }

    public function tag(): string
    {
        return base64_encode('content-item' . $this->title);
    }

    public function tagAttribute(): string
    {
        return 'data-frisbee-tag=' . $this->tag() ;
    }
}
