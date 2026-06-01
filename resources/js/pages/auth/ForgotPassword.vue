<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/widgets/Button.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import NoScriptWarning from '@/components/widgets/NoScriptWarning.vue';
import { login } from '@/routes';
import { email as sendResetLink } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Mot de passe oublié',
        description: 'Recevez un lien de réinitialisation par email.',
    },
});

defineProps<{
    status?: string;
}>();

const page = usePage();
const serverErrors = computed(() => page.props.errors);

const form = useForm({ email: '' });

function submit() {
    form.post(sendResetLink.url());
}
</script>

<template>
    <Head title="Mot de passe oublié" />

    <NoScriptWarning />

    <div v-if="status" class="text-sm font-medium text-green-600 text-center">
        {{ status }}
    </div>

    <form method="post" :action="sendResetLink.url()" class="flex flex-col gap-5" @submit.prevent="submit">
        <input type="hidden" name="_token" :value="page.props.csrf_token" />
        <InputLabel
            v-model="form.email"
            label="Adresse email"
            type="email"
            placeholder="email@exemple.com"
            :error="form.errors.email || serverErrors.email"
        />

        <Button
            type="submit"
            variant="primary"
            size="md"
            label="Envoyer le lien"
            :loading="form.processing"
            class="w-full"
        />

        <p class="text-center text-sm">
            <span class="text-neutral-500">Retour à la </span>
            <a :href="login.url()" class="font-medium text-blue hover:underline">connexion</a>
        </p>
    </form>
</template>
