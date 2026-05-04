<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import NavItem from '@/components/widgets/nav/NavItem.vue';
import ClassesList from '@/components/widgets/svg/ClassesList.vue';
import { dashboard, classlist } from '@/routes';
import Button from '../components/widgets/Button.vue';
const name = ref('');
const email = ref('john@example.com');
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <NavItem :href="classlist.url()" title="Liste des classes">
                    <template #icon>
                        <ClassesList :size="20" :stroke-width="2" />
                    </template>
                </NavItem>
                <form action="" method="get">
                    <InputLabel
                        v-model="name"
                        label="name"
                        placeholder="Placeholder"
                    />
                    <InputLabel
                        v-model="email"
                        label="Email"
                        placeholder="Placeholder"
                    />
                    <Button
                        variant="secondary"
                        size="sm"
                        label="Test"
                        type="submit"
                    />
                    <Button
                        variant="primary"
                        size="md"
                        label="Test"
                        type="submit"
                    />
                    <Button
                        variant="danger"
                        size="lg"
                        label="Test"
                        type="submit"
                    />
                </form>
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
