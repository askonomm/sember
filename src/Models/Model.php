<?php

namespace Asko\Sember\Models;

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

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}