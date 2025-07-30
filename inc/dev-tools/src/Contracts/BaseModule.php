<?php

namespace NeonWebId\DevTools\Contracts;

use NeonWebId\DevTools\Utils\DevOption;
use NeonWebId\DevTools\Utils\View;

abstract class BaseModule
{
    /**
     * The view instance for rendering the module.
     *
     * @var View
     */
    protected View $view;

    /**
     * The DevOption instance for managing options.
     *
     * @var DevOption
     */
    protected DevOption $option;

    /**
     * The name of the field in the module.
     *
     * @var string
     */
    protected string $fieldName;

    /**
     * The value of the field in the module.
     *
     * @var mixed
     */
    protected mixed $fieldValue;

    /**
     * Get the unique identifier for the module.
     *
     * @return string The unique identifier for the module.
     */
    abstract public function id(): string;

    /**
     * Get the title of the module.
     *
     * @return string The title of the module.
     */
    abstract public function title(): string;

    /**
     * Get the name of the module.
     *
     * @return string The name of the module.
     */
    abstract public function name(): string;

    /**
     * Render the content of the module.
     *
     * This method should be implemented to output the HTML content of the module.
     *
     * @return void
     */
    abstract public function content(): void;

    /**
     * Apply the module's changes.
     *
     * This method should be implemented to apply any changes made by the module.
     *
     * @return void
     */
    abstract public function apply():void;

    /**
     * BaseModule constructor.
     *
     * @param View $view The view instance for rendering the module.
     * @param DevOption $option The DevOption instance for managing options.
     */
    public function __construct(View $view, DevOption $option)
    {
        $this->view   = $view;
        $this->option = $option;
    }

    /**
     * Check if the module should always be shown.
     *
     * This method can be overridden in subclasses to control visibility.
     *
     * @return bool True if the module should always be shown, false otherwise.
     */
    public function shouldAlwaysShow():bool
    {
        return false;
    }

    /**
     * Get the field name for a specific option in the module.
     *
     * @param string $name The name of the option.
     *
     * @return string The full field name including the module ID and option name.
     */
    protected function fieldName(string $name): string
    {
        return $this->fieldName = $this->option->getName() . '[' . $this->id() . '][' . $name . ']';
    }

    /**
     * Get the value of a specific field in the module.
     *
     * This method retrieves the value of a field from the DevOption instance,
     * using the module ID and field name as the key.
     *
     * @param string $name The name of the field.
     * @param mixed $default The default value to return if the field is not set.
     *
     * @return mixed The value of the field, or the default value if not set.
     */
    protected function fieldValue(string $name, mixed $default = null): mixed
    {
        return $this->fieldValue = $this->option->get($this->id() . '.' . $name, $default);
    }

}