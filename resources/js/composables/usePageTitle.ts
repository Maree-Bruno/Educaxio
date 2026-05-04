import { ref } from 'vue';

const title = ref('');

export function usePageTitle() {
    return title;
}

export function setPageTitle(value: string) {
    title.value = value;
}