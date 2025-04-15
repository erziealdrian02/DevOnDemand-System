<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

type BreadcrumbItem = { title: string; href: string };
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: 'Edit', href: '#' },
];

interface Project {
    id: string;
    client_id: string;
    project_name: string;
    start_date: string;
    is_approved: boolean;
    settings: string; // This is a JSON string
}

const page = usePage<{
    project: Project;
    clients: { id: string; company_name: string }[];
    employees: { id: string; name: string }[];
}>();

const clientOptions = computed(() => page.props.clients);

// Parse the project data
const projectData = computed(() => {
    const project = page.props.project;
    return {
        ...project,
        settings: typeof project.settings === 'string' ? JSON.parse(project.settings) : project.settings,
    };
});

// Client search functionality
const clientSearchQuery = ref('');
const isClientDropdownOpen = ref(false);
const filteredClients = computed(() => {
    if (!clientSearchQuery.value) return clientOptions.value;
    const query = clientSearchQuery.value.toLowerCase();
    return clientOptions.value.filter((client) => client.company_name.toLowerCase().includes(query));
});

// Get selected client name
const selectedClientName = computed(() => {
    if (!form.value.client_id) return '';
    const client = clientOptions.value.find((c) => c.id === form.value.client_id);
    return client ? client.company_name : '';
});

const setClient = (clientId: string) => {
    form.value.client_id = clientId;
    isClientDropdownOpen.value = false;
    clientSearchQuery.value = '';
};

// Click outside to close client dropdown
const clientDropdownRef = ref<HTMLDivElement | null>(null);
const handleClientClickOutside = (event: MouseEvent) => {
    if (clientDropdownRef.value && !clientDropdownRef.value.contains(event.target as Node)) {
        isClientDropdownOpen.value = false;
    }
};

// Add event listener for client dropdown
watch(isClientDropdownOpen, (isOpen) => {
    if (isOpen) {
        setTimeout(() => {
            window.addEventListener('mousedown', handleClientClickOutside);
        }, 0);
    } else {
        window.removeEventListener('mousedown', handleClientClickOutside);
    }
});

const costFormatted = computed({
    get() {
        const raw = form.value.settings.cost?.toString() || '';
        return raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },
    set(val: string) {
        // Hapus titik, simpan hanya angka
        form.value.settings.cost = val.replace(/\./g, '');
    },
});

// Format date for input fields
const formatDateForInput = (dateString: string | null) => {
    if (!dateString) return '';

    // If it's already in YYYY-MM-DD format, just return it
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return dateString;

    // Otherwise try to convert to local date format
    try {
        const date = new Date(dateString);
        return date.toISOString().slice(0, 10); // Format: YYYY-MM-DD for date input
    } catch (e) {
        return dateString; // Return original if conversion fails
    }
};

const form = ref({
    id: '',
    client_id: '',
    project_name: '',
    start_date: '',
    is_approved: false,
    settings: {
        lokasi: '',
        cost: '',
        employee_count: '0',
    },
});

// Initialize form with project data
const initializeForm = () => {
    const project = projectData.value;
    const settings = project.settings || {};

    form.value = {
        id: project.id,
        client_id: project.client_id || '',
        project_name: project.project_name || '',
        start_date: formatDateForInput(project.start_date) || '',
        is_approved: project.is_approved || false,
        settings: {
            lokasi: settings.lokasi || '',
            cost: settings.cost || '',
            employee_count: settings.employee_count || '0',
        },
    };
};

const submit = () => {
    router.put(
        `/projects/${form.value.id}`,
        {
            client_id: form.value.client_id,
            project_name: form.value.project_name,
            start_date: form.value.start_date,
            is_approved: form.value.is_approved ? '1' : '0',
            settings: form.value.settings,
        },
        {
            onSuccess: () => {
                router.visit('/projects');
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
            },
        },
    );
};

// Initialize form on component mount
onMounted(() => {
    initializeForm();
});
</script>

<template>
    <Head title="Edit Project" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-12">
            <h1 class="text-2xl font-bold">Edit Project</h1>
            <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 rounded-2xl border p-6 md:grid-cols-[1fr_1px_1fr]">
                <!-- LEFT: Project Info -->
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="client_id">Client (Perusahaan)</Label>
                        <div ref="clientDropdownRef" class="relative">
                            <div
                                @click="isClientDropdownOpen = true"
                                class="flex w-full cursor-pointer items-center justify-between rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                            >
                                <span v-if="selectedClientName">{{ selectedClientName }}</span>
                                <span v-else class="text-gray-500">Pilih Perusahaan</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>

                            <div
                                v-if="isClientDropdownOpen"
                                class="absolute z-10 mt-1 w-full rounded-md border bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                            >
                                <div class="p-2">
                                    <Input
                                        v-model="clientSearchQuery"
                                        type="text"
                                        placeholder="Cari perusahaan..."
                                        class="w-full"
                                        @click.stop
                                        autofocus
                                    />
                                </div>
                                <ul class="max-h-60 overflow-auto py-1">
                                    <li v-if="filteredClients.length === 0" class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil</li>
                                    <li
                                        v-for="client in filteredClients"
                                        :key="client.id"
                                        @click="setClient(client.id)"
                                        class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                                    >
                                        {{ client.company_name }}
                                    </li>
                                </ul>
                            </div>

                            <!-- Hidden select for form validation -->
                            <select class="hidden" v-model="form.client_id" required>
                                <option v-for="client in clientOptions" :key="client.id" :value="client.id">
                                    {{ client.company_name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="project_name">Project Name</Label>
                        <Input id="project_name" v-model="form.project_name" type="text" placeholder="Project Name" required />
                    </div>
                    <div class="space-y-2">
                        <Label for="start_date">Start Date</Label>
                        <Input id="start_date" v-model="form.start_date" type="date" required />
                    </div>
                    <div class="flex items-center space-x-2 pt-4">
                        <Switch id="is_approved" v-model:checked="form.is_approved" />
                        <Label for="is_approved">Approved</Label>
                    </div>
                </div>

                <div class="bg-white/10" />

                <!-- RIGHT: Settings -->
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="lokasi">Lokasi</Label>
                        <Input id="lokasi" v-model="form.settings.lokasi" type="text" placeholder="Project Location" />
                    </div>
                    <div class="space-y-2">
                        <Label for="cost">Cost (Rp)</Label>
                        <Input id="cost" v-model="costFormatted" type="text" inputmode="numeric" placeholder="Project Cost" />
                    </div>
                    <div class="space-y-2">
                        <Label for="employee_count">Jumlah Employee</Label>
                        <Input id="employee_count" v-model="form.settings.employee_count" type="text" placeholder="Jumlah Employee" />
                    </div>
                </div>

                <!-- Buttons -->
                <div class="col-span-1 flex gap-4 pt-4 md:col-span-3">
                    <Button type="submit" class="bg-indigo-500 hover:bg-indigo-600">Update Project</Button>
                    <Button as="a" href="/projects" variant="outline">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
