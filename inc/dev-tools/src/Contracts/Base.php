<?php

namespace NeonWebId\DevTools\Contracts;

use NeonWebId\DevTools\Utils\DevOption;
use NeonWebId\DevTools\Utils\View;

abstract class Base
{
    protected View $view;

    protected DevOption $option;

    protected string $fieldName;

    protected mixed $fieldValue;

    abstract public function id(): string;

    abstract public function title(): string;

    abstract public function name(): string;

    abstract public function content(): void;

    public function __construct(View $view, DevOption $option)
    {
        $this->view   = $view;
        $this->option = $option;
    }

    protected function fieldName(string $name): string
    {
        return $this->fieldName = $this->option->getName() . '[' . $this->id() . '][' . $name . ']';
    }

    protected function fieldValue(string $name, mixed $default = null): mixed
    {
        return $this->fieldValue = $this->option->get($this->id() . '.' . $name, $default);
    }
}