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

const visibleSchoolCount = computed(() => {
    const q = schoolSearch.value.trim().toLowerCase();

    return q
        ? props.schools.filter((s) => s.name.toLowerCase().includes(q)).length
        : props.schools.length;
});

const selectSize = computed(() => Math.min(Math.max(visibleSchoolCount.value, 2), 7));

const passwordCriteria = computed(() => {
    const p = form.password;

    return [
        { label: 'Au moins 8 caractères',  met: p.length >= 8 },
        { label: 'Une lettre majuscule',   met: /[A-Z]/.test(p) },
        { label: 'Un chiffre',             met: /[0-9]/.test(p) },
        { label: 'Un caractère spécial',   met: /[^A-Za-z0-9]/.test(p) },
    ];
});

const form = useForm({
    name:                  '',
    email:                 '',
    password:              '',
    password_confirmation: '',
    subject_ids:           [] as number[],
    school_ids:            [] as number[],
});

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
    } else if (passwordCriteria.value.some((c) => !c.met)) {
        errors.password = 'Le mot de passe ne respecte pas tous les critères requis.';
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
        <div class="flex flex-col gap-2">
            <InputLabel
                v-model="form.password"
                type="password"
                label="Mot de passe"
                placeholder="••••••••"
                autocomplete="new-password"
                required
                :error="stepErrors.password ?? form.errors.password ?? serverErrors.password"
            />
            <ul class="flex flex-col gap-0.5 pl-1" aria-label="Critères du mot de passe">
                <li
                    v-for="c in passwordCriteria"
                    :key="c.label"
                    :class="c.met ? 'text-green-600' : 'text-stone-400'"
                    class="flex items-center gap-1.5 text-xs"
                >
                    <span aria-hidden="true">{{ c.met ? '✓' : '○' }}</span>
                    {{ c.label }}
                </li>
            </ul>
        </div>
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

        <div class="flex flex-col gap-1.5">
            <SearchInput v-model="schoolSearch" placeholder="Rechercher un établissement..." />
            <p class="text-right text-xs text-stone-400">
                <span v-if="form.school_ids.length > 0" class="font-semibold text-blue">
                    {{ form.school_ids.length }} sélectionné{{ form.school_ids.length > 1 ? 's' : '' }}
                </span>
                <span v-else>Ctrl+clic pour sélectionner plusieurs établissements</span>
            </p>
        </div>

        <select
            v-if="visibleSchoolCount > 0"
            v-model="form.school_ids"
            multiple
            :size="selectSize"
            class="w-full rounded-2xl border border-border-figma bg-white font-manrope text-sm text-text-base transition-all focus:border-blue focus:outline-none focus:ring-2 focus:ring-blue/20 [&>option]:cursor-pointer [&>option]:px-4 [&>option]:py-2.5 [&>option:checked]:bg-blue [&>option:checked]:text-white"
        >
            <option
                v-for="school in schools"
                :key="school.id"
                :value="school.id"
                :hidden="!!schoolSearch.trim() && !school.name.toLowerCase().includes(schoolSearch.trim().toLowerCase())"
            >
                {{ school.name }}
            </option>
        </select>

        <div
            v-else-if="schoolSearch.trim()"
            class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-border-figma bg-bg-primary px-4 py-6 text-center"
        >
            <p class="text-sm font-medium text-text-base">
                Aucun établissement trouvé pour « {{ schoolSearch }} »
            </p>
            <p class="text-xs text-stone-400">
                Votre école n'est peut-être pas encore sur Educaxio, ou son nom est légèrement différent.
            </p>
            <a
                href="mailto:support@educaxio.be?subject=Demande d'ajout d'établissement"
                class="mt-1 text-xs font-medium text-blue hover:underline"
            >
                Contacter le support →
            </a>
        </div>

        <p v-if="form.errors.school_ids" class="text-sm font-medium text-pink">
            {{ form.errors.school_ids }}
        </p>

        <p class="rounded-xl bg-blue/5 px-3 py-2.5 text-xs text-stone-500">
            <span class="font-semibold text-text-base">Vous ne savez pas quelle école choisir ?</span>
            Rejoignez l'établissement où vous enseignez. Si votre école n'est pas encore sur Educaxio ou si vous devez en être l'administrateur, <a href="mailto:support@educaxio.be" class="font-medium text-blue hover:underline">contactez-nous</a>.
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
