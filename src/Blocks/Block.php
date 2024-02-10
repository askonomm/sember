<?php

namespace Asko\Sember\Blocks;

use Asko\Sember\Models\Post;

interface Block
{
    public static function editable(Post $post, array $block): string;

    public static function viewable(Post $post, array $block): string;
}