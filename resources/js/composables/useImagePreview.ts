import { onUnmounted, ref } from 'vue';

export const useImagePreview = () => {
    const previewUrl = ref<string | null>(null);

    const handleSingleImage = (event: Event): File | null => {
        const target = event.target as HTMLInputElement;

        if (!target.files || !target.files[0]) {
            return null;
        }

        const file = target.files[0];

        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
        }

        previewUrl.value = URL.createObjectURL(file);

        return file;
    };

    const removeSinglePreview = () => {
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
            previewUrl.value = null;
        }
    };

    onUnmounted(() => {
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
        }
    });

    return { previewUrl, handleSingleImage, removeSinglePreview };
};