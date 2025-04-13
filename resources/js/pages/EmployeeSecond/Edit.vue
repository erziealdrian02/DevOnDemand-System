<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';

type BreadcrumbItem = { title: string; href: string };
const { props } = usePage();
type Employee = {
    id: number;
    name: string;
    email: string;
    phone: string;
    skillset?: string | string[];
    is_available?: boolean;
};

const employee = props.employee as Employee;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Employees', href: '/employees' },
    { title: 'Edit', href: '#' },
];

// Daftar contoh skill
const availableSkills = [
    'Node.js',
    'JavaScript',
    'TypeScript',
    'React.js',
    'Vue.js',
    'UI Design',
    'UX Design',
    'Git',
    'PHP',
    'Laravel',
    'MySQL',
    'MongoDB',
    'REST API',
    'GraphQL',
    'Docker',
    'AWS',
    'DevOps',
    'Figma',
    'CSS',
    'HTML',
    'Tailwind CSS',
    'Microsoft Office',
    'Canva Design',
    'Python',
    'Angular',
    'SEO',
    'Content Writing',
    'Digital Marketing',
    'Kerja Tim',
];

// Form State
const form = reactive({
    name: '',
    email: '',
    phone: '',
    skillset: [] as string[],
    is_available: true,
});

// Pencarian skill
const skillSearch = ref('');
const isSkillModalOpen = ref(false);

// Filtered skills berdasarkan pencarian
const filteredSkills = computed(() => {
    if (!skillSearch.value) return availableSkills;
    return availableSkills.filter((skill) => skill.toLowerCase().includes(skillSearch.value.toLowerCase()));
});

onMounted(() => {
    form.name = employee.name ?? '';
    form.email = employee.email ?? '';
    form.phone = employee.phone ?? '';

    // Handle skillset data
    if (employee.skillset) {
        // If skillset is already an array, use it directly
        if (Array.isArray(employee.skillset)) {
            form.skillset = [...employee.skillset];
        }
        // If skillset is a string (comma-separated), split it
        else if (typeof employee.skillset === 'string') {
            form.skillset = employee.skillset
                .split(',')
                .map((s) => s.trim())
                .filter((s) => s !== '');
        }
    }

    form.is_available = employee.is_available ?? true;
});

// Tambah skill
const addSkill = (skill: string) => {
    if (!form.skillset.includes(skill) && form.skillset.length < 10) {
        form.skillset.push(skill);
    }
    skillSearch.value = '';
};

// Hapus skill
const removeSkill = (skill: string) => {
    form.skillset = form.skillset.filter((s) => s !== skill);
};

// Toggle modal skill
const toggleSkillModal = () => {
    isSkillModalOpen.value = !isSkillModalOpen.value;
    if (isSkillModalOpen.value) {
        // Focus on search input when modal opens
        setTimeout(() => {
            document.getElementById('skill-search')?.focus();
        }, 100);
    }
};

const resetForm = () => {
    form.name = '';
    form.email = '';
    form.phone = '';
    form.skillset = [];
    form.is_available = true;
};

const submit = () => {
    router.put(`/employeesSec/${employee.id}`, form, {
        onSuccess: () => {
            // Optional: redirect back to employees list
            // router.visit('/employees');
        },
    });
};
</script>

<template>
    <Head title="Edit Employee" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-12">
            <h1 class="text-2xl font-bold">Edit Employee</h1>
            <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 rounded-2xl border p-6 md:grid-cols-[1fr_1px_1fr]">
                <!-- LEFT: Main Info -->
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" type="text" required placeholder="Employee name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" required placeholder="Employee email" />
                    </div>
                    <div class="space-y-2">
                        <Label for="phone">Phone</Label>
                        <Input id="phone" v-model="form.phone" type="text" placeholder="Phone number" />
                    </div>
                    <div class="flex items-center space-x-2 pt-4">
                        <Switch id="is_available" v-model:checked="form.is_available" />
                        <Label for="is_available">Available</Label>
                    </div>
                </div>

                <!-- Divider -->
                <div class="h-full w-full rounded-full bg-white/10" />

                <!-- RIGHT: Skillset -->
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="skillset">Skillset</Label>
                        <div class="relative">
                            <div
                                @click="toggleSkillModal"
                                class="flex min-h-10 w-full cursor-pointer items-center rounded-md border border-input bg-transparent px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <span v-if="form.skillset.length === 0" class="text-muted-foreground"> Pilih skill (max 10) </span>
                                <div v-else class="flex flex-wrap gap-2">
                                    <div
                                        v-for="skill in form.skillset"
                                        :key="skill"
                                        class="flex items-center rounded-full bg-indigo-100 px-3 py-1 text-sm text-indigo-800"
                                    >
                                        {{ skill }}
                                        <button
                                            type="button"
                                            @click.stop="removeSkill(skill)"
                                            class="ml-1 rounded-full p-1 text-indigo-500 hover:bg-indigo-200 hover:text-indigo-700"
                                        >
                                            <X class="h-3 w-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm text-muted-foreground">Pilih maksimal 10 skill terkuat</p>
                    </div>
                </div>

                <!-- Modal untuk pilih skill -->
                <div v-if="isSkillModalOpen" class="fixed inset-0 z-50 flex items-start justify-center pt-16">
                    <div class="fixed inset-0 bg-black/50" @click="toggleSkillModal"></div>
                    <div class="relative z-50 w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-medium">Skills</h3>
                            <button type="button" @click="toggleSkillModal" class="rounded-full p-1 hover:bg-gray-100">
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <p class="mb-2 text-sm text-gray-600">
                            Pilih 3-10 skill terkuat kamu. Hal ini akan memungkinkan rekruter untuk lebih memahami kesesuaian kamu untuk pekerjaan
                            tersebut.
                        </p>

                        <div class="mb-4">
                            <Input id="skill-search" v-model="skillSearch" type="text" placeholder="Cari skill" class="w-full" />
                        </div>

                        <p class="mb-2 text-sm font-medium text-gray-600">{{ form.skillset.length }} skill dipilih</p>

                        <div class="mb-4 flex flex-wrap gap-2">
                            <div
                                v-for="skill in form.skillset"
                                :key="skill"
                                class="flex items-center rounded-full bg-indigo-100 px-3 py-1 text-sm text-indigo-800"
                            >
                                {{ skill }}
                                <button
                                    type="button"
                                    @click="removeSkill(skill)"
                                    class="ml-1 rounded-full p-1 text-indigo-500 hover:bg-indigo-200 hover:text-indigo-700"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>

                        <div class="max-h-60 overflow-y-auto">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="skill in filteredSkills"
                                    :key="skill"
                                    type="button"
                                    @click="addSkill(skill)"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-sm',
                                        form.skillset.includes(skill)
                                            ? 'border-indigo-200 bg-indigo-100 text-indigo-800'
                                            : 'border-gray-200 bg-white text-gray-800 hover:bg-gray-100',
                                    ]"
                                    :disabled="form.skillset.length >= 10 && !form.skillset.includes(skill)"
                                >
                                    {{ skill }}
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end space-x-2">
                            <Button type="button" variant="outline" @click="toggleSkillModal"> Batal </Button>
                            <Button type="button" @click="toggleSkillModal" class="bg-indigo-500 hover:bg-indigo-600"> Simpan </Button>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="col-span-1 flex gap-4 pt-4 md:col-span-3">
                    <Button type="submit" class="bg-indigo-500 hover:bg-indigo-600">Update</Button>
                    <Button as="a" href="/employees" variant="outline">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
