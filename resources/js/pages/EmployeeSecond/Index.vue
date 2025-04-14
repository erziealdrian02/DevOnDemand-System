<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, CirclePlus, FolderInput, FolderOutput, Pencil, Search, Trash } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';

interface Employee {
    id: string;
    name: string;
    email: string;
    phone?: string;
    skillset?: string[];
    is_available: boolean;
}

interface EmployeePageProps extends SharedData {
    employees: Employee[];
    total?: number;
    per_page?: number;
    current_page?: number;
    last_page?: number;
}

const page = usePage();
const props = page.props as unknown as EmployeePageProps;
const allEmployees = computed(() => props.employees);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Employees', href: '/employees' }];

const searchTerm = ref('');
const currentPage = ref(props.current_page || 1);
const perPage = ref(props.per_page || 10);
const sortField = ref('name');
const sortDirection = ref<'asc' | 'desc'>('asc');
const isLoading = ref(false);

const showImportModal = ref(false);
const importFile = ref<File | null>(null);
const isImporting = ref(false);
const importErrors = ref<string[]>([]);

// SweetAlert: Flash success
const showSuccessMessage = (message: string) => {
    Swal.fire({
        title: 'Success!',
        text: message,
        icon: 'success',
        confirmButtonColor: '#6366f1',
        timer: 3000,
        timerProgressBar: true,
    });
};

onMounted(() => {
    const flash = page.props.flash as { success?: string; error?: string };

    if (flash?.success) showSuccessMessage(flash.success);
    if (flash?.error) {
        Swal.fire({
            title: 'Error!',
            text: flash.error,
            icon: 'error',
            confirmButtonColor: '#ef4444',
        });
    }
});

router.on('success', (event) => {
    const flash = (event.detail.page.props.flash as { success?: string })?.success;
    if (flash) showSuccessMessage(flash);
});

const filteredEmployees = computed(() => {
    let filtered = [...allEmployees.value];

    if (searchTerm.value) {
        const search = searchTerm.value.toLowerCase();
        filtered = filtered.filter(
            (e) =>
                e.name.toLowerCase().includes(search) ||
                e.email.toLowerCase().includes(search) ||
                e.phone?.toLowerCase().includes(search) ||
                (e.skillset?.join(', ').toLowerCase().includes(search) ?? false),
        );
    }

    filtered.sort((a, b) => {
        const getValue = (field: string, obj: Employee) => {
            switch (field) {
                case 'name':
                    return obj.name.toLowerCase();
                case 'email':
                    return obj.email.toLowerCase();
                case 'phone':
                    return obj.phone?.toLowerCase() || '';
                case 'is_available':
                    return obj.is_available ? '1' : '0';
                case 'skillset':
                    return obj.skillset?.join(', ').toLowerCase() || '';
                default:
                    return obj.name.toLowerCase();
            }
        };
        const valA = getValue(sortField.value, a);
        const valB = getValue(sortField.value, b);

        return sortDirection.value === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
    });

    return filtered;
});

const paginatedEmployees = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredEmployees.value.slice(start, start + perPage.value);
});

const totalPages = computed(() => Math.ceil(filteredEmployees.value.length / perPage.value));
const totalItems = computed(() => filteredEmployees.value.length);

const goToPage = (page: number) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
};

const handlePerPageChange = (e: Event) => {
    perPage.value = parseInt((e.target as HTMLSelectElement).value);
    currentPage.value = 1;
};

const sortBy = (field: string) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const getSortIcon = (field: string) => {
    if (sortField.value !== field) return '';
    return sortDirection.value === 'asc' ? '↑' : '↓';
};

const openImportModal = () => {
    showImportModal.value = true;
    importFile.value = null;
    importErrors.value = [];
};

const closeImportModal = () => {
    showImportModal.value = false;
    importFile.value = null;
    importErrors.value = [];
};

const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        importFile.value = input.files[0];
    } else {
        importFile.value = null;
    }
};

const submitImport = () => {
    if (!importFile.value) {
        Swal.fire({
            title: 'Error!',
            text: 'Please select a file to import',
            icon: 'error',
            confirmButtonColor: '#ef4444',
        });
        return;
    }

    isImporting.value = true;
    importErrors.value = [];

    // Create form data
    const formData = new FormData();
    formData.append('file', importFile.value);

    // Send to server
    router.post('/employeesSec/import', formData, {
        onSuccess: () => {
            isImporting.value = false;
            closeImportModal();
            Swal.fire({
                title: 'Success!',
                text: 'Employee imported successfully',
                icon: 'success',
                confirmButtonColor: '#6366f1',
            });
            // Refresh the page to show updated employeesSec
            router.visit('/employeesSec', { replace: true });
        },
        onError: (errors) => {
            isImporting.value = false;

            if (errors.file) {
                importErrors.value = Array.isArray(errors.file) ? errors.file : [errors.file as string];
            } else if (errors.error) {
                importErrors.value = [errors.error as string];
            } else {
                importErrors.value = ['An unknown error occurred during import'];
            }

            // If there are parsing errors in the response
            if (errors.parsing_errors) {
                const parsingErrors = errors.parsing_errors as Record<string, string[]>;
                for (const row in parsingErrors) {
                    importErrors.value.push(`Row ${row}: ${parsingErrors[row].join(', ')}`);
                }
            }
        },
    });
};

const deleteEmployee = (id: string) => {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This employee will be deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/employeesSec/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Employee has been deleted.', 'success');
                    router.visit('/employeesSec', { replace: true });
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Employees" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Button as-child size="sm" class="bg-indigo-500 text-white hover:bg-indigo-700">
                        <Link href="/employeesSec/create"> <CirclePlus class="mr-1" /> Create </Link>
                    </Button>

                    <Button as-child size="sm" class="bg-green-500 text-white hover:bg-green-700">
                        <Link href="/employeesSec/export" target="_blank"> <FolderInput class="mr-1" /> Print </Link>
                    </Button>

                    <Button size="sm" class="bg-blue-500 text-white hover:bg-blue-700" @click="openImportModal">
                        <FolderOutput class="mr-1" /> Import Excel
                    </Button>
                </div>
                <div class="relative">
                    <Search class="absolute left-2 top-2 h-4 w-4 text-gray-500" />
                    <Input v-model="searchTerm" placeholder="Search..." class="w-64 pl-8" />
                </div>
            </div>

            <div class="relative flex flex-1 flex-col rounded-xl border">
                <div class="flex-grow overflow-auto">
                    <Table>
                        <TableCaption>Employee List (Total: {{ totalItems }})</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="cursor-pointer" @click="sortBy('name')">Name {{ getSortIcon('name') }}</TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('email')">Email {{ getSortIcon('email') }}</TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('phone')">Phone {{ getSortIcon('phone') }}</TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('skillset')">Skillset {{ getSortIcon('skillset') }}</TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('is_available')"
                                    >Available {{ getSortIcon('is_available') }}</TableHead
                                >
                                <TableHead class="text-center">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="employee in paginatedEmployees" :key="employee.id">
                                <TableCell>{{ employee.name }}</TableCell>
                                <TableCell>{{ employee.email }}</TableCell>
                                <TableCell>{{ employee.phone ?? 'N/A' }}</TableCell>
                                <TableCell>
                                    <div class="line-clamp-2 text-sm text-gray-600 dark:text-gray-300">
                                        {{ (employee.skillset || []).slice(0, 3).join(', ') }}
                                        <span v-if="(employee.skillset || []).length > 3" class="text-xs opacity-70">
                                            +{{ (employee.skillset || []).length - 3 }} more
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <span
                                        :class="
                                            employee.is_available
                                                ? 'rounded-full bg-green-500 px-3 py-1 text-white'
                                                : 'rounded-full bg-orange-500 px-3 py-1 text-white'
                                        "
                                    >
                                        {{ employee.is_available ? 'Avaible' : 'On Duty' }}
                                    </span>
                                </TableCell>
                                <TableCell class="flex justify-center gap-2">
                                    <Button as-child size="sm" class="bg-blue-500 text-white hover:bg-blue-700">
                                        <Link :href="`/employeesSec/${employee.id}/edit`"><Pencil /></Link>
                                    </Button>
                                    <Button size="sm" class="bg-rose-500 text-white hover:bg-rose-700" @click="deleteEmployee(employee.id)">
                                        <Trash />
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="paginatedEmployees.length === 0">
                                <TableCell colspan="6" class="py-4 text-center">No employees found</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="mt-auto flex items-center justify-between border-t p-4">
                    <div class="flex items-center gap-2">
                        <select
                            :value="perPage"
                            @change="handlePerPageChange"
                            class="rounded border px-3 py-1 text-sm dark:border-gray-700 dark:bg-gray-800"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        Showing
                        {{ paginatedEmployees.length ? (currentPage - 1) * perPage + 1 : 0 }} to {{ Math.min(currentPage * perPage, totalItems) }} of
                        {{ totalItems }}
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            v-for="n in totalPages"
                            :key="n"
                            variant="outline"
                            size="sm"
                            :class="{ 'bg-indigo-500 text-white': currentPage === n }"
                            @click="goToPage(n)"
                        >
                            {{ n }}
                        </Button>
                        <Button variant="outline" size="sm" :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-bold">Import Employee</h2>
                <button @click="closeImportModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    &times;
                </button>
            </div>

            <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                Upload Excel file (.xlsx, .csv) with client data. Make sure the file follows the required format.
            </p>

            <div class="mb-4">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Excel File </label>
                <div class="flex items-center gap-2">
                    <input
                        type="file"
                        @change="handleFileChange"
                        accept=".xlsx,.csv,.xls"
                        class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-500 file:px-4 file:py-2 file:text-sm file:text-white dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                    />
                    <Upload v-if="!importFile" class="h-5 w-5 text-gray-500" />
                    <span v-else class="text-sm text-gray-600 dark:text-gray-300">
                        {{ importFile.name }}
                    </span>
                </div>
            </div>

            <div v-if="importErrors.length > 0" class="mb-4 rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Import errors</h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                            <ul class="list-disc space-y-1 pl-5">
                                <li v-for="(error, index) in importErrors" :key="index">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4 rounded-md border border-yellow-300 bg-yellow-50 p-4 dark:border-yellow-700 dark:bg-yellow-900/20">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Required Format</h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <p>Your Excel file should contain these columns in order:</p>
                            <ol class="mt-1 list-decimal pl-5">
                                <li>Name</li>
                                <li>Email</li>
                                <li>Phone</li>
                                <li>Skillset</li>
                                <li>Avaibility</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <Button variant="outline" @click="closeImportModal" :disabled="isImporting"> Cancel </Button>
                <Button @click="submitImport" class="bg-indigo-500 text-white hover:bg-indigo-700" :disabled="isImporting || !importFile">
                    <span
                        v-if="isImporting"
                        class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                    ></span>
                    {{ isImporting ? 'Importing...' : 'Import' }}
                </Button>
            </div>
        </div>
    </div>
</template>
