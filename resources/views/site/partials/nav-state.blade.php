@php
    /**
     * Active-state resolution for the public navigation.
     *
     * Defined as a real function (guarded, so a second include in the same
     * request is a no-op) so the desktop navbar and the mobile offcanvas
     * share one rule instead of each re-implementing it.
     */
    if (! function_exists('lindu_nav_active')) {
        function lindu_nav_active(?array $item): bool
        {
            $path = ltrim(parse_url((string) ($item['url'] ?? '/'), PHP_URL_PATH) ?: '/', '/');
            $current = trim(parse_url(request()->url(), PHP_URL_PATH) ?: '/', '/');

            if ($path === $current) {
                return true;
            }

            // A parent counts as current when any descendant does.
            foreach ($item['children'] ?? [] as $child) {
                if (lindu_nav_active($child)) {
                    return true;
                }
            }

            return $path !== '' && str_starts_with($current, $path);
        }
    }
@endphp
