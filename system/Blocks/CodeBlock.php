<?php

namespace Sember\System\Blocks;

use Sember\System\Models\Post;
use Sember\System\Response;
use Exception;

/**
 * The code block.
 *
 * @since 0.1.0
 */
class CodeBlock implements Block
{
    // The name of the block.
    public string $name = 'Code';

    // The icon of the block.
    public string $icon = 'fa-solid fa-code';

    // Injected JS
    public array $js = [
        '/system/js/blocks/code.js'
    ];

    // Injected CSS
    public array $css = [
        '/system/css/blocks/code.css'
    ];

    /**
     * Returns the data model for the heading block.
     *
     * @return array
     */
    public static function model(): array
    {
        return [];
    }

    /**
     * Returns the options for the heading block.
     *
     * @param Post $post
     * @param array $block
     * @return array
     */
    public static function options(Post $post, array $block): array
    {
        return [];
    }

    /**
     * Returns the editable heading block.
     *
     * @param Post $post
     * @param array $block
     * @return string
     */
    public static function editable(Post $post, array $block): string
    {
        return (new Response)->systemView('blocks/code_editable', [
            'post' => $post->toArray(),
            'block' => $block,
        ])->send();
    }

    /**
     * Returns the viewable heading block.
     *
     * @param Post $post
     * @param array $block
     * @return string
     * @throws Exception
     */
    public static function viewable(Post $post, array $block): string
    {
        $hl = new \Highlight\Highlighter();
        $hl->setAutodetectLanguages(['php', 'javascript', 'json', 'css', 'html', 'rust', 'clojure']);
        $hled = $hl->highlightAuto($block['value']);

        return (new Response)->systemView('blocks/code_viewable', [
            'post' => $post->toArray(),
            'block' => $block,
            'language' => $hled->language,
            'code' => $hled->value,
        ])->send();
    }
}