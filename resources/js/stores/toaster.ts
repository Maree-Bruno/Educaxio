import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ToastStatus = 'success' | 'warning' | 'error';

export interface Toast {
    id:      number;
    text:    string;
    status:  ToastStatus;
    action?: { label: string; onClick: () => void };
}

export const useToasterStore = defineStore('toaster', () => {
    const toasts = ref<Toast[]>([]);

    function add(text: string, status: ToastStatus, timeout = 3000) {
        const id = Math.random() * 1_000_000;

        toasts.value.push({ id, text, status });
        setTimeout(() => {
            toasts.value = toasts.value.filter((t) => t.id !== id);
        }, timeout);
    }

    function deletable(text: string, onConfirm: () => void, onUndo?: () => void, timeout = 3000) {
        const id = Math.random() * 1_000_000;

        const timer = setTimeout(() => {
            toasts.value = toasts.value.filter((t) => t.id !== id);
            onConfirm();
        }, timeout);

        const undo = () => {
            clearTimeout(timer);
            toasts.value = toasts.value.filter((t) => t.id !== id);
            onUndo?.();
        };

        toasts.value.push({ id, text, status: 'warning', action: { label: 'Annuler', onClick: undo } });
    }

    function actionable(text: string, actionLabel: string, onAction: () => void, timeout = 6000) {
        const id = Math.random() * 1_000_000;

        const timer = setTimeout(() => {
            toasts.value = toasts.value.filter((t) => t.id !== id);
        }, timeout);

        const action = () => {
            clearTimeout(timer);
            toasts.value = toasts.value.filter((t) => t.id !== id);
            onAction();
        };

        toasts.value.push({ id, text, status: 'success', action: { label: actionLabel, onClick: action } });
    }

    const success = (text: string, timeout?: number) => add(text, 'success', timeout);
    const warning = (text: string, timeout?: number) => add(text, 'warning', timeout);
    const error   = (text: string, timeout?: number) => add(text, 'error',   timeout);

    return { toasts, success, warning, error, deletable, actionable };
});