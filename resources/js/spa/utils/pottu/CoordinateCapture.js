/**
 * Map pointer coordinates to normalized position inside image bounds.
 */
export function pointerToNormalized(clientX, clientY, rect) {
    const x = (clientX - rect.left) / rect.width;
    const y = (clientY - rect.top) / rect.height;

    return {
        x: clamp(x),
        y: clamp(y),
    };
}

export function clamp(value, min = 0, max = 1) {
    return Math.max(min, Math.min(max, Number(value.toFixed(6))));
}

export function normalizedToPixels(x, y, width, height) {
    return {
        left: x * width,
        top: y * height,
    };
}

export function captureBoardSize(element) {
    const rect = element.getBoundingClientRect();

    return {
        boardWidth: Math.round(rect.width),
        boardHeight: Math.round(rect.height),
    };
}

export function captureImageSize(imageElement, boardElement) {
    const target = imageElement ?? boardElement;
    if (!target) {
        return { boardWidth: 400, boardHeight: 600 };
    }

    const rect = target.getBoundingClientRect();

    return {
        boardWidth: Math.round(rect.width),
        boardHeight: Math.round(rect.height),
    };
}
