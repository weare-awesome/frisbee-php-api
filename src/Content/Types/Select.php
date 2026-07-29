<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content\Types;


/**
 * Class Select
 *
 * A dropdown (`text-input-select`) field. The authored value is stored in the
 * item body — the machine `value` of the chosen option, never its human
 * `label` (the label/options list lives in the content-type schema, not in the
 * page data, so it is not available here). `->raw()`/`->value()` return that
 * value; `->is()`/`->in()` test it so templates can branch on the selection.
 *
 *   @if($page->section('Layout')->content('columns')->is('three'))
 *
 * @package WeAreAwesome\FrisbeePHPAPI\Content\Types
 */
class Select extends ContentItem
{
    /**
     * The chosen option's value (alias of raw(), reads better at a call site).
     *
     * @return string
     */
    public function value(): string
    {
        return $this->raw();
    }

    /**
     * True when the selected value is exactly $value (strict string compare on
     * the option's machine value, not its label).
     *
     * @param string $value
     * @return bool
     */
    public function is(string $value): bool
    {
        return $this->raw() === $value;
    }

    /**
     * True when the selected value is any one of $values.
     *
     * @param array $values
     * @return bool
     */
    public function in(array $values): bool
    {
        return in_array($this->raw(), $values, true);
    }
}
