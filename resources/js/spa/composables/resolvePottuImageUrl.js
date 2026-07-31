/**
 * Resolve a pottu image URL for display in the browser.
 * Handles relative /storage paths, blob previews, and absolute URLs.
 */
export function resolvePottuImageUrl(image) {
    if (!image) {
        return '';
    }

    const candidate = image.url || image.previewUrl || '';

    if (!candidate) {
        if (image.path) {
            const normalizedPath = image.path.startsWith('/storage/')
                ? image.path
                : `/storage/${image.path.replace(/^\/+/, '')}`;

            return normalizedPath;
        }

        return '';
    }

    if (
        candidate.startsWith('blob:')
        || candidate.startsWith('http://')
        || candidate.startsWith('https://')
        || candidate.startsWith('/')
    ) {
        return candidate;
    }

    return `/storage/${candidate.replace(/^\/+/, '')}`;
}
