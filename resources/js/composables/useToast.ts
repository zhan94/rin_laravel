import { ref } from 'vue';

export type ToastKind = 'success' | 'error' | 'info';

export interface ToastItem {
    id: number;
    kind: ToastKind;
    title: string;
    message: string;
}

const toasts = ref<ToastItem[]>([]);
const timers = new Map<number, ReturnType<typeof setTimeout>>();

let nextToastId = 1;

function removeToast(id: number): void {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);

    const timer = timers.get(id);
    if (timer) {
        clearTimeout(timer);
        timers.delete(id);
    }
}

function pushToast(
    kind: ToastKind,
    title: string,
    message: string,
    timeoutMs = 3500,
): void {
    const id = nextToastId++;

    toasts.value = [
        ...toasts.value,
        {
            id,
            kind,
            title: title.trim(),
            message: message.trim(),
        },
    ];

    if (timeoutMs > 0) {
        const timer = setTimeout(() => {
            removeToast(id);
        }, timeoutMs);

        timers.set(id, timer);
    }
}

export function useToast() {
    return {
        toasts,
        removeToast,
        success(title: string, message: string, timeoutMs?: number): void {
            pushToast('success', title, message, timeoutMs);
        },
        error(title: string, message: string, timeoutMs?: number): void {
            pushToast('error', title, message, timeoutMs ?? 4500);
        },
        info(title: string, message: string, timeoutMs?: number): void {
            pushToast('info', title, message, timeoutMs);
        },
    };
}
