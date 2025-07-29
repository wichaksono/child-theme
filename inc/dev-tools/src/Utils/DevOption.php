<?php
namespace NeonWebId\DevTools\Utils;

final class DevOption
{
    private string $name;

    private static ?DevOption $instance = null;

    private static array $optionValues = [];

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->loadOptionValues();
    }

    private function loadOptionValues(): void
    {
        if (self::$optionValues === []) {
            self::$optionValues = get_option($this->name, []);
        }
    }

    public static function getInstance(string $name): DevOption
    {
        if (self::$instance === null) {
            self::$instance = new self($name);
        }

        return self::$instance;
    }

    public function getName():string
    {
        return $this->name;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return self::$optionValues[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        self::$optionValues[$key] = $value;
        update_option($this->name, self::$optionValues);
    }

    public function delete(string $key): void
    {
        if (isset(self::$optionValues[$key])) {
            unset(self::$optionValues[$key]);
            update_option($this->name, self::$optionValues);
        }
    }
}