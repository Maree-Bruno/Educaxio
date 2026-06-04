import { computed, onMounted, onUnmounted, ref } from 'vue';

export interface TimedSlot {
    start_time: string | null;
    end_time: string | null;
}

export function toMins(t: string): number {
    const [h, m] = t.split(':').map(Number);

    return h * 60 + m;
}

export function useCurrentSlot(slots: TimedSlot[]) {
    const now = ref<Date | null>(null);
    let interval: ReturnType<typeof setInterval> | null = null;

    onMounted(() => {
        now.value = new Date();
        interval = setInterval(() => {
            now.value = new Date();
        }, 60_000);
    });

    onUnmounted(() => {
        if (interval) {
            clearInterval(interval);
        }
    });

    const activeSlotIndex = computed((): number | null => {
        if (!now.value) {
            return null;
        }

        const mins = now.value.getHours() * 60 + now.value.getMinutes();

        for (let i = 0; i < slots.length; i++) {
            const s = slots[i];

            if (!s.start_time || !s.end_time) {
                continue;
            }

            if (mins >= toMins(s.start_time) && mins < toMins(s.end_time)) {
                return i;
            }
        }

        return null;
    });

    return { now, activeSlotIndex };
}
