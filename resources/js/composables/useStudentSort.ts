import { ref } from 'vue';

export function useSort(initial?: { col?: string; dir?: 'asc' | 'desc' }) {
    const sortCol = ref(initial?.col ?? 'name');
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

export function useStudentSort(initial?: { col?: string; dir?: 'asc' | 'desc' }) {
    return useSort({ col: initial?.col ?? 'lastname', dir: initial?.dir ?? 'asc' });
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
