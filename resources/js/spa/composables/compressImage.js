/**
 * Compress an image file in the browser before upload.
 */
export async function compressImageFile(
    file,
    { maxWidth = 900, maxHeight = 1200, quality = 0.82 } = {}
) {
    if (!file?.type?.startsWith('image/')) {
        throw new Error('Please choose a valid image file.');
    }

    const bitmap = await createImageBitmap(file);
    const scale = Math.min(maxWidth / bitmap.width, maxHeight / bitmap.height, 1);
    const width = Math.max(1, Math.round(bitmap.width * scale));
    const height = Math.max(1, Math.round(bitmap.height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;

    const context = canvas.getContext('2d');
    if (!context) {
        bitmap.close();
        throw new Error('Could not prepare image for compression.');
    }

    context.drawImage(bitmap, 0, 0, width, height);
    bitmap.close();

    const blob = await new Promise((resolve, reject) => {
        canvas.toBlob(
            (result) => (result ? resolve(result) : reject(new Error('Could not compress image.'))),
            'image/jpeg',
            quality
        );
    });

    const baseName = file.name.replace(/\.[^.]+$/, '') || 'custom-image';

    return new File([blob], `${baseName}.jpg`, {
        type: 'image/jpeg',
        lastModified: Date.now(),
    });
}
