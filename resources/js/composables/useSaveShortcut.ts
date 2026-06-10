import { onMounted, onUnmounted } from 'vue';

export function useSaveShortcut(fn: () => void) {
    function onKeydown(e: KeyboardEvent) {
        if ((e.metaKey || e.ctrlKey) && e.key === 's') {
            e.preventDefault();
            fn();
        }
    }

    onMounted(() => window.addEventListener('keydown', onKeydown));
    onUnmounted(() => window.removeEventListener('keydown', onKeydown));
}
