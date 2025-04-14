<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
// import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, ChevronLeft, ChevronRight, ChevronUp, CirclePlus, Pencil, PlusCircle, Printer, Search, Trash } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref } from 'vue';

interface Project {
    id: string;
    project_name: string;
    start_date: string;
    is_approved: boolean;
    settings: string; // This is a JSON string
}

interface Assignment {
    id: string;
    project_id: string;
    employee_id: string;
    start_date: string;
    end_date: string | null;
    attachment: string | null;
    notes: string | null;
    employee?: {
        id: string;
        name: string;
    };
}

interface Employee {
    id: string;
    name: string;
}

interface ParsedProject extends Omit<Project, 'settings'> {
    settings: {
        lokasi?: string;
        cost?: string;
        employee_count?: string;
        start_date?: string;
        end_date?: string;
    };
}

interface ProjectPageProps extends SharedData {
    projects: Project[];
    assignments?: Assignment[];
    employees?: Employee[];
    total?: number;
    per_page?: number;
    current_page?: number;
    last_page?: number;
}

const page = usePage();
const props = page.props as unknown as ProjectPageProps;

// Modal state
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const currentProjectId = ref<string | null>(null);

// Form data for create/edit
const assignmentForm = reactive({
    id: '',
    project_id: '',
    employee_id: '',
    start_date: '',
    end_date: '',
    notes: '',
    attachment: null as File | null,
    _method: 'POST',
});

// Form errors
const formErrors = ref<Record<string, string>>({});

// Parse the settings JSON for each project
const allProjects = computed(() => {
    return props.projects.map((project) => {
        // Create a new object with parsed settings
        const parsedProject: ParsedProject = {
            ...project,
            settings: typeof project.settings === 'string' ? JSON.parse(project.settings) : project.settings,
        };
        return parsedProject;
    });
});

// Track expanded projects
const expandedProjects = ref<Record<string, boolean>>({});

// Toggle expanded state for a project
const toggleProjectExpansion = (projectId: string) => {
    expandedProjects.value[projectId] = !expandedProjects.value[projectId];
};

// Get assignments for a specific project
const getProjectAssignments = (projectId: string) => {
    if (!props.assignments || !Array.isArray(props.assignments)) {
        return [];
    }
    return props.assignments.filter((assignment) => assignment.project_id === projectId);
};

// Check if a project has any assignments
const hasAssignments = (projectId: string) => {
    if (!props.assignments || !Array.isArray(props.assignments)) {
        return false;
    }
    return props.assignments.some((assignment) => assignment.project_id === projectId);
};

// Open create assignment modal
const openCreateModal = (projectId: string) => {
    currentProjectId.value = projectId;
    resetForm();
    assignmentForm.project_id = projectId;
    isCreateModalOpen.value = true;
};

// Open edit assignment modal
const openEditModal = (assignment: Assignment) => {
    resetForm();
    currentProjectId.value = assignment.project_id;
    assignmentForm.id = assignment.id;
    assignmentForm.project_id = assignment.project_id;
    assignmentForm.employee_id = assignment.employee_id;
    assignmentForm.start_date = formatDateForInput(assignment.start_date);
    assignmentForm.end_date = formatDateForInput(assignment.end_date);
    assignmentForm.notes = assignment.notes || '';
    assignmentForm._method = 'PUT';
    isEditModalOpen.value = true;
};

// Reset form
const resetForm = () => {
    assignmentForm.id = '';
    assignmentForm.project_id = '';
    assignmentForm.employee_id = '';
    assignmentForm.start_date = '';
    assignmentForm.end_date = '';
    assignmentForm.notes = '';
    assignmentForm.attachment = null;
    assignmentForm._method = 'POST';
    formErrors.value = {};
};

// Format date for input fields (YYYY-MM-DD)
const formatDateForInput = (dateString: string | null) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().split('T')[0];
};

// Create or update assignment
const saveAssignment = () => {
    const formData = new FormData();
    Object.entries(assignmentForm).forEach(([key, value]) => {
        if (value !== null && value !== undefined) {
            formData.append(key, value instanceof File ? value : String(value));
        }
    });

    // Handle file upload if present
    if (assignmentForm.attachment) {
        formData.append('attachment', assignmentForm.attachment);
    }

    const url = assignmentForm._method === 'PUT' ? `/assignments/${assignmentForm.id}` : '/assignments';

    router.post(url, formData, {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            isEditModalOpen.value = false;
            showSuccessMessage(assignmentForm._method === 'PUT' ? 'Assignment updated successfully.' : 'Assignment created successfully.');
        },
        onError: (errors) => {
            formErrors.value = errors;
        },
        preserveScroll: true,
    });
};

// Delete assignment
const deleteAssignment = (id: string) => {
    Swal.fire({
        title: 'Delete Assignment?',
        text: 'This assignment will be permanently removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/assignments/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    showSuccessMessage('Assignment has been removed.');
                },
            });
        }
    });
};

// Handle file input
const handleFileInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        assignmentForm.attachment = input.files[0];
    }
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Projects', href: '/projects' }];

const searchTerm = ref('');
const currentPage = ref(props.current_page || 1);
const perPage = ref(props.per_page || 10);
const sortField = ref<'project_name' | 'start_date' | 'lokasi' | 'is_approved'>('project_name');
const sortDirection = ref<'asc' | 'desc'>('asc');

const isLoading = ref(false);

const showSuccessMessage = (message: string) => {
    Swal.fire({
        title: 'Success!',
        text: message,
        icon: 'success',
        confirmButtonColor: '#6366f1',
        timer: 3000,
    });
};

onMounted(() => {
    const flash = page.props.flash as { success?: string; error?: string };
    if (flash?.success) showSuccessMessage(flash.success);
});

const filteredProjects = computed(() => {
    let filtered = [...allProjects.value];

    if (searchTerm.value) {
        const search = searchTerm.value.toLowerCase();
        filtered = filtered.filter(
            (p) => p.project_name.toLowerCase().includes(search) || (p.settings?.lokasi?.toLowerCase() ?? '').includes(search),
        );
    }

    filtered.sort((a, b) => {
        const getVal = (project: ParsedProject, field: string) => {
            if (field === 'lokasi') return project.settings?.lokasi?.toLowerCase() || '';
            if (field === 'is_approved') return project.is_approved ? '1' : '0';
            return (project as any)[field]?.toLowerCase?.() ?? '';
        };

        const valA = getVal(a, sortField.value);
        const valB = getVal(b, sortField.value);

        return sortDirection.value === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
    });

    return filtered;
});

const paginatedProjects = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredProjects.value.slice(start, start + perPage.value);
});

const totalPages = computed(() => Math.ceil(filteredProjects.value.length / perPage.value));
const totalItems = computed(() => filteredProjects.value.length);

const goToPage = (page: number) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
};

const handlePerPageChange = (e: Event) => {
    perPage.value = parseInt((e.target as HTMLSelectElement).value);
    currentPage.value = 1;
};

const sortBy = (field: typeof sortField.value) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const getSortIcon = (field: typeof sortField.value) => {
    if (sortField.value !== field) return '';
    return sortDirection.value === 'asc' ? '↑' : '↓';
};

const deleteProject = (id: string) => {
    Swal.fire({
        title: 'Delete Project?',
        text: 'Project will be permanently removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/projects/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Project has been removed.', 'success');
                    router.visit('/projects', { replace: true });
                },
            });
        }
    });
};

// Format date for display
const formatDate = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
};

// Format currency for display
const formatCurrency = (value: string | undefined) => {
    if (!value) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(value));
};
</script>

<template>
    <Head title="Projects" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Button as-child size="sm" class="bg-indigo-500 text-white hover:bg-indigo-700">
                        <Link href="/projects/create"> <CirclePlus class="mr-1" /> Create </Link>
                    </Button>
                    <Button as-child size="sm" class="bg-green-500 text-white hover:bg-green-700">
                        <Link href="/projects/export" target="_blank"> <Printer class="mr-1" /> Print </Link>
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
                        <TableCaption>Project List (Total: {{ totalItems }})</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-10"></TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('project_name')">
                                    Project {{ getSortIcon('project_name') }}
                                </TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('start_date')"> Start {{ getSortIcon('start_date') }} </TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('lokasi')"> Location {{ getSortIcon('lokasi') }} </TableHead>
                                <TableHead class="cursor-pointer">Budget</TableHead>
                                <TableHead class="cursor-pointer">Employees</TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('is_approved')"> Status {{ getSortIcon('is_approved') }} </TableHead>
                                <TableHead class="text-center">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-for="project in paginatedProjects" :key="project.id">
                                <TableRow class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <TableCell class="w-10">
                                        <Button variant="ghost" size="sm" @click="toggleProjectExpansion(project.id)">
                                            <ChevronDown v-if="!expandedProjects[project.id]" class="h-5 w-5" />
                                            <ChevronUp v-else class="h-5 w-5" />
                                        </Button>
                                    </TableCell>
                                    <TableCell class="font-medium">{{ project.project_name }}</TableCell>
                                    <TableCell>{{ project.start_date }}</TableCell>
                                    <TableCell>{{ project.settings?.lokasi ?? '-' }}</TableCell>
                                    <TableCell>{{ formatCurrency(project.settings?.cost) }}</TableCell>
                                    <TableCell>{{ project.settings?.employee_count ?? '0' }}</TableCell>
                                    <TableCell>
                                        <span
                                            :class="
                                                project.is_approved
                                                    ? 'rounded-full bg-green-500 px-3 py-1 text-white'
                                                    : 'rounded-full bg-yellow-500 px-3 py-1 text-white'
                                            "
                                        >
                                            {{ project.is_approved ? 'Approved' : 'Pending' }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="flex justify-center gap-2">
                                        <Button as-child size="sm" class="bg-blue-500 text-white hover:bg-blue-700">
                                            <Link :href="`/projects/${project.id}/edit`"><Pencil /></Link>
                                        </Button>
                                        <Button size="sm" class="bg-rose-500 text-white hover:bg-rose-700" @click="deleteProject(project.id)">
                                            <Trash />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <!-- Assignments dropdown section -->
                                <TableRow v-if="expandedProjects[project.id]" class="bg-gray-50 dark:bg-gray-900">
                                    <TableCell colspan="8" class="p-0">
                                        <div class="p-4">
                                            <div class="mb-4 flex items-center justify-between">
                                                <h3 class="font-semibold">Project Assignments</h3>
                                                <Button
                                                    size="sm"
                                                    class="bg-indigo-500 text-white hover:bg-indigo-700"
                                                    @click="openCreateModal(project.id)"
                                                >
                                                    <PlusCircle class="mr-1 h-4 w-4" />
                                                    Add Assignment
                                                </Button>
                                            </div>
                                            <div class="rounded-lg border">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Employee</TableHead>
                                                            <TableHead>Start Date</TableHead>
                                                            <TableHead>End Date</TableHead>
                                                            <TableHead>Notes</TableHead>
                                                            <TableHead>Attachment</TableHead>
                                                            <TableHead class="text-center">Actions</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        <TableRow v-for="assignment in getProjectAssignments(project.id)" :key="assignment.id">
                                                            <TableCell>{{ assignment.employee?.name ?? 'Unknown Employee' }}</TableCell>
                                                            <TableCell>{{ formatDate(assignment.start_date) }}</TableCell>
                                                            <TableCell>{{ formatDate(assignment.end_date) }}</TableCell>
                                                            <TableCell>{{ assignment.notes ?? '-' }}</TableCell>
                                                            <TableCell>
                                                                <a
                                                                    v-if="assignment.attachment"
                                                                    :href="assignment.attachment"
                                                                    target="_blank"
                                                                    class="text-indigo-500 hover:underline"
                                                                >
                                                                    View Attachment
                                                                </a>
                                                                <span v-else>-</span>
                                                            </TableCell>
                                                            <TableCell class="flex justify-center gap-2">
                                                                <Button
                                                                    size="sm"
                                                                    class="bg-blue-500 text-white hover:bg-blue-700"
                                                                    @click="openEditModal(assignment)"
                                                                >
                                                                    <Pencil class="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    size="sm"
                                                                    class="bg-rose-500 text-white hover:bg-rose-700"
                                                                    @click="deleteAssignment(assignment.id)"
                                                                >
                                                                    <Trash class="h-4 w-4" />
                                                                </Button>
                                                            </TableCell>
                                                        </TableRow>
                                                        <TableRow v-if="getProjectAssignments(project.id).length === 0">
                                                            <TableCell colspan="6" class="py-4 text-center text-gray-500">
                                                                No assignments found for this project
                                                            </TableCell>
                                                        </TableRow>
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </template>
                            <TableRow v-if="paginatedProjects.length === 0">
                                <TableCell colspan="8" class="py-4 text-center">No projects found</TableCell>
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
                        Showing {{ paginatedProjects.length ? (currentPage - 1) * perPage + 1 : 0 }} to
                        {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }}
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

        <!-- Create Assignment Modal -->
        <Dialog :open="isCreateModalOpen" @update:open="isCreateModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add Assignment</DialogTitle>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="employee" class="text-right">Employee</Label>
                        <div class="col-span-3">
                            <select
                                id="employee"
                                v-model="assignmentForm.employee_id"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800"
                                required
                            >
                                <option value="" disabled>Select an employee</option>
                                <option v-for="employee in props.employees" :key="employee.id" :value="employee.id">
                                    {{ employee.name }}
                                </option>
                            </select>
                            <p v-if="formErrors['employee_id']" class="mt-1 text-sm text-red-500">{{ formErrors['employee_id'] }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="start_date" class="text-right">Start Date</Label>
                        <div class="col-span-3">
                            <Input id="start_date" type="date" v-model="assignmentForm.start_date" required />
                            <p v-if="formErrors['start_date']" class="mt-1 text-sm text-red-500">{{ formErrors['start_date'] }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="end_date" class="text-right">End Date</Label>
                        <div class="col-span-3">
                            <Input id="end_date" type="date" v-model="assignmentForm.end_date" />
                            <p v-if="formErrors['end_date']" class="mt-1 text-sm text-red-500">{{ formErrors['end_date'] }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="notes" class="text-right">Notes</Label>
                        <div class="col-span-3">
                            <textarea
                                v-model="assignmentForm.notes"
                                rows="4"
                                class="w-full rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                                placeholder="Keterangan atau catatan tambahan..."
                            ></textarea>
                            <p v-if="formErrors['notes']" class="mt-1 text-sm text-red-500">{{ formErrors['notes'] }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="attachment" class="text-right">Attachment</Label>
                        <div class="col-span-3">
                            <Input id="attachment" type="file" @change="handleFileInput" />
                            <p v-if="formErrors['attachment']" class="mt-1 text-sm text-red-500">{{ formErrors['attachment'] }}</p>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isCreateModalOpen = false"> Cancel </Button>
                    <Button type="button" @click="saveAssignment"> Save Assignment </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Assignment Modal -->
        <Dialog :open="isEditModalOpen" @update:open="isEditModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit Assignment</DialogTitle>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit-employee" class="text-right">Employee</Label>
                        <div class="col-span-3">
                            <select
                                id="edit-employee"
                                v-model="assignmentForm.employee_id"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800"
                                required
                            >
                                <option value="" disabled>Select an employee</option>
                                <option v-for="employee in props.employees" :key="employee.id" :value="employee.id">
                                    {{ employee.name }}
                                </option>
                            </select>
                            <p v-if="formErrors['employee_id']" class="mt-1 text-sm text-red-500">{{ formErrors['employee_id'] }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit-start_date" class="text-right">Start Date</Label>
                        <div class="col-span-3">
                            <Input id="edit-start_date" type="date" v-model="assignmentForm.start_date" required />
                            <p v-if="formErrors['start_date']" class="mt-1 text-sm text-red-500">{{ formErrors['start_date'] }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit-end_date" class="text-right">End Date</Label>
                        <div class="col-span-3">
                            <Input id="edit-end_date" type="date" v-model="assignmentForm.end_date" />
                            <p v-if="formErrors['end_date']" class="mt-1 text-sm text-red-500">{{ formErrors['end_date'] }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit-notes" class="text-right">Notes</Label>
                        <div class="col-span-3">
                            <textarea
                                v-model="assignmentForm.notes"
                                rows="4"
                                class="w-full rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                                placeholder="Keterangan atau catatan tambahan..."
                            ></textarea>
                            <p v-if="formErrors['notes']" class="mt-1 text-sm text-red-500">{{ formErrors['notes'] }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit-attachment" class="text-right">Attachment</Label>
                        <div class="col-span-3">
                            <Input id="edit-attachment" type="file" @change="handleFileInput" />
                            <p class="mt-1 text-xs text-gray-500">Upload a new file to replace the existing attachment.</p>
                            <p v-if="formErrors['attachment']" class="mt-1 text-sm text-red-500">{{ formErrors['attachment'] }}</p>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isEditModalOpen = false"> Cancel </Button>
                    <Button type="button" @click="saveAssignment"> Update Assignment </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
