<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ActivityLog {
    id: string;
    type: 'Employee' | 'Client' | 'Project' | 'Assignment' | 'User';
    action_type: 'Create' | 'Update' | 'Delete';
    user_id: number;
    log: Record<string, any>;
    created_at: string;
}

interface ActivityPageProps extends SharedData {
    activity: ActivityLog[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Activity Logs', href: '/activity-logs' }];
const page = usePage();
const props = page.props as unknown as ActivityPageProps;
const allLogs = computed(() => props.activity);

const searchTerm = ref('');
const sortField = ref('created_at');
const sortDirection = ref<'asc' | 'desc'>('desc');
const currentPage = ref(1);
const perPage = ref(10);

const filteredLogs = computed(() => {
    let data = [...allLogs.value];

    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        data = data.filter((log) => [log.type, log.action_type, JSON.stringify(log.log)].some((v) => v.toLowerCase().includes(term)));
    }

    data.sort((a, b) => {
        const valA = a[sortField.value as keyof ActivityLog];
        const valB = b[sortField.value as keyof ActivityLog];
        return sortDirection.value === 'asc' ? String(valA).localeCompare(String(valB)) : String(valB).localeCompare(String(valA));
    });

    return data;
});

const paginatedLogs = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredLogs.value.slice(start, start + perPage.value);
});

const totalPages = computed(() => Math.ceil(filteredLogs.value.length / perPage.value));

const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page;
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
    return sortField.value === field ? (sortDirection.value === 'asc' ? '↑' : '↓') : '';
};
</script>

<template>
    <Head title="Activity Logs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-6">
            <div class="flex justify-between">
                <h1 class="text-2xl font-bold">Activity Logs</h1>
                <div class="relative">
                    <Search class="absolute left-2 top-2 h-4 w-4 text-gray-500" />
                    <Input v-model="searchTerm" class="pl-8" placeholder="Search activity..." />
                </div>
            </div>

            <div class="overflow-auto rounded-xl border">
                <Table>
                    <TableCaption>Total: {{ filteredLogs.length }}</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="cursor-pointer" @click="sortBy('created_at')">Created At {{ getSortIcon('created_at') }}</TableHead>
                            <TableHead class="cursor-pointer" @click="sortBy('type')">Note {{ getSortIcon('type') }}</TableHead>
                            <TableHead class="cursor-pointer" @click="sortBy('action_type')">Action {{ getSortIcon('action_type') }}</TableHead>
                            <TableHead>User</TableHead>
                            <!-- <TableHead>Log</TableHead> -->
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="log in paginatedLogs" :key="log.id">
                            <TableCell>{{ new Date(log.created_at).toLocaleString() }}</TableCell>
                            <TableCell>{{ log.action_type }} {{ log.type }}</TableCell>
                            <TableCell>{{ log.action_type }}</TableCell>
                            <TableCell>{{ log.user?.name }}</TableCell>
                            <!-- <TableCell class="whitespace-pre-wrap break-words text-xs">
                                <code>{{ JSON.stringify(log.log, null, 2) }}</code>
                            </TableCell> -->
                        </TableRow>
                        <TableRow v-if="paginatedLogs.length === 0">
                            <TableCell colspan="5" class="py-4 text-center">No activity found</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between">
                <div class="text-sm">
                    Showing
                    {{ paginatedLogs.length ? (currentPage - 1) * perPage + 1 : 0 }} to {{ Math.min(currentPage * perPage, filteredLogs.length) }} of
                    {{ filteredLogs.length }}
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
    </AppLayout>
</template>
