export function formatDate(date: string | null | undefined): string {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-BE', { day: '2-digit', month: '2-digit', year: 'numeric' });
}