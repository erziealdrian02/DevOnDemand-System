<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, CirclePlus, Pencil, Printer, Search, Trash } from 'lucide-vue-next';
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
                        <Link href="/clients/print" target="_blank"> <Printer class="mr-1" /> Print </Link>
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
                                <TableCell colspan="5" class="py-4 text-center">No clients found</TableCell>
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
</template>
