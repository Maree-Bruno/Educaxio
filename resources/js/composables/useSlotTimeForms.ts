import { ref } from 'vue';
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

    function saveSlotTimes(schoolSlug: string, schoolId: number) {
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

    return { slotTimeForms, saveSlotTimes };
}