import client from './client';

export const getConfig = (slug) => client.get(`/campaigns/${slug}/pottu/config`);

export const uploadImage = (slug, file) => {
    const formData = new FormData();
    formData.append('image', file);
    return client.post(`/campaigns/${slug}/pottu/images`, formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });
};

export const submitPlacement = (sessionUuid, placement, challengeToken = null) => {
    const payload = {
        image_id: placement.imageId,
        style_id: placement.styleId ?? null,
        x: placement.x,
        y: placement.y,
        size: placement.size,
        rotation: placement.rotation ?? 0,
        board_width: placement.boardWidth,
        board_height: placement.boardHeight,
    };

    if (challengeToken) {
        return client.post(`/challenges/${challengeToken}/sessions/${sessionUuid}/pottu-placement`, payload);
    }

    return client.post(`/sessions/${sessionUuid}/pottu-placement`, payload);
};
