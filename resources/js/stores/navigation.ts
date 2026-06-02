import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useNavigationStore = defineStore('navigation', () => {
    const isPinned = ref(localStorage.getItem('nav-pinned') === 'true');
    const isCollapsed = ref(!isPinned.value);

    function togglePin() {
        isPinned.value = !isPinned.value;
        isCollapsed.value = !isPinned.value;
        localStorage.setItem('nav-pinned', String(isPinned.value));
    }

    const isMobileOpen = ref(false);

    function toggleMobile() {
        isMobileOpen.value = !isMobileOpen.value;
    }

    function closeMobile() {
        isMobileOpen.value = false;
    }

    return { isPinned, isCollapsed, togglePin, isMobileOpen, toggleMobile, closeMobile };
});
