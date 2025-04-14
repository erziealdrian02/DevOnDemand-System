<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, CirclePlus, FolderInput, FolderOutput, Pencil, Search, Trash, Upload } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
// Import SweetAlert2
import Swal from 'sweetalert2';

interface Client {
    id: string;
    name: string;
    email: string;
    company_name: string;
    metadata?: {
        npwp?: string;
        industri?: string;
    };
    is_active: boolean;
}

interface ClientPageProps extends SharedData {
    clients: Client[];
    total?: number;
    per_page?: number;
    current_page?: number;
    last_page?: number;
}

const page = usePage();
const props = page.props as unknown as ClientPageProps;
const allClients = computed(() => props.clients);
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Clients', href: '/clients' }];

// Search and filter functionality
const searchTerm = ref('');
const currentPage = ref(props.current_page || 1);
const perPage = ref(props.per_page || 10);
const sortField = ref('name');
const sortDirection = ref('asc');
const isLoading = ref(false);

// Import modal state
const showImportModal = ref(false);
const importFile = ref<File | null>(null);
const isImporting = ref(false);
const importErrors = ref<string[]>([]);

// SweetAlert2 function for success message
const showSuccessMessage = (message: string) => {
    Swal.fire({
        title: 'Success!',
        text: message,
        icon: 'success',
        confirmButtonColor: '#6366f1', // Indigo color to match your theme
        timer: 3000,
        timerProgressBar: true,
    });
};

// Check for flash messages on component mount
onMounted(() => {
    // Check directly from the page object for flash messages
    // This accesses the flash messages regardless of their structure
    const flash = page.props.flash as { success?: string; error?: string };

    if (flash && (flash as { success?: string }).success) {
        showSuccessMessage(flash.success ?? '');
    }

    if (flash?.error) {
        Swal.fire({
            title: 'Error!',
            text: flash.error,
            icon: 'error',
            confirmButtonColor: '#ef4444', // Red color
        });
    }

    // Alternative access method if the above doesn't work
    const message = page.props.success || page.props.message;
    if (message) {
        showSuccessMessage(message as string);
    }
});

// Listen for Inertia page updates to catch flash messages during navigation
router.on('success', (event) => {
    const flashMessage = (event.detail.page.props.flash as { success?: string })?.success;
    if (flashMessage) {
        showSuccessMessage(flashMessage);
    }
});

// Computed property for filtered and paginated clients
const filteredClients = computed(() => {
    if (!allClients.value) return [];

    // If server-side pagination is implemented, return all clients
    if (props.total !== undefined) return allClients.value;

    // Client-side filtering
    let filtered = [...allClients.value];

    if (searchTerm.value) {
        const search = searchTerm.value.toLowerCase();
        filtered = filtered.filter(
            (client) =>
                client.name.toLowerCase().includes(search) ||
                client.email.toLowerCase().includes(search) ||
                client.company_name.toLowerCase().includes(search) ||
                (client.metadata?.industri || '').toLowerCase().includes(search),
        );
    }

    // Sort clients
    filtered.sort((a, b) => {
        let valueA, valueB;

        switch (sortField.value) {
            case 'name':
                valueA = a.name.toLowerCase();
                valueB = b.name.toLowerCase();
                break;
            case 'email':
                valueA = a.email.toLowerCase();
                valueB = b.email.toLowerCase();
                break;
            case 'company':
                valueA = a.company_name.toLowerCase();
                valueB = b.company_name.toLowerCase();
                break;
            case 'industry':
                valueA = (a.metadata?.industri || '').toLowerCase();
                valueB = (b.metadata?.industri || '').toLowerCase();
                break;
            case 'active':
                valueA = a.is_active ? '1' : '0';
                valueB = b.is_active ? '1' : '0';
                break;
            default:
                valueA = a.name.toLowerCase();
                valueB = b.name.toLowerCase();
        }

        if (sortDirection.value === 'asc') {
            return valueA > valueB ? 1 : -1;
        } else {
            return valueA < valueB ? 1 : -1;
        }
    });

    return filtered;
});

// Client-side pagination computed properties
const paginatedClients = computed(() => {
    if (props.total !== undefined) return filteredClients.value; // If server-side pagination

    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredClients.value.slice(start, end);
});

const totalPages = computed(() => {
    if (props.last_page) return props.last_page;
    return Math.ceil(filteredClients.value.length / perPage.value);
});

const totalItems = computed(() => {
    if (props.total !== undefined) return props.total;
    return filteredClients.value.length;
});

// Pagination methods
const goToPage = (page: number) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;

    if (props.total !== undefined) {
        // Server-side pagination
        fetchData();
    }
};

const fetchData = () => {
    isLoading.value = true;

    router.get(
        '/clients',
        {
            page: currentPage.value,
            per_page: perPage.value,
            search: searchTerm.value,
            sort_field: sortField.value,
            sort_direction: sortDirection.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
};

// Handle per page change
const handlePerPageChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    perPage.value = parseInt(target.value);
    currentPage.value = 1; // Reset to first page

    if (props.total !== undefined) {
        fetchData();
    }
};

// Watch for changes that require refetching from server
watch([searchTerm], () => {
    if (props.total !== undefined) {
        // Reset to first page when search changes
        currentPage.value = 1;
        // Debounce search
        const handler = setTimeout(() => {
            fetchData();
        }, 500);
        return () => clearTimeout(handler);
    }
});

watch([sortField, sortDirection], () => {
    if (props.total !== undefined) {
        fetchData();
    }
});

// Watch for flash messages on the page props
watch(
    () => page.props.flash,
    (newFlash) => {
        if (newFlash && (newFlash as { success?: string }).success) {
            if ((newFlash as { success?: string }).success) {
                showSuccessMessage((newFlash as { success: string }).success);
            }
        }
    },
    { deep: true },
);

// Sort functionality
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

// Import modal functions
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
    router.post('/clients/import', formData, {
        onSuccess: () => {
            isImporting.value = false;
            closeImportModal();
            Swal.fire({
                title: 'Success!',
                text: 'Clients imported successfully',
                icon: 'success',
                confirmButtonColor: '#6366f1',
            });
            // Refresh the page to show updated clients
            router.visit('/clients', { replace: true });
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

const deleteClient = async (id: string) => {
    // Use SweetAlert2 for delete confirmation
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/clients/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Client has been deleted.', 'success');
                    router.visit('/clients', { replace: true });
                },
                onError: (errors) => {
                    Swal.fire('Error!', 'There was a problem deleting the client.', 'error');
                    console.error('Error deleting client:', errors);
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Clients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between">
                <div class="flex items-center gap-2">
                    <Button as-child size="sm" class="bg-indigo-500 text-white hover:bg-indigo-700">
                        <Link href="/clients/create"> <CirclePlus class="mr-1" /> Create </Link>
                    </Button>

                    <Button as-child size="sm" class="bg-green-500 text-white hover:bg-green-700">
                        <Link href="/clients/export" target="_blank"> <FolderInput class="mr-1" /> Export Excel </Link>
                    </Button>

                    <Button size="sm" class="bg-blue-500 text-white hover:bg-blue-700" @click="openImportModal">
                        <FolderOutput class="mr-1" /> Import Excel
                    </Button>
                </div>

                <div class="flex gap-2">
                    <div class="relative">
                        <Search class="absolute left-2 top-2 h-4 w-4 text-gray-500" />
                        <Input v-model="searchTerm" placeholder="Search clients..." class="w-64 pl-8" :disabled="isLoading" />
                    </div>
                </div>
            </div>

            <!-- Modified table container with flex layout -->
            <div class="relative flex flex-1 flex-col rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-black/50">
                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent"></div>
                </div>

                <!-- Table wrapper takes most space but not all -->
                <div class="flex-grow overflow-auto">
                    <Table>
                        <TableCaption>Client List (Total: {{ totalItems }})</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="cursor-pointer" @click="sortBy('name')"> Name {{ getSortIcon('name') }} </TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('email')"> Email {{ getSortIcon('email') }} </TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('company')"> Company {{ getSortIcon('company') }} </TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('industry')"> Industry {{ getSortIcon('industry') }} </TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('active')"> Active {{ getSortIcon('active') }} </TableHead>
                                <TableHead class="text-center">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="client in paginatedClients" :key="client.id">
                                <TableCell class="font-medium">{{ client.name }}</TableCell>
                                <TableCell>{{ client.email }}</TableCell>
                                <TableCell>{{ client.company_name }}</TableCell>
                                <TableCell>{{ client.metadata?.industri ?? 'N/A' }}</TableCell>
                                <TableCell>
                                    <span
                                        :class="
                                            client.is_active
                                                ? 'rounded-full bg-blue-500 px-3 py-1 text-white'
                                                : 'rounded-full bg-red-500 px-3 py-1 text-white'
                                        "
                                    >
                                        {{ client.is_active ? 'Active' : 'Non-Active' }}
                                    </span>
                                </TableCell>

                                <TableCell class="flex justify-center gap-2">
                                    <Button as-child size="sm" class="bg-blue-500 text-white hover:bg-blue-700">
                                        <Link :href="`/clients/${client.id}/edit`">
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button size="sm" class="bg-rose-500 text-white hover:bg-rose-700" @click="deleteClient(client.id)">
                                        <Trash />
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="paginatedClients.length === 0">
                                <TableCell colspan="6" class="py-4 text-center">No clients found</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination controls always at bottom -->
                <div class="mt-auto flex items-center justify-between border-t p-4">
                    <div class="flex gap-2">
                        <!-- Basic select dropdown replacement -->
                        <select
                            :value="perPage"
                            @change="handlePerPageChange"
                            class="rounded border border-gray-300 bg-white px-3 py-1 text-sm dark:border-gray-700 dark:bg-gray-800"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        Showing {{ paginatedClients.length ? (currentPage - 1) * perPage + 1 : 0 }} to
                        {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} entries
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">
                            <ChevronLeft class="h-4 w-4" />
                        </Button>

                        <div class="flex gap-1">
                            <Button
                                v-for="page in totalPages <= 5 ? totalPages : 5"
                                :key="page"
                                variant="outline"
                                size="sm"
                                :class="{ 'bg-indigo-500 text-white': page === currentPage }"
                                @click="goToPage(page)"
                            >
                                {{ page }}
                            </Button>

                            <span v-if="totalPages > 5" class="flex items-center px-2">...</span>

                            <Button
                                v-if="totalPages > 5"
                                variant="outline"
                                size="sm"
                                :class="{ 'bg-indigo-500 text-white': totalPages === currentPage }"
                                @click="goToPage(totalPages)"
                            >
                                {{ totalPages }}
                            </Button>
                        </div>

                        <Button variant="outline" size="sm" :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Import Excel Modal -->
    <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-bold">Import Clients</h2>
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
                                <li>Company Name</li>
                                <li>NPWP</li>
                                <li>Bidang (Industry)</li>
                                <li>Status (Active/Non-Active)</li>
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
