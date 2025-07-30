<?php

namespace NeonWebId\DevTools\Contracts;

use NeonWebId\DevTools\Utils\DevOption;
use NeonWebId\DevTools\Utils\Field;
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

    protected Field $field;

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
    abstract public function apply(): void;

    /**
     * BaseModule constructor.
     *
     * @param View $view The view instance for rendering the module.
     * @param DevOption $option The DevOption instance for managing options.
     */
    final public function __construct(View $view, DevOption $option)
    {
        $this->view   = $view;
        $this->option = $option;
        $prefixName   = $this->option->getName();
        $this->field  = new Field($this->option, $prefixName, $this->id());

        $this->onContructor();
    }

    /**
     * Method to be called in the constructor of the module.
     *
     * This method can be overridden in subclasses to perform additional initialization.
     */
    protected function onContructor(): void
    {
        // Default implementation does nothing.
    }

    /**
     * Check if the module should always be shown.
     *
     * This method can be overridden in subclasses to control visibility.
     *
     * @return bool True if the module should always be shown, false otherwise.
     */
    public function shouldAlwaysShow(): bool
    {
        return false;
    }
}