import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Group } from '@/types';

export const useGroupsStore = defineStore('groups', () => {
    const groups = ref<Group[]>([]);
    const loaded = ref(false);

    function setGroups(data: Group[]) {
        groups.value = data;
        loaded.value = true;
    }

    function updateGroup(updated: Group) {
        const index = groups.value.findIndex((g) => g.id === updated.id);

        if (index !== -1) {
            groups.value[index] = updated;
        }
    }

    function removeGroup(id: number) {
        groups.value = groups.value.filter((g) => g.id !== id);
    }

    return { groups, loaded, setGroups, updateGroup, removeGroup };
});
