<?php

namespace App\Notifications\Messages;

class DiscordMessage
{
    /**
     * The text channel id.
     *
     * @var string
     */
    public string $channelId;

    /**
     * The text content of the message.
     *
     * @var string
     */
    public string $content;

    /**
     * The embedded objects attached to the message.
     *
     * @var array
     */
    public array $embeds;

    /**
     * @param string $channelId
     * @param string $content
     * @param array|null $embeds
     *
     * @return static
     */
    public static function create(string $channelId = '', string $content = '', ?array $embeds = []): static
    {
        return new static($channelId, $content, $embeds);
    }

    /**
     * @param string $channelId
     * @param string $content
     * @param array $embeds
     */
    public function __construct(string $channelId = '', string $content = '', array $embeds = [])
    {
        $this->channelId = $channelId;
        $this->content = $content;
        $this->embeds = $embeds;
    }

    /**
     * Set the text content of the message.
     *
     * @param string $content
     *
     * @return $this
     */
    public function content(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set the text channel of the message.
     *
     * @param string $channelId
     *
     * @return $this
     */
    public function channelId(string $channelId): static
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * Set the embedded objects.
     *
     * @param $embeds
     *
     * @return $this
     */
    public function embeds($embeds): static
    {
        $this->embeds = $embeds;

        return $this;
    }
}
