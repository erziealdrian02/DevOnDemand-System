<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, CirclePlus, Pencil, Printer, Search, Trash } from 'lucide-vue-next';
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
                        <Link href="/employeesSec/print" target="_blank"> <Printer class="mr-1" /> Print </Link>
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
</template>
