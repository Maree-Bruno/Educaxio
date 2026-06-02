import { ref } from 'vue';

export function useStudentSort(initial?: { col?: string; dir?: 'asc' | 'desc' }) {
    const sortCol = ref(initial?.col ?? 'lastname');
    const sortDir = ref<'asc' | 'desc'>(initial?.dir ?? 'asc');

    function sortBy(col: string) {
        if (sortCol.value === col) {
            sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortCol.value = col;
            sortDir.value = 'asc';
        }
    }

    return { sortCol, sortDir, sortBy };
}

export function studentRowNumber(
    index: number,
    currentPage: number,
    perPage: number,
    total: number,
    dir: 'asc' | 'desc',
): string {
    const pos = (currentPage - 1) * perPage + index + 1;
    return String(dir === 'desc' ? total - pos + 1 : pos).padStart(2, '0');
}
