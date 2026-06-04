<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/widgets/Button.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import NoScriptWarning from '@/components/widgets/NoScriptWarning.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request as forgotPassword } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Bon retour.',
        description: 'Connectez-vous à votre espace de travail.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const page = usePage();
const serverErrors = computed(() => page.props.errors);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(store.url(), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Connexion" />

    <NoScriptWarning />

    <div v-if="status" class="text-sm font-medium text-green-600 text-center">
        {{ status }}
    </div>

    <form method="post" :action="store.url()" class="flex flex-col gap-5" @submit.prevent="submit">
        <input type="hidden" name="_token" :value="page.props.csrf_token" />
        <InputLabel
            v-model="form.email"
            label="Adresse email"
            type="email"
            placeholder="email@exemple.com"
            autocomplete="email"
            required
            :error="form.errors.email || serverErrors.email"
        />

        <div class="flex flex-col gap-1">
            <InputLabel
                v-model="form.password"
                label="Mot de passe"
                type="password"
                placeholder="••••••••••"
                autocomplete="current-password"
                required
                :error="form.errors.password || serverErrors.password"
            />
            <Link
                v-if="canResetPassword"
                :href="forgotPassword.url()"
                class="self-start text-sm text-blue hover:underline"
            >
                Mot de passe oublié ?
            </Link>
        </div>

        <label class="flex cursor-pointer items-center gap-1.5">
            <input
                v-model="form.remember"
                type="checkbox"
                class="size-3.5 accent-blue"
            />
            <span class="text-sm text-text-base">Se souvenir de moi</span>
        </label>

        <Button
            type="submit"
            variant="primary"
            size="md"
            label="Se connecter"
            :loading="form.processing"
            class="w-full"
        />

        <p v-if="canRegister" class="text-center text-sm">
            <span class="text-neutral-500">Pas de compte ? </span>
            <Link :href="register.url()" class="font-medium text-pink hover:underline">
                Créez un compte gratuitement
            </Link>
        </p>
    </form>
</template>