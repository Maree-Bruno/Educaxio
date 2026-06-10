<script setup lang="ts">
import DashboardCard from '@/components/admin/DashboardCard.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import { index as academicYearsIndex } from '@/routes/admin/academic-years';
import { index as lessonsIndex } from '@/routes/admin/lessons';
import { index as studentsIndex } from '@/routes/admin/students';
import { index as teachersIndex } from '@/routes/admin/teachers';

defineProps<{ schoolSlug: string }>();
</script>

<template>
    <DashboardCard title="Accès rapide" description="Raccourcis vers les principales sections de gestion.">
        <div class="grid grid-cols-2 gap-3 bg-white p-6">
            <LinkButton href="/classlist"                                   variant="secondary" size="sm" label="Groupes"         class="justify-center" />
            <LinkButton :href="studentsIndex.url({ school: schoolSlug })"  variant="secondary" size="sm" label="Élèves"          class="justify-center" />
            <LinkButton :href="teachersIndex.url({ school: schoolSlug })"  variant="secondary" size="sm" label="Professeurs"     class="justify-center" />
            <LinkButton :href="lessonsIndex.url({ school: schoolSlug })"   variant="secondary" size="sm" label="Attributions"    class="justify-center" />
            <LinkButton :href="academicYearsIndex.url({ school: schoolSlug })" variant="secondary" size="sm" label="Années scolaires" class="col-span-2 justify-center" />
        </div>
    </DashboardCard>
</template>
