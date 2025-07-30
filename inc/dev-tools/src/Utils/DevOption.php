<?php
namespace NeonWebId\DevTools\Utils;

use function update_option;

final class DevOption
{
    /**
     * The name of the option.
     *
     * @var string
     */
    private string $name;

    /**
     * The singleton instance of DevOption.
     *
     * @var ?DevOption
     */
    private static ?DevOption $instance = null;

    /**
     * The cached option values.
     *
     * @var array<string, mixed>
     */
    private static array $optionValues = [];

    /**
     * Private constructor to prevent direct instantiation.
     *
     * @param string $name The name of the option.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->loadOptionValues();
    }

    /**
     * Load the option values from the database.
     *
     * This method initializes the static $optionValues property
     * with the values retrieved from the WordPress options table.
     */
    private function loadOptionValues(): void
    {
        if (self::$optionValues === []) {
            self::$optionValues = get_option($this->name, []);
        }
    }

    /**
     * Get the singleton instance of DevOption.
     *
     * @param string $name The name of the option.
     *
     * @return DevOption The singleton instance.
     */
    public static function getInstance(string $name): DevOption
    {
        if (self::$instance === null) {
            self::$instance = new self($name);
        }

        return self::$instance;
    }

    /**
     * Get the name of the option.
     *
     * @return string The name of the option.
     */
    public function getName():string
    {
        return $this->name;
    }

    /**
     * Get the value of an option by key.
     *
     * @param string $key The key of the option.
     * @param mixed  $default The default value to return if the key does not exist.
     *
     * @return mixed The value of the option or the default value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return self::$optionValues[$key] ?? $default;
    }

    /**
     * Set the value of an option by key.
     *
     * @param string $key The key of the option.
     * @param mixed  $value The value to set for the option.
     *
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        self::$optionValues[$key] = $value;
        update_option($this->name, self::$optionValues);
    }

    /**
     * Set multiple options at once.
     *
     * @param array $options An associative array of key-value pairs to set.
     *
     * @return void
     */
    public function setBatch(array $options):void
    {
        update_option($this->name, $options);
    }

    /**
     * Delete an option by key.
     *
     * @param string $key The key of the option to delete.
     *
     * @return void
     */
    public function delete(string $key): void
    {
        if (isset(self::$optionValues[$key])) {
            unset(self::$optionValues[$key]);
            update_option($this->name, self::$optionValues);
        }
    }

    /**
     * Get all option values.
     *
     * @return array<string, mixed> An associative array of all option values.
     */
    public function getAll(): array
    {
        return self::$optionValues;
    }

    /**
     * Clear all option values.
     *
     * This method resets the option values to an empty array
     * and updates the WordPress options table accordingly.
     *
     * @return void
     */
    public function clear(): void
    {
        self::$optionValues = [];
        update_option($this->name, []);
    }

}