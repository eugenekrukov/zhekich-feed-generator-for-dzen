<?php

namespace DzenUnifiedRss\Feed;

defined('ABSPATH') || exit;

/**
 * Подбирает обложку для <enclosure> — минимум 700px по ширине
 * (эмпирическое требование Дзена, см. plugin.md).
 */
class ImageResolver
{
    private const MIN_WIDTH = 700;

    /**
     * @return array{url:string,type:string}|null
     */
    public static function resolve(int $postId): ?array
    {
        return self::fromFeaturedImage($postId) ?? self::fromContent($postId);
    }

    private static function fromFeaturedImage(int $postId): ?array
    {
        $attachmentId = get_post_thumbnail_id($postId);

        return $attachmentId ? self::fromAttachment((int) $attachmentId) : null;
    }

    private static function fromAttachment(int $attachmentId): ?array
    {
        $src = wp_get_attachment_image_src($attachmentId, 'full');
        if (!$src || $src[1] < self::MIN_WIDTH) {
            return null;
        }

        return [
            'url'  => $src[0],
            'type' => get_post_mime_type($attachmentId) ?: 'image/jpeg',
        ];
    }

    private static function fromContent(int $postId): ?array
    {
        $post = get_post($postId);
        if (!$post || !preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches)) {
            return null;
        }

        foreach ($matches[1] as $url) {
            $attachmentId = attachment_url_to_postid($url);
            if ($attachmentId) {
                $resolved = self::fromAttachment((int) $attachmentId);
                if ($resolved) {
                    return $resolved;
                }
                continue;
            }

            $size = @getimagesize($url);
            if ($size && $size[0] >= self::MIN_WIDTH) {
                return ['url' => $url, 'type' => $size['mime'] ?? 'image/jpeg'];
            }
        }

        return null;
    }
}
