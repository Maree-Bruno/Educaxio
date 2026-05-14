import { usePage } from '@inertiajs/vue3';

const profileImageVariants = {
    xs: '64x64',
    sm: '128x128',
    md: '256x256',
    lg: '512x512',
} as const;

export const useUserHelpers = () => {
    const page = usePage();

    const storageUrl = (): string => {
        const url = (page.props.storage as { users: string })?.users ?? '/images';
        return url.endsWith('/') ? url.slice(0, -1) : url;
    };

    const getUserImageUrl = (
        picture: string | null | undefined,
        size: keyof typeof profileImageVariants = 'md',
    ): string => {
        if (!picture) {
            return '';
        }

        return `${storageUrl()}/profile/variants/${profileImageVariants[size]}/${picture}`;
    };

    const getUserImageSrcset = (picture: string | null | undefined): string => {
        if (!picture) {
            return '';
        }

        const base = storageUrl();

        return Object.entries(profileImageVariants)
            .map(([, size]) => `${base}/profile/variants/${size}/${picture} ${size.split('x')[0]}w`)
            .join(', ');
    };

    return { getUserImageUrl, getUserImageSrcset };
};