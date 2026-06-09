import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { update as updateSlotTimes } from '@/routes/admin/slot-times';
import { useToasterStore } from '@/stores/toaster';

type SlotDef = { id: number; start_time: string | null; end_time: string | null };
type AdminSchool = { id: number; slug: string };
type SlotTimeRow = { slot_id: number; start_time: string; end_time: string };

export function useSlotTimeForms(
    adminSchools: AdminSchool[],
    scheduleSlots: SlotDef[],
    adminSchoolSlotTimes: Record<string, Record<string, { start_time: string; end_time: string }>>,
) {
    const toaster = useToasterStore();

    const slotTimeForms = ref<Record<number, SlotTimeRow[]>>(
        Object.fromEntries(
            adminSchools.map((school) => {
                const times = adminSchoolSlotTimes[school.id] ?? {};
                return [
                    school.id,
                    scheduleSlots.map((s) => ({
                        slot_id: s.id,
                        start_time: times[s.id]?.start_time ?? s.start_time ?? '',
                        end_time:   times[s.id]?.end_time   ?? s.end_time   ?? '',
                    })),
                ];
            }),
        ),
    );

    const slotTimeErrors = computed(() =>
        Object.fromEntries(
            adminSchools.map((school) => {
                const rows = slotTimeForms.value[school.id];
                const errors = rows.map((row, i) => {
                    if (i === 0 || !rows[i - 1].end_time || !row.start_time) {
                        return null;
                    }

                    return row.start_time < rows[i - 1].end_time
                        ? `Après ${rows[i - 1].end_time}`
                        : null;
                });

                return [school.id, errors];
            }),
        )
    );

    function saveSlotTimes(schoolSlug: string, schoolId: number) {
        if (slotTimeErrors.value[schoolId].some((e) => e !== null)) {
            toaster.error('Corrigez les horaires avant d\'enregistrer');
            return;
        }

        router.put(
            updateSlotTimes.url({ school: schoolSlug }),
            { slots: slotTimeForms.value[schoolId] },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => toaster.success('Horaires enregistrés'),
            },
        );
    }

    return { slotTimeForms, slotTimeErrors, saveSlotTimes };
}