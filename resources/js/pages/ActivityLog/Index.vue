<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
// import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, FilterX, Search, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref, watch } from 'vue';

interface ActivityLog {
    id: string;
    type: 'Employee' | 'Client' | 'Project' | 'Assignment' | 'User';
    action_type: 'Create' | 'Update' | 'Delete' | 'Import' | 'Export';
    user_id: number;
    user?: {
        id: number;
        name: string;
    };
    log: Record<string, any>;
    created_at: string;
    updated_at: string;
}

interface ActivityPageProps extends SharedData {
    activity: ActivityLog[];
    total?: number;
    per_page?: number;
    current_page?: number;
    last_page?: number;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Activity Logs', href: '/activity-logs' }];
const page = usePage();
const props = page.props as unknown as ActivityPageProps;
const allLogs = computed(() => props.activity);

// Search and filter functionality
const searchTerm = ref('');
const currentPage = ref(props.current_page || 1);
const perPage = ref(props.per_page || 10);
const sortField = ref('created_at');
const sortDirection = ref<'asc' | 'desc'>('desc');
const isLoading = ref(false);

// Filter state
const showFilters = ref(false);
const typeFilters = ref<Record<string, boolean>>({});
const actionFilters = ref<Record<string, boolean>>({});

// Modal state
const showModal = ref(false);
const selectedLog = ref<Record<string, any> | null>(null);
const selectedLogType = ref<string>('');
const relatedLogs = ref<ActivityLog[]>([]);
const activeTab = ref('details');
const entityId = ref('');

// Function to format date strings
const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString();
};

// Function to check if a value is an object or array
const isObjectOrArray = (val: any): boolean => {
    return typeof val === 'object' && val !== null;
};

// Function to check if a string is a JSON string
const isJsonString = (str: string): boolean => {
    try {
        JSON.parse(str);
        return true;
    } catch (e) {
        return false;
    }
};

// Function to parse JSON safely
const parseJsonSafely = (jsonString: string): any => {
    try {
        return JSON.parse(jsonString);
    } catch (e) {
        return jsonString;
    }
};

// Function to get the badge color for action type
const getActionBadgeClass = (actionType: string) => {
    if (actionType === 'Create') return 'bg-green-500';
    if (actionType === 'Update') return 'bg-blue-500';
    if (actionType === 'Delete') return 'bg-red-500';
    if (actionType === 'Import') return 'bg-yellow-500';
    if (actionType === 'Export') return 'bg-purple-500';
    return 'bg-gray-500';
};

// Function to handle opening the log details modal
const openLogModal = (log: ActivityLog) => {
    // Parse any JSON strings in the log object
    const processedLog = { ...log.log };

    // Process each property to parse JSON strings if needed
    for (const key in processedLog) {
        if (typeof processedLog[key] === 'string' && isJsonString(processedLog[key])) {
            processedLog[key] = parseJsonSafely(processedLog[key]);
        }
    }

    selectedLog.value = processedLog;
    selectedLogType.value = log.type;
    showModal.value = true;
    activeTab.value = 'details';

    // Find entity ID from the log
    if (processedLog.id) {
        entityId.value = processedLog.id;

        // Find all related logs with the same entity ID
        relatedLogs.value = allLogs.value
            .filter((item) => {
                const itemLog = item.log;
                return itemLog && itemLog.id === entityId.value;
            })
            .sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());
    } else {
        relatedLogs.value = [];
    }
};

// Function to close the modal
const closeModal = () => {
    showModal.value = false;
    selectedLog.value = null;
    relatedLogs.value = [];
};

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

    // Initialize filter objects with all options
    if (allLogs.value && allLogs.value.length > 0) {
        // Get unique types
        const types = new Set<string>(allLogs.value.map((log) => log.type));
        types.forEach((type) => {
            typeFilters.value[type] = false;
        });

        // Get unique action types
        const actions = new Set<string>(allLogs.value.map((log) => log.action_type));
        actions.forEach((action) => {
            actionFilters.value[action] = false;
        });
    }
});

// Listen for Inertia page updates to catch flash messages during navigation
router.on('success', (event) => {
    const flashMessage = (event.detail.page.props.flash as { success?: string })?.success;
    if (flashMessage) {
        showSuccessMessage(flashMessage);
    }
});

// Get unique values for filter options
const typeOptions = computed(() => {
    const types = new Set<string>(allLogs.value.map((log) => log.type));
    return Array.from(types);
});

const actionOptions = computed(() => {
    const actions = new Set<string>(allLogs.value.map((log) => log.action_type));
    return Array.from(actions);
});

const resetFilters = () => {
    // Reset all checkbox filters
    for (const key in typeFilters.value) {
        typeFilters.value[key] = false;
    }
    for (const key in actionFilters.value) {
        actionFilters.value[key] = false;
    }
    searchTerm.value = '';
    currentPage.value = 1;

    if (props.total !== undefined) {
        fetchData();
    }
};

const filteredLogs = computed(() => {
    if (!allLogs.value) return [];

    // If server-side pagination is implemented, return all logs
    if (props.total !== undefined) return allLogs.value;

    // Client-side filtering
    let filtered = [...allLogs.value];

    // Apply text search
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        filtered = filtered.filter((log) => {
            const userName = log.user?.name || '';
            return [log.type, log.action_type, userName, JSON.stringify(log.log)].some((v) => String(v).toLowerCase().includes(term));
        });
    }

    // Apply type filters (if any are selected)
    const selectedTypes = Object.keys(typeFilters.value).filter((key) => typeFilters.value[key]);
    if (selectedTypes.length > 0) {
        filtered = filtered.filter((log) => selectedTypes.includes(log.type));
    }

    // Apply action filters (if any are selected)
    const selectedActions = Object.keys(actionFilters.value).filter((key) => actionFilters.value[key]);
    if (selectedActions.length > 0) {
        filtered = filtered.filter((log) => selectedActions.includes(log.action_type));
    }

    // Apply sorting
    filtered.sort((a, b) => {
        let valA, valB;

        if (sortField.value === 'created_at') {
            valA = new Date(a.created_at).getTime();
            valB = new Date(b.created_at).getTime();
            return sortDirection.value === 'asc' ? valA - valB : valB - valA;
        } else if (sortField.value === 'user') {
            valA = (a.user?.name || '').toLowerCase();
            valB = (b.user?.name || '').toLowerCase();
        } else {
            valA = String(a[sortField.value as keyof ActivityLog]).toLowerCase();
            valB = String(b[sortField.value as keyof ActivityLog]).toLowerCase();
        }

        return sortDirection.value === 'asc' ? String(valA).localeCompare(String(valB)) : String(valB).localeCompare(String(valA));
    });

    return filtered;
});

// Client-side pagination computed properties
const paginatedLogs = computed(() => {
    if (props.total !== undefined) return filteredLogs.value; // If server-side pagination

    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredLogs.value.slice(start, end);
});

const totalPages = computed(() => {
    if (props.last_page) return props.last_page;
    return Math.ceil(filteredLogs.value.length / perPage.value);
});

const totalItems = computed(() => {
    if (props.total !== undefined) return props.total;
    return filteredLogs.value.length;
});

// Function to view a specific related log
const viewRelatedLog = (log: ActivityLog) => {
    // Process the log data
    const processedLog = { ...log.log };

    // Process each property to parse JSON strings if needed
    for (const key in processedLog) {
        if (typeof processedLog[key] === 'string' && isJsonString(processedLog[key])) {
            processedLog[key] = parseJsonSafely(processedLog[key]);
        }
    }

    selectedLog.value = processedLog;
    selectedLogType.value = log.type;
    activeTab.value = 'details';
};

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

    // Prepare filter data
    const selectedTypes = Object.keys(typeFilters.value).filter((key) => typeFilters.value[key]);
    const selectedActions = Object.keys(actionFilters.value).filter((key) => actionFilters.value[key]);

    router.get(
        '/activity-logs',
        {
            page: currentPage.value,
            per_page: perPage.value,
            search: searchTerm.value,
            sort_field: sortField.value,
            sort_direction: sortDirection.value,
            types: selectedTypes.length > 0 ? selectedTypes : undefined,
            actions: selectedActions.length > 0 ? selectedActions : undefined,
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

// Watch for filter changes
watch(
    [typeFilters, actionFilters],
    () => {
        if (props.total !== undefined) {
            currentPage.value = 1;
            fetchData();
        }
    },
    { deep: true },
);

// Watch for flash messages on the page props
watch(
    () => page.props.flash,
    (newFlash) => {
        if (newFlash && (newFlash as { success?: string }).success) {
            showSuccessMessage((newFlash as { success: string }).success);
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

// Toggle filter panel
const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

// Check if any filters are active
const hasActiveFilters = computed(() => {
    return Object.values(typeFilters.value).some((val) => val) || Object.values(actionFilters.value).some((val) => val) || searchTerm.value !== '';
});

// Compute the related logs status text
const relatedLogsStatusText = computed(() => {
    if (relatedLogs.value.length <= 1) {
        return 'No related activity found for this item';
    }

    // Count operations by type
    const operations = relatedLogs.value.reduce(
        (acc, log) => {
            acc[log.action_type] = (acc[log.action_type] || 0) + 1;
            return acc;
        },
        {} as Record<string, number>,
    );

    const parts = [];
    if (operations.Create) parts.push(`${operations.Create} create`);
    if (operations.Update) parts.push(`${operations.Update} update`);
    if (operations.Import) parts.push(`${operations.Import} import`);
    if (operations.Export) parts.push(`${operations.Export} Export`);
    if (operations.Delete) parts.push(`${operations.Delete} delete`);

    return `Found ${relatedLogs.value.length} related activities (${parts.join(', ')})`;
});

const formatHeader = (key: string) => {
    // Ubah snake_case atau camelCase ke format lebih readable
    const words = key.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1));
    return words.join(' ');
};
</script>

<template>
    <Head title="Activity Logs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between">
                <h1 class="text-2xl font-bold">Activity Logs</h1>
                <div class="flex gap-2">
                    <Button variant="outline" class="h-10" @click="toggleFilters">
                        {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                    </Button>
                    <div class="relative">
                        <Search class="absolute left-2 top-2 h-4 w-4 text-gray-500" />
                        <Input v-model="searchTerm" class="w-64 pl-8" placeholder="Search activity..." :disabled="isLoading" />
                    </div>
                </div>
            </div>

            <!-- Filter section -->
            <div v-if="showFilters" class="rounded-lg border p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-medium">Filters</h3>
                    <Button v-if="hasActiveFilters" variant="outline" size="sm" @click="resetFilters">
                        <FilterX class="mr-1 h-4 w-4" />
                        Clear Filters
                    </Button>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Page Type Filters -->
                    <div>
                        <h4 class="mb-2 text-sm font-medium">Page Type</h4>
                        <div class="flex flex-col gap-2">
                            <div v-for="type in typeOptions" :key="type" class="flex items-center space-x-2">
                                <Checkbox :id="`type-${type}`" v-model:checked="typeFilters[type]" />
                                <label :for="`type-${type}`" class="text-sm">{{ type }}</label>
                            </div>
                        </div>
                    </div>

                    <!-- Action Type Filters -->
                    <div>
                        <h4 class="mb-2 text-sm font-medium">Action Type</h4>
                        <div class="flex flex-col gap-2">
                            <div v-for="action in actionOptions" :key="action" class="flex items-center space-x-2">
                                <Checkbox :id="`action-${action}`" v-model:checked="actionFilters[action]" />
                                <label :for="`action-${action}`" class="text-sm">{{ action }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table container with flex layout like Client page -->
            <div class="relative flex flex-1 flex-col rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <div v-if="isLoading" class="absolute inset-0 z-50 flex items-center justify-center bg-white/50 dark:bg-black/50">
                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent"></div>
                </div>

                <!-- Table wrapper takes most space but not all -->
                <div class="flex-grow overflow-auto">
                    <Table>
                        <TableCaption>Activity Logs (Total: {{ totalItems }})</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="cursor-pointer" @click="sortBy('created_at')">Created At {{ getSortIcon('created_at') }}</TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('type')">Page {{ getSortIcon('type') }}</TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('action_type')">Action {{ getSortIcon('action_type') }}</TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('user')">User {{ getSortIcon('user') }}</TableHead>
                                <TableHead>Log Details</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="log in paginatedLogs" :key="log.id">
                                <TableCell>{{ new Date(log.created_at).toLocaleString() }}</TableCell>
                                <TableCell>
                                    <span class="rounded-full bg-blue-500 px-3 py-1 text-white">{{ log.type }}</span>
                                </TableCell>
                                <TableCell>
                                    <span
                                        :class="{
                                            'rounded-full px-3 py-1 text-white': true,
                                            'bg-green-500': log.action_type === 'Create',
                                            'bg-blue-500': log.action_type === 'Update',
                                            'bg-yellow-600': log.action_type === 'Import',
                                            'bg-purple-500': log.action_type === 'Export',
                                            'bg-red-500': log.action_type === 'Delete',
                                        }"
                                        >{{ log.action_type }}</span
                                    >
                                </TableCell>
                                <TableCell>{{ log.user?.name || 'N/A' }}</TableCell>
                                <TableCell class="max-w-xs truncate">
                                    <Button size="sm" variant="outline" @click="openLogModal(log)"> View Details </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="paginatedLogs.length === 0">
                                <TableCell colspan="5" class="py-4 text-center">No activity found</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination controls always at bottom -->
                <div class="mt-auto flex items-center justify-between border-t p-4">
                    <div class="flex gap-2">
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
                        Showing {{ paginatedLogs.length ? (currentPage - 1) * perPage + 1 : 0 }} to
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

        <!-- Log Details Modal with Tabs -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4">
                <div class="relative w-full max-w-4xl rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                    <!-- Modal Header -->
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-xl font-semibold">{{ selectedLogType }} Log Details</h3>
                        <Button variant="ghost" size="sm" @click="closeModal">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Tabs -->
                    <div class="w-full">
                        <div class="mb-4 border-b">
                            <div class="flex">
                                <button
                                    @click="activeTab = 'details'"
                                    class="relative -mb-px px-4 py-2"
                                    :class="activeTab === 'details' ? 'border-b-2 border-indigo-500 font-medium' : 'text-gray-500'"
                                >
                                    Details
                                </button>
                                <button
                                    @click="activeTab = 'history'"
                                    class="relative -mb-px px-4 py-2"
                                    :class="[
                                        activeTab === 'history' ? 'border-b-2 border-indigo-500 font-medium' : 'text-gray-500',
                                        relatedLogs.length <= 1 ? 'cursor-not-allowed opacity-50' : '',
                                    ]"
                                    :disabled="relatedLogs.length <= 1"
                                >
                                    History
                                    <span v-if="relatedLogs.length > 1" class="ml-2 rounded-full bg-indigo-500 px-2 py-0.5 text-xs text-white">
                                        {{ relatedLogs.length }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Details Tab -->
                        <div v-if="activeTab === 'details'" class="max-h-[60vh] overflow-auto">
                            <div class="space-y-4">
                                <template v-for="(value, key) in selectedLog" :key="key">
                                    <!-- Skip fields that don't need to be shown -->
                                    <template v-if="!['id', 'created_at', 'updated_at', 'deleted_at'].includes(key)">
                                        <!-- Important fields as card headers -->
                                        <div
                                            v-if="['name', 'email', 'phone', 'project_name', 'company_name'].includes(key)"
                                            class="rounded-lg border p-4"
                                        >
                                            <h3 class="mb-2 text-lg font-semibold">{{ formatHeader(key) }}</h3>
                                            <p>{{ value }}</p>
                                        </div>

                                        <!-- Arrays (like skillsets) -->
                                        <div v-else-if="Array.isArray(value)" class="rounded-lg border p-4">
                                            <h3 class="mb-2 text-lg font-semibold">{{ formatHeader(key) }}</h3>
                                            <div class="space-y-2">
                                                <div
                                                    v-for="(item, index) in value"
                                                    :key="index"
                                                    class="rounded bg-gray-100 px-3 py-2 dark:bg-gray-700"
                                                >
                                                    {{ typeof item === 'object' ? JSON.stringify(item) : item }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Booleans -->
                                        <div v-else-if="typeof value === 'boolean'" class="rounded-lg border p-4">
                                            <h3 class="mb-2 text-lg font-semibold">{{ formatHeader(key) }}</h3>
                                            <p>{{ value ? 'Yes' : 'No' }}</p>
                                        </div>

                                        <!-- Objects (like metadata) -->
                                        <div v-else-if="isObjectOrArray(value)" class="rounded-lg border p-4">
                                            <h3 class="mb-2 text-lg font-semibold">{{ formatHeader(key) }}</h3>
                                            <div class="space-y-2">
                                                <div
                                                    v-for="(nestedValue, nestedKey) in value"
                                                    :key="nestedKey"
                                                    class="rounded bg-gray-100 px-3 py-2 dark:bg-gray-700"
                                                >
                                                    <strong>{{ formatHeader(nestedKey) }}:</strong>
                                                    <span v-if="nestedKey.includes('date') || nestedKey.includes('_at')">
                                                        {{ formatDate(nestedValue) }}
                                                    </span>
                                                    <span v-else-if="typeof nestedValue === 'boolean'">
                                                        {{ nestedValue ? 'Yes' : 'No' }}
                                                    </span>
                                                    <span v-else>{{ nestedValue }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Other regular fields -->
                                        <div v-else class="rounded-lg border p-4">
                                            <h3 class="mb-2 text-lg font-semibold">{{ formatHeader(key) }}</h3>
                                            <p>{{ value }}</p>
                                        </div>
                                    </template>
                                </template>
                            </div>
                        </div>

                        <!-- History Tab - Optimized -->
                        <div v-if="activeTab === 'history'" class="max-h-[60vh] overflow-auto">
                            <div class="mb-4 mt-2 rounded-md bg-gray-50 p-3 dark:bg-gray-700">
                                <div class="flex items-center text-sm">
                                    <div class="mr-2 h-4 w-4">
                                        <!-- Clock icon -->
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                    </div>
                                    {{ relatedLogsStatusText }}
                                </div>
                            </div>

                            <div v-if="relatedLogs.length > 1" class="space-y-4">
                                <div v-for="(log, index) in relatedLogs" :key="log.id" class="relative">
                                    <!-- Timeline connector -->
                                    <div
                                        v-if="index < relatedLogs.length - 1"
                                        class="absolute bottom-0 left-4 top-8 w-0.5 bg-gray-200 dark:bg-gray-600"
                                    ></div>

                                    <div class="flex gap-4">
                                        <!-- Action badge -->
                                        <div
                                            :class="`mt-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full ${getActionBadgeClass(log.action_type)}`"
                                        >
                                            <span class="text-xs font-bold text-white">
                                                {{ log.action_type.charAt(0) }}
                                            </span>
                                        </div>

                                        <!-- Log card -->
                                        <div class="flex-1 rounded-lg border bg-white p-4 shadow-sm dark:bg-gray-800">
                                            <div class="mb-2 flex items-center justify-between">
                                                <span :class="`rounded-full px-3 py-1 text-xs text-white ${getActionBadgeClass(log.action_type)}`">
                                                    {{ log.action_type }}
                                                </span>
                                                <span class="text-sm text-gray-500">{{ formatDate(log.created_at) }}</span>
                                            </div>

                                            <!-- Simplified log summary -->
                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">User</p>
                                                    <p class="text-sm">{{ log.user?.name || 'N/A' }}</p>
                                                </div>

                                                <div v-if="log.log && log.log.id">
                                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">ID</p>
                                                    <p class="truncate text-sm">{{ log.log.id }}</p>
                                                </div>
                                            </div>

                                            <!-- View details button -->
                                            <div class="mt-3 flex items-center justify-end">
                                                <Button size="sm" variant="outline" class="text-xs" @click="viewRelatedLog(log)">
                                                    View Details
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty state -->
                            <div v-else class="p-8 text-center text-gray-500">
                                <div class="mb-4 flex justify-center">
                                    <!-- Clock icon -->
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-8 w-8"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </div>
                                <p>No history available for this item.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
/* You can add any custom styling needed here */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Style for the timeline look */
.timeline-item {
    position: relative;
    padding-left: 2rem;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 2rem;
    bottom: -1rem;
    width: 2px;
    background-color: #e5e7eb;
}

.timeline-item:last-child::before {
    display: none;
}
</style>
