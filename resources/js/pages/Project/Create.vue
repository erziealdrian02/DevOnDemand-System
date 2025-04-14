<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type BreadcrumbItem = { title: string; href: string };
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: 'Create', href: '#' },
];

const page = usePage<{
    clients: { id: string; company_name: string }[];
    employees: { id: string; name: string }[];
}>();
const clientOptions = computed(() => page.props.clients);
const employeeOptions = computed(() => page.props.employees);

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

// Employee search functionality for each item
const employeeDropdowns = ref<{
    [key: number]: {
        isOpen: boolean;
        searchQuery: string;
    };
}>({});

const getFilteredEmployees = (itemId: number) => {
    const dropdown = employeeDropdowns.value[itemId];
    if (!dropdown || !dropdown.searchQuery) return employeeOptions.value;

    const query = dropdown.searchQuery.toLowerCase();
    return employeeOptions.value.filter((employee) => employee.name.toLowerCase().includes(query));
};

const getSelectedEmployeeName = (employeeId: string) => {
    if (!employeeId) return '';
    const employee = employeeOptions.value.find((e) => e.id === employeeId);
    return employee ? employee.name : '';
};

const setEmployee = (itemId: number, employeeId: string) => {
    const item = form.value.additionalItems.find((item) => item.id === itemId);
    if (item) {
        item.employee_id = employeeId;
    }

    if (employeeDropdowns.value[itemId]) {
        employeeDropdowns.value[itemId].isOpen = false;
        employeeDropdowns.value[itemId].searchQuery = '';
    }
};

const initEmployeeDropdown = (itemId: number) => {
    if (!employeeDropdowns.value[itemId]) {
        employeeDropdowns.value[itemId] = {
            isOpen: false,
            searchQuery: '',
        };
    }
};

const toggleEmployeeDropdown = (itemId: number) => {
    initEmployeeDropdown(itemId);
    employeeDropdowns.value[itemId].isOpen = !employeeDropdowns.value[itemId].isOpen;
};

const employeeDropdownRefs = ref<{ [key: number]: HTMLDivElement | null }>({});

const handleEmployeeClickOutside = (event: MouseEvent, itemId: number) => {
    const dropdownRef = employeeDropdownRefs.value[itemId];
    if (dropdownRef && !dropdownRef.contains(event.target as Node)) {
        if (employeeDropdowns.value[itemId]) {
            employeeDropdowns.value[itemId].isOpen = false;
        }
    }
};

// Watch for employee dropdown changes
watch(
    employeeDropdowns,
    (dropdowns) => {
        for (const itemId in dropdowns) {
            if (dropdowns[itemId].isOpen) {
                setTimeout(() => {
                    const handler = (event: MouseEvent) => handleEmployeeClickOutside(event, parseInt(itemId));
                    window.addEventListener('mousedown', handler);

                    // Store cleanup function
                    return () => {
                        window.removeEventListener('mousedown', handler);
                    };
                }, 0);
            }
        }
    },
    { deep: true },
);

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

// Define additional form item structure
interface AdditionalFormItem {
    id: number;
    start_date: string;
    end_date: string;
    notes: string;
    employee_id: string;
    attachment: File | null;
}

// Generate unique ID for form items
let nextFormItemId = 1;

const form = ref({
    client_id: '',
    project_name: '',
    start_date: '',
    is_approved: false,
    settings: {
        lokasi: '',
        cost: '',
        employee_count: '0',
    },
    // For additional forms that appear when approved
    additionalItems: [] as AdditionalFormItem[],
});

// Compute employee count based on filled employee_id fields
const employeeCount = computed(() => {
    if (!form.value.is_approved || form.value.additionalItems.length === 0) {
        return '0';
    }

    const filledEmployeeIds = form.value.additionalItems.filter((item) => item.employee_id && item.employee_id.trim() !== '').length;

    return filledEmployeeIds.toString();
});

// Update employee_count whenever additionalItems changes
watch(employeeCount, (count) => {
    form.value.settings.employee_count = count;
});

const addNewFormItem = () => {
    const newItem = {
        id: nextFormItemId++,
        start_date: '',
        end_date: '',
        notes: '',
        employee_id: '',
        attachment: null,
    };

    form.value.additionalItems.push(newItem);
    initEmployeeDropdown(newItem.id);
};

const removeFormItem = (id: number) => {
    const index = form.value.additionalItems.findIndex((item) => item.id === id);
    if (index !== -1) {
        form.value.additionalItems.splice(index, 1);

        // Remove dropdown state
        if (employeeDropdowns.value[id]) {
            delete employeeDropdowns.value[id];
        }
    }
};

// Initialize with one form item when approved
watch(
    () => form.value.is_approved,
    (isApproved) => {
        if (isApproved && form.value.additionalItems.length === 0) {
            addNewFormItem();
        }
    },
);

const resetForm = () => {
    form.value = {
        client_id: '',
        project_name: '',
        start_date: '',
        is_approved: false,
        settings: {
            lokasi: '',
            cost: '',
            employee_count: '0',
        },
        additionalItems: [],
    };
    nextFormItemId = 1;
    employeeDropdowns.value = {};
};

const submit = () => {
    // Create FormData for file uploads
    const formData = new FormData();

    // Append basic form fields
    formData.append('client_id', form.value.client_id);
    formData.append('project_name', form.value.project_name);
    formData.append('start_date', form.value.start_date);
    formData.append('is_approved', form.value.is_approved ? '1' : '0');
    formData.append('settings', JSON.stringify(form.value.settings));

    // Append additional items if approved
    if (form.value.is_approved) {
        // Create a clean version of additionalItems without the file objects
        const additionalItemsForJSON = form.value.additionalItems.map((item) => ({
            id: item.id,
            start_date: item.start_date,
            end_date: item.end_date,
            notes: item.notes,
            employee_id: item.employee_id,
        }));

        formData.append('additionalItems', JSON.stringify(additionalItemsForJSON));

        // Append files separately
        form.value.additionalItems.forEach((item, index) => {
            if (item.attachment) {
                formData.append(`attachment_${item.id}`, item.attachment);
            }
        });
    }

    router.post('/projects', formData, {
        onSuccess: resetForm,
    });
};

const handleFileChange = (event: Event, itemId: number) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] || null;

    const item = form.value.additionalItems.find((item) => item.id === itemId);
    if (item) {
        item.attachment = file;
    }
};
</script>

<template>
    <Head title="Create Project" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-12">
            <h1 class="text-2xl font-bold">Create Project</h1>
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
                        <Input id="employee_count" v-model="form.settings.employee_count" type="text" readonly class="bg-gray-100 dark:bg-gray-900" />
                    </div>
                </div>

                <!-- Additional forms that appear when approved -->
                <div v-if="form.is_approved" class="col-span-1 mt-6 space-y-6 md:col-span-3">
                    <h2 class="text-xl font-semibold">Detail Project</h2>

                    <div v-for="item in form.additionalItems" :key="item.id" class="relative space-y-4 rounded-lg border p-4">
                        <!-- Remove button -->
                        <button
                            v-if="form.additionalItems.length > 1"
                            type="button"
                            @click="removeFormItem(item.id)"
                            class="absolute right-2 top-2 text-red-500 hover:text-red-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label :for="`start_date_${item.id}`">Start Date</Label>
                                <Input :id="`start_date_${item.id}`" v-model="item.start_date" type="date" required />
                            </div>
                            <div class="space-y-2">
                                <Label :for="`end_date_${item.id}`">End Date</Label>
                                <Input :id="`end_date_${item.id}`" v-model="item.end_date" type="date" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label :for="`notes_${item.id}`">Notes</Label>
                            <textarea
                                :id="`notes_${item.id}`"
                                v-model="item.notes"
                                rows="4"
                                class="w-full rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                                placeholder="Keterangan atau catatan tambahan..."
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <!-- Searchable Employee Dropdown -->
                            <div class="space-y-2">
                                <Label :for="`employee_id_${item.id}`">Employee</Label>
                                <div :ref="(el) => (employeeDropdownRefs[item.id] = el as HTMLDivElement | null)" class="relative">
                                    <div
                                        @click="toggleEmployeeDropdown(item.id)"
                                        class="flex w-full cursor-pointer items-center justify-between rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                                    >
                                        <span v-if="item.employee_id">{{ getSelectedEmployeeName(item.employee_id) }}</span>
                                        <span v-else class="text-gray-500">Pilih Karyawan</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>

                                    <div
                                        v-if="employeeDropdowns[item.id]?.isOpen"
                                        class="absolute z-10 mt-1 w-full rounded-md border bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                    >
                                        <div class="p-2">
                                            <Input
                                                v-model="employeeDropdowns[item.id].searchQuery"
                                                type="text"
                                                placeholder="Cari karyawan..."
                                                class="w-full"
                                                @click.stop
                                                autofocus
                                            />
                                        </div>
                                        <ul class="max-h-60 overflow-auto py-1">
                                            <li v-if="getFilteredEmployees(item.id).length === 0" class="px-3 py-2 text-sm text-gray-500">
                                                Tidak ada hasil
                                            </li>
                                            <li
                                                v-for="employee in getFilteredEmployees(item.id)"
                                                :key="employee.id"
                                                @click="setEmployee(item.id, employee.id)"
                                                class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                                            >
                                                {{ employee.name }}
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Hidden select for form validation -->
                                    <select class="hidden" v-model="item.employee_id" required>
                                        <option v-for="employee in employeeOptions" :key="employee.id" :value="employee.id">
                                            {{ employee.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label :for="`attachment_${item.id}`">Upload Attachment (PDF)</Label>
                                <Input :id="`attachment_${item.id}`" type="file" accept=".pdf" @change="(e: Event) => handleFileChange(e, item.id)" />
                            </div>
                        </div>
                    </div>

                    <!-- Add more button -->
                    <div class="flex justify-center">
                        <Button type="button" @click="addNewFormItem" variant="outline" class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            Add More Detail
                        </Button>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="col-span-1 flex gap-4 pt-4 md:col-span-3">
                    <Button type="submit" class="bg-indigo-500 hover:bg-indigo-600">Save</Button>
                    <Button as="a" href="/projects" variant="outline">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
