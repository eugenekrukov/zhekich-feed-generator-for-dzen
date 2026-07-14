<?php

namespace DzenUnifiedRss\Feed;

use DzenUnifiedRss\Support\Options;

defined('ABSPATH') || exit;

class QueryHelper
{
    /**
     * @param bool $applyAgeAndExclusions только для потока Новостей — у канала таких
     *                                    ограничений исторически нет (см. plugin.md)
     */
    public static function args(bool $applyAgeAndExclusions, int $postsPerPage = 50): array
    {
        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $postsPerPage,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ];

        if (!$applyAgeAndExclusions) {
            return $args;
        }

        $maxAgeDays = (int) Options::get('max_age_days');
        if ($maxAgeDays > 0) {
            $args['date_query'] = [
                ['after' => $maxAgeDays . ' days ago'],
            ];
        }

        $taxonomy = trim((string) Options::get('exclude_taxonomy'));
        $terms = array_filter(array_map('trim', explode(',', (string) Options::get('exclude_terms'))));

        if ($taxonomy !== '' && $terms) {
            $args['tax_query'] = [[
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => array_map('intval', $terms),
                'operator' => 'NOT IN',
            ]];
        }

        return $args;
    }
}
