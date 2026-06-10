<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/widgets/Button.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import NoScriptWarning from '@/components/widgets/NoScriptWarning.vue';
import { login } from '@/routes';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Nouveau mot de passe',
        description: 'Choisissez un nouveau mot de passe pour votre compte.',
    },
});

const props = defineProps<{
    token: string;
    email: string;
}>();

const page = usePage();
const serverErrors = computed(() => page.props.errors);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(update.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Nouveau mot de passe" />

    <NoScriptWarning />

    <form method="post" :action="update.url()" class="flex flex-col gap-5" @submit.prevent="submit">
        <input type="hidden" name="_token" :value="page.props.csrf_token" />

        <div class="flex flex-col gap-1.5">
            <p class="font-manrope text-xs font-bold uppercase tracking-widest leading-4 text-border-figma">Email</p>
            <p class="rounded-2xl border border-border-figma bg-stone-50 px-3 py-3 font-manrope text-base font-semibold text-text-base opacity-60">
                {{ email }}
            </p>
        </div>

        <InputLabel
            v-model="form.password"
            label="Nouveau mot de passe"
            type="password"
            placeholder="••••••••••"
            autocomplete="new-password"
            required
            :error="form.errors.password || serverErrors.password"
        />

        <InputLabel
            v-model="form.password_confirmation"
            label="Confirmer le mot de passe"
            type="password"
            placeholder="••••••••••"
            autocomplete="new-password"
            required
            :error="form.errors.password_confirmation || serverErrors.password_confirmation"
        />

        <Button
            type="submit"
            variant="primary"
            size="md"
            label="Réinitialiser le mot de passe"
            :loading="form.processing"
            class="w-full"
        />

        <p class="text-center text-sm">
            <span class="text-neutral-500">Retour à la </span>
            <a :href="login.url()" class="font-medium text-blue hover:underline">connexion</a>
        </p>
    </form>
</template>
