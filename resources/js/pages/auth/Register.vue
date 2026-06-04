<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Button from '@/components/widgets/Button.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import NoScriptWarning from '@/components/widgets/NoScriptWarning.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SubjectGrid from '@/components/widgets/SubjectGrid.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

interface Subject { id: number; name: string }
interface School  { id: number; name: string; slug: string }

const props = defineProps<{
    subjects: Subject[];
    schools:  School[];
}>();

defineOptions({
    layout: {
        title: 'Créez votre compte.',
        description: 'Rejoignez votre établissement scolaire.',
    },
});

const page = usePage();
const serverErrors = computed(() => page.props.errors);

const step = ref(1);
const stepErrors = ref<Record<string, string>>({});
const schoolSearch = ref('');

const form = useForm({
    name:                  '',
    email:                 '',
    password:              '',
    password_confirmation: '',
    subject_ids:           [] as number[],
    school_ids:            [] as number[],
});

const filteredSchools = computed(() => {
    const q = schoolSearch.value.trim().toLowerCase();

    return q ? props.schools.filter((s) => s.name.toLowerCase().includes(q)) : props.schools;
});

function toggleSchool(id: number) {
    const idx = form.school_ids.indexOf(id);

    if (idx === -1) {
        form.school_ids.push(id);
    } else {
        form.school_ids.splice(idx, 1);
    }
}

function validateStep1(): boolean {
    const errors: Record<string, string> = {};

    if (!form.name.trim()) {
        errors.name = 'Le nom est requis.';
    }

    if (!form.email.trim()) {
        errors.email = "L'adresse email est requise.";
    } else {
        const input = document.createElement('input');
        input.type = 'email';
        input.value = form.email;

        if (!input.validity.valid) {
            errors.email = "L'adresse email n'est pas valide.";
        }
    }

    if (!form.password) {
        errors.password = 'Le mot de passe est requis.';
    } else if (form.password.length < 8) {
        errors.password = 'Le mot de passe doit contenir au moins 8 caractères.';
    }

    if (form.password !== form.password_confirmation) {
        errors.password_confirmation = 'Les mots de passe ne correspondent pas.';
    }

    stepErrors.value = errors;

    return Object.keys(errors).length === 0;
}

async function next() {
    if (step.value === 1) {
        if (!validateStep1()) {
            return;
        }

        await new Promise<void>((resolve) => {
            form.post('/register/validate', {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: () => resolve(),
            });
        });

        if (form.hasErrors) {
            return;
        }
    }

    stepErrors.value = {};
    step.value++;
}

function back() {
    stepErrors.value = {};
    step.value--;
}

function submit() {
    form.post(store.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}

watch(
    () => form.errors,
    (errors) => {
        if (errors.name || errors.email || errors.password || errors.password_confirmation) {
            step.value = 1;
        } else if (errors.subject_ids) {
            step.value = 2;
        }
    },
    { deep: true },
);
</script>

<template>
    <Head title="Inscription" />

    <NoScriptWarning />

    <div class="mb-1 flex items-center gap-1.5">
        <div
            v-for="n in 3"
            :key="n"
            :class="[
                'h-1.5 rounded-full transition-all duration-300',
                step >= n ? 'w-8 bg-blue' : 'w-4 bg-gray-200',
            ]"
        />
    </div>

    <form v-if="step === 1" method="post" action="/register/validate" class="flex flex-col gap-5" @submit.prevent="next">
        <input type="hidden" name="_token" :value="page.props.csrf_token" />
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Étape 1 sur 3</p>
            <h3 class="text-xl font-bold text-text-base">Vos informations</h3>
        </div>

        <InputLabel
            v-model="form.name"
            label="Nom complet"
            placeholder="Jean Dupont"
            autocomplete="name"
            required
            :error="stepErrors.name ?? form.errors.name ?? serverErrors.name"
        />
        <InputLabel
            v-model="form.email"
            type="email"
            label="Adresse email"
            placeholder="jean@exemple.com"
            autocomplete="email"
            required
            :error="stepErrors.email ?? form.errors.email ?? serverErrors.email"
        />
        <InputLabel
            v-model="form.password"
            type="password"
            label="Mot de passe"
            placeholder="••••••••"
            autocomplete="new-password"
            required
            :error="stepErrors.password ?? form.errors.password ?? serverErrors.password"
        />
        <InputLabel
            v-model="form.password_confirmation"
            type="password"
            label="Confirmer le mot de passe"
            placeholder="••••••••"
            autocomplete="new-password"
            required
            :error="stepErrors.password_confirmation ?? form.errors.password_confirmation ?? serverErrors.password_confirmation"
        />

        <Button type="submit" variant="primary" size="md" label="Suivant →" class="w-full" :loading="form.processing" />

        <p class="text-center text-sm">
            <span class="text-neutral-500">Déjà un compte ? </span>
            <Link :href="login.url()" class="font-medium text-blue hover:underline">Se connecter</Link>
        </p>
    </form>

    <form v-else-if="step === 2" class="flex flex-col gap-5" @submit.prevent="next">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Étape 2 sur 3</p>
            <h3 class="text-xl font-bold text-text-base">Vos matières</h3>
            <p class="text-sm text-gray-500">Sélectionnez les matières que vous pouvez enseigner.</p>
        </div>

        <SubjectGrid v-model="form.subject_ids" :subjects="subjects" />

        <div class="flex gap-3">
            <Button type="button" variant="ghost" size="md" label="← Retour" class="w-full" @click="back" />
            <Button type="submit" variant="primary" size="md" label="Suivant →" class="w-full" />
        </div>
    </form>

    <form v-else class="flex flex-col gap-5" @submit.prevent="submit">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Étape 3 sur 3</p>
            <h3 class="text-xl font-bold text-text-base">Votre établissement</h3>
            <p class="text-sm text-gray-500">
                Trouvez votre école. Votre demande sera validée par l'administrateur.
            </p>
        </div>

        <SearchInput v-model="schoolSearch" placeholder="Rechercher un établissement..." />

        <div class="flex max-h-52 flex-col gap-2 overflow-y-auto pr-1">
            <button
                v-for="school in filteredSchools"
                :key="school.id"
                type="button"
                :class="[
                    'flex items-center gap-3 rounded-2xl border px-4 py-3 text-left transition-all duration-150',
                    form.school_ids.includes(school.id)
                        ? 'border-blue bg-blue/5'
                        : 'border-border-figma hover:border-blue',
                ]"
                @click="toggleSchool(school.id)"
            >
                <span
                    :class="[
                        'size-4 flex-shrink-0 rounded-md border-2 transition-colors',
                        form.school_ids.includes(school.id) ? 'border-blue bg-blue' : 'border-gray-300',
                    ]"
                />
                <span class="text-sm font-medium text-text-base">{{ school.name }}</span>
            </button>

            <p v-if="filteredSchools.length === 0" class="py-3 text-center text-sm text-gray-400">
                Aucun résultat
            </p>
        </div>

        <p v-if="form.errors.school_ids" class="text-sm font-medium text-pink">
            {{ form.errors.school_ids }}
        </p>

        <div class="flex gap-3">
            <Button type="button" variant="ghost" size="md" label="← Retour" class="w-full" @click="back" />
            <Button
                type="submit"
                variant="primary"
                size="md"
                label="Créer mon compte"
                class="w-full"
                :loading="form.processing"
                :disabled="form.school_ids.length === 0"
            />
        </div>
    </form>
</template>
