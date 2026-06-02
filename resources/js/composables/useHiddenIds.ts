import { ref } from 'vue';

export function useHiddenIds() {
    const hiddenIds = ref(new Set<number>());

    function hide(id: number) {
        hiddenIds.value = new Set([...hiddenIds.value, id]);
    }

    function show(id: number) {
        hiddenIds.value.delete(id);
        hiddenIds.value = new Set(hiddenIds.value);
    }

    function isHidden(id: number): boolean {
        return hiddenIds.value.has(id);
    }

    return { hiddenIds, hide, show, isHidden };
}
