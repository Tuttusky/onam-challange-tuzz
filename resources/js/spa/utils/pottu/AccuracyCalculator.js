import { pointerToNormalized } from './CoordinateCapture';

const DEFAULT_BANDS = [
    { min: 0, max: 5, stars: 5, label: 'Perfect', points: 100 },
    { min: 6, max: 10, stars: 4, label: 'Excellent', points: 90 },
    { min: 11, max: 20, stars: 3, label: 'Good', points: 75 },
    { min: 21, max: 30, stars: 2, label: 'Average', points: 50 },
];

/**
 * Client-side preview only. Authoritative scoring is on the backend.
 */
export function estimatePixelDistance(creator, friend, refWidth = 400, refHeight = 600) {
    const dx = (creator.x - friend.x) * refWidth;
    const dy = (creator.y - friend.y) * refHeight;

    return Math.sqrt(dx * dx + dy * dy);
}

export function resolveBand(distance, bands = DEFAULT_BANDS, failThreshold = 30) {
    const px = Math.round(distance);

    if (px > failThreshold) {
        return { label: 'Miss', stars: 0, points: 0 };
    }

    for (const band of bands) {
        const min = band.min ?? 0;
        const max = band.max ?? null;

        if (px >= min && (max === null || px <= max)) {
            return band;
        }
    }

    return { label: 'Miss', stars: 0, points: 0 };
}

export function estimateAccuracy(creator, friend, options = {}) {
    if (!creator || !friend) {
        return null;
    }

    const refWidth = options.refWidth ?? 400;
    const refHeight = options.refHeight ?? 600;
    const failThreshold = options.failThreshold ?? 30;
    const bands = options.bands ?? DEFAULT_BANDS;

    const pixelDistance = estimatePixelDistance(creator, friend, refWidth, refHeight);
    const maxDistance = Math.max(refWidth, refHeight);
    const accuracyPercent = maxDistance > 0
        ? Math.max(0, Math.min(100, Math.round((1 - pixelDistance / maxDistance) * 100)))
        : 0;
    const won = pixelDistance <= failThreshold;
    const band = resolveBand(pixelDistance, bands, failThreshold);

    return {
        pixelDistance,
        accuracyPercent,
        won,
        label: won ? band.label : 'Miss',
        band,
        pointsToWin: Math.max(0, Math.round(pixelDistance - failThreshold)),
    };
}

export { pointerToNormalized };
