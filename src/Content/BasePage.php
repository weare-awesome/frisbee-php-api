<?php


namespace WeAreAwesome\FrisbeePHPAPI\Content;


use WeAreAwesome\FrisbeePHPAPI\Content\Menus\Menu;
use WeAreAwesome\FrisbeePHPAPI\Content\Menus\MenuInterface;
use WeAreAwesome\FrisbeePHPAPI\Content\Menus\NullMenu;
use Illuminate\Support\Collection;

abstract class BasePage extends ContentResource
{
    public function menu(string $name) : MenuInterface
    {

        if(!isset($this->menus) && !is_array($this->menus)) {
            return new NullMenu();
        }

        $name = strtolower($name);

        $index = Collection::make($this->menus)->search(function ($menu) use ($name) {
            return strtolower($menu['name']) === $name;
        });

        return $index !== false ? Menu::make($this->menus[$index]) : new NullMenu();
    }

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getMeta(string $name, string $default = '')
    {
        return isset($this->meta[$name]) ? $this->meta[$name] : $default;
    }




    /**
     * @param string $name
     * @return bool
     */
    public function hasSetting(string $name) : bool
    {
        return !empty($this->getSetting($name));
    }


    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getSetting(string $name, string $default = '')
    {
        return isset($this->distribution_settings[$name]) ? $this->distribution_settings[$name] : $default;
    }

    /**
     * @param array $names
     * @param string $separator
     * @return string
     */
    public function joinSettings(array $names = [], string $separator = ',') : string
    {
        $existing = [];
        foreach ($names as $name) {
            if($this->hasSetting($name)) {
                $existing[] = $this->getSetting($name);
            }
        }

        return implode($separator, $existing);
    }


}
