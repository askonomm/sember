<?php

namespace Sember\System\Models;

use Sember\System\Collection;
use Sember\System\Database;

/**
 * @template T
 */
class Model
{
    protected string $storage_name;

    public array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getStorageName(): string
    {
        return $this->storage_name;
    }

    public function setStorageName(string $storage_name): void
    {
        $this->storage_name = $storage_name;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @param string $query
     * @param array $params
     * @return ?T
     */
    public static function findOne(string $query, array $params = []): ?static
    {
        return (new Database())->findOne(static::class, $query, $params);
    }

    /**
     * @param string $query
     * @param array $params
     * @return Collection<T>
     */
    public static function find(string $query, array $params = []): Collection
    {
        return (new Database())->find(static::class, $query, $params);
    }

    /**
     * Update the model in the database.
     *
     * @return void
     */
    public function update(): void
    {
        (new Database())->update($this);
    }

    /**
     * Delete the model from the database.
     *
     * @return void
     */
    public function delete(): void
    {
        (new Database())->delete($this);
    }
}