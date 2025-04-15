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
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    CirclePlus,
    FolderInput,
    FolderOutput,
    Pencil,
    PlusCircle,
    Search,
    Trash,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref } from 'vue';

interface Project {
    id: string;
    project_id: string;
    project_name: string;
    start_date: string;
    is_approved: boolean;
    settings: string; // This is a JSON string
}

interface Assignment {
    id: string;
    project_id: string;
    employee_id: string;
    asssignment_id: string;
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
    assignments_id: '',
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
    assignmentForm.assignments_id = assignment.assignments_id;
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
    assignmentForm.assignments_id = '';
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
const sortField = ref<'project_name' | 'project_id' | 'start_date' | 'lokasi' | 'is_approved'>('project_name');
const sortDirection = ref<'asc' | 'desc'>('asc');

const isLoading = ref(false);

const showImportModal = ref(false);
const importFile = ref<File | null>(null);
const isImporting = ref(false);
const importErrors = ref<string[]>([]);

const showImportAssignmentModal = ref(false);
const assignmentImportFile = ref<File | null>(null);
const importAssignmentErrors = ref<string[]>([]);
const isImportingAssignments = ref(false);
const currentProjectName = ref('');

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
    router.post('/projects/import', formData, {
        onSuccess: () => {
            isImporting.value = false;
            closeImportModal();
            Swal.fire({
                title: 'Success!',
                text: 'Projects imported successfully',
                icon: 'success',
                confirmButtonColor: '#6366f1',
            });
            // Refresh the page to show updated projects
            router.visit('/projects', { replace: true });
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

const openImportAssignmentModal = (projectId: string, projectName: string) => {
    currentProjectId.value = projectId;
    currentProjectName.value = projectName;
    showImportAssignmentModal.value = true;
    assignmentImportFile.value = null;
    importAssignmentErrors.value = [];
};

const closeImportAssignmentModal = () => {
    showImportAssignmentModal.value = false;
    assignmentImportFile.value = null;
    importAssignmentErrors.value = [];
};

const handleAssignmentFileInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        assignmentImportFile.value = input.files[0];
    } else {
        assignmentImportFile.value = null;
    }
};

const importAssignments = () => {
    if (!assignmentImportFile.value || !currentProjectId.value) {
        Swal.fire({
            title: 'Error!',
            text: 'Please select a file to import',
            icon: 'error',
            confirmButtonColor: '#ef4444',
        });
        return;
    }

    isImportingAssignments.value = true;
    importAssignmentErrors.value = [];

    // Create form data
    const formData = new FormData();
    formData.append('file', assignmentImportFile.value);
    formData.append('project_id', currentProjectId.value);

    // Send to server
    router.post('/assignments/import', formData, {
        onSuccess: () => {
            isImportingAssignments.value = false;
            closeImportAssignmentModal();
            Swal.fire({
                title: 'Success!',
                text: 'Assignments imported successfully',
                icon: 'success',
                confirmButtonColor: '#6366f1',
            });
            // Refresh the page to show updated assignments
            router.visit(window.location.pathname, { replace: true });
        },
        onError: (errors) => {
            isImportingAssignments.value = false;

            if (errors.file) {
                importAssignmentErrors.value = Array.isArray(errors.file) ? errors.file : [errors.file as string];
            } else if (errors.error) {
                importAssignmentErrors.value = [errors.error as string];
            } else {
                importAssignmentErrors.value = ['An unknown error occurred during import'];
            }

            // If there are parsing errors in the response
            if (errors.parsing_errors) {
                const parsingErrors = errors.parsing_errors as Record<string, string[]>;
                for (const row in parsingErrors) {
                    importAssignmentErrors.value.push(`Row ${row}: ${parsingErrors[row].join(', ')}`);
                }
            }
        },
    });
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
                        <Link href="/projects/export" target="_blank"> <FolderInput class="mr-1" /> Print </Link>
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
                        <TableCaption>Project List (Total: {{ totalItems }})</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-10"></TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('project_name')">
                                    Project {{ getSortIcon('project_name') }}
                                </TableHead>
                                <TableHead class="cursor-pointer" @click="sortBy('project_id')">
                                    Project ID {{ getSortIcon('project_id') }}
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
                                    <TableCell>{{ project.project_id }}</TableCell>
                                    <TableCell>{{ formatDate(project.start_date) }}</TableCell>
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
                                    <TableCell colspan="9" class="p-0">
                                        <div class="p-4">
                                            <!-- Find this section in the template and modify it -->
                                            <div class="mb-4 flex items-center justify-between">
                                                <h3 class="font-semibold">Project Assignments</h3>
                                                <div class="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        class="bg-indigo-500 text-white hover:bg-indigo-700"
                                                        @click="openCreateModal(project.id)"
                                                    >
                                                        <PlusCircle class="mr-1 h-4 w-4" />
                                                        Add Assignment
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        class="bg-blue-500 text-white hover:bg-blue-700"
                                                        @click="openImportAssignmentModal(project.id, project.project_name)"
                                                    >
                                                        <FolderOutput class="mr-1 h-4 w-4" />
                                                        Import
                                                    </Button>
                                                </div>
                                            </div>
                                            <div class="rounded-lg border">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Employee</TableHead>
                                                            <TableHead>Project ID</TableHead>
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
                                                            <TableCell>{{ assignment.assignments_id ?? 'Unknown ID' }}</TableCell>
                                                            <TableCell>{{ formatDate(assignment.start_date) }}</TableCell>
                                                            <TableCell>{{ formatDate(assignment.end_date) }}</TableCell>
                                                            <TableCell>{{ assignment.notes ?? '-' }}</TableCell>
                                                            <TableCell>
                                                                <a
                                                                    v-if="assignment.attachment"
                                                                    :href="`/storage/${assignment.attachment}`"
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
                                <TableCell colspan="9" class="py-4 text-center">No projects found</TableCell>
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
                            <Input id="attachment" type="file" accept=".pdf" @change="handleFileInput" />
                            <p v-if="formErrors['attachment']" class="mt-1 text-sm text-red-500">
                                {{ formErrors['attachment'] }}
                            </p>
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
                            <Input id="edit-attachment" type="file" accept=".pdf" @change="handleFileInput" />
                            <p class="mt-1 text-xs text-gray-500">Upload a new file to replace the existing attachment.</p>
                            <p v-if="formErrors['attachment']" class="mt-1 text-sm text-red-500">
                                {{ formErrors['attachment'] }}
                            </p>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isEditModalOpen = false"> Cancel </Button>
                    <Button type="button" @click="saveAssignment"> Update Assignment </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Add this alongside your other modals -->
        <Dialog :open="showImportAssignmentModal" @update:open="showImportAssignmentModal = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Import Assignments for {{ currentProjectName }}</DialogTitle>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Upload Excel file (.xlsx, .csv) with Assignment data. Make sure the file follows the required format.
                        <!-- <span class="font-medium">{{ currentProjectId }}</span> -->
                    </p>
                    <div class="flex items-center gap-2">
                        <Input id="assignment-import" type="file" accept=".xlsx,.csv,.xls" @change="handleAssignmentFileInput" />
                    </div>
                    <div v-if="importAssignmentErrors.length > 0" class="rounded-md bg-red-50 p-3 dark:bg-red-900/20">
                        <ul class="list-disc space-y-1 pl-5 text-sm text-red-700 dark:text-red-300">
                            <li v-for="(error, index) in importAssignmentErrors" :key="index">{{ error }}</li>
                        </ul>
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
                                    <li>Project Name</li>
                                    <li>Client Company</li>
                                    <li>Start Date</li>
                                    <li>Status</li>
                                    <li>Lokasi</li>
                                    <li>Cost</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeImportAssignmentModal">Cancel</Button>
                    <Button type="button" @click="importAssignments" :disabled="isImportingAssignments || !assignmentImportFile">
                        <span
                            v-if="isImportingAssignments"
                            class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        ></span>
                        {{ isImportingAssignments ? 'Importing...' : 'Import' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Import Excel Modal -->
        <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-bold">Import Project</h2>
                    <button @click="closeImportModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        &times;
                    </button>
                </div>

                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                    Upload Excel file (.xlsx, .csv) with project data. Make sure the file follows the required format.
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
                                    <li>Employee Name</li>
                                    <li>Start Date</li>
                                    <li>End Date</li>
                                    <li>Notes</li>
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
    </AppLayout>
</template>
