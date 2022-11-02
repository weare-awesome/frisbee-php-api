<?php


namespace WeAreAwesome\FrisbeePHPAPI\Requests\Content;

class PageRequest
{

    /**
     * @var array
     */
    protected array $additionCalls = [];

    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @param string $name
     * @return null
     */
    public function __get(string $name)
    {
        return $this->{$name} ? $this->{$name} : null;
    }


    /**
     * @param ContentCall $call
     * @return $this
     */
    public function addCall(ContentCall $call) : PageRequest
    {
        $this->additionCalls[] = $call;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasAdditionalCalls() : bool
    {
        return count($this->additionCalls) >= 1;
    }

    /**
     * @param string $path
     * @return PageRequest
     */
    public static function make(string $path): PageRequest
    {
        return new static($path);
    }
}