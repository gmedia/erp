import { Camera, type MediaResult } from '@capacitor/camera';
import { Capacitor } from '@capacitor/core';

async function mediaResultToFile(photo: MediaResult): Promise<File> {
    const candidates = [
        photo.webPath,
        photo.uri ? Capacitor.convertFileSrc(photo.uri) : null,
    ].filter((path): path is string => Boolean(path));

    if (candidates.length === 0) {
        throw new Error('Camera did not return a photo path');
    }

    let lastError: unknown = null;

    for (const url of candidates) {
        try {
            const response = await fetch(url);
            const blob = await response.blob();
            const type = blob.type || 'image/jpeg';

            return new File([blob], `photo-${Date.now()}.jpg`, { type });
        } catch (error) {
            lastError = error;
        }
    }

    throw lastError instanceof Error
        ? lastError
        : new Error('Failed to read the captured photo');
}

export async function takePhoto(): Promise<File> {
    const photo = await Camera.takePhoto({
        quality: 80,
        editable: 'no',
        correctOrientation: true,
        saveToGallery: false,
    });

    return mediaResultToFile(photo);
}
