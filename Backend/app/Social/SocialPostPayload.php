<?php

namespace App\Social;

/**
 * Everything a publisher needs to post once — deliberately platform-agnostic.
 * Built from a social_posts row plus its media_assets row by SocialPublishCommand.
 */
class SocialPostPayload
{
    public function __construct(
        public readonly string $caption,
        public readonly ?string $hashtags = null,
        public readonly ?string $mediaUrl = null,      // publicly reachable — every platform's upload API needs a fetchable URL or bytes
        public readonly ?string $mediaPath = null,      // local absolute path, when the platform needs raw bytes (resumable upload)
        public readonly ?string $mediaMime = null,
        public readonly bool $isVideo = false,
        public readonly ?string $linkUrl = null,        // e.g. the blog article this post promotes
    ) {
    }

    /** Caption with hashtags appended, the shape most platforms expect. */
    public function fullText(int $limit = 0): string
    {
        $text = trim($this->caption.($this->hashtags ? "\n\n{$this->hashtags}" : ''));

        return $limit > 0 ? mb_substr($text, 0, $limit) : $text;
    }
}
