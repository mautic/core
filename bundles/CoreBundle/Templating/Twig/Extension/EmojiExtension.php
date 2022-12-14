<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Helper\EmojiHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EmojiExtension extends AbstractExtension
{
    protected EmojiHelper $emojiHelper;

    public function __construct(EmojiHelper $emojiHelper)
    {
        $this->emojiHelper = $emojiHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('toHtml', [$this, 'toHtml'], ['is_safe' => ['all']]),
            new TwigFunction('instanceof', [$this, 'isInstanceof'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * Convert to html.
     *
     * @param $text
     *
     * @return mixed
     */
    public function toHtml($text, string $from = 'emoji'): string
    {
        return $this->emojiHelper->toHtml($text, $from);
    }

    /**
     * @param $var
     * @param $instance
     */
    public function isInstanceof($var, $instance): bool
    {
        return $var instanceof $instance;
    }
}
