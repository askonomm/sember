<?php

namespace Asko\Sember\Models;

class Migration extends Model
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->storage_name = 'migrations';
    }
}