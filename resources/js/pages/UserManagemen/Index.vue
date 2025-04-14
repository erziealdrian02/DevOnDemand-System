<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, CirclePlus, Pencil, Search, Trash, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    role?: {
        id: number;
        name: string;
    };
    role_id?: number;
}

interface Role {
    id: number;
    name: string;
}

interface PageProps {
    flash?: {
        success?: string;
        error?: string;
    };
    [key: string]: any;
}

interface UserPageProps extends PageProps {
    users: User[];
    roles: Role[];
    [key: string]: any;
}

const { props } = usePage<UserPageProps>();
const users = computed(() => props.users);
const roles = computed(() => props.roles);
const breadcrumbs = [{ title: 'Users', href: '/users' }];

const searchTerm = ref('');
const currentPage = ref(1);
const perPage = ref(10);
const sortField = ref<'name' | 'email' | 'email_verified_at' | 'role'>('name');
const sortDirection = ref<'asc' | 'desc'>('asc');

// Modal state
const createModalOpen = ref(false);
const editModalOpen = ref(false);
const formErrors = ref<Record<string, string>>({});

// Form data
const formData = ref<{
    id?: number;
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role_id: number | null;
    current_email?: string; // Tambahan untuk menyimpan email saat ini
}>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: null,
});

const resetForm = () => {
    formData.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role_id: null,
    };
    formErrors.value = {};
};

const openCreateModal = () => {
    resetForm();
    createModalOpen.value = true;
};

const openEditModal = (user: User) => {
    resetForm();
    formData.value = {
        id: user.id,
        name: user.name,
        email: user.email,
        current_email: user.email, // Simpan email saat ini
        password: '',
        password_confirmation: '',
        role_id: user.role_id || user.role?.id || null,
    };
    editModalOpen.value = true;
};

const closeModals = () => {
    createModalOpen.value = false;
    editModalOpen.value = false;
};

const createUser = () => {
    router.post('/userManagement', formData.value, {
        onSuccess: () => {
            closeModals();
            showSuccessMessage('User created successfully.', true);
        },
        onError: (errors) => {
            formErrors.value = errors;
        },
    });
};

const updateUser = () => {
    if (!formData.value.id) return;
    
    router.put(`/userManagement/${formData.value.id}`, formData.value, {
        onSuccess: () => {
            closeModals();
            showSuccessMessage('User updated successfully.', true);
        },
        onError: (errors) => {
            formErrors.value = errors;
        },
    });
};

const showSuccessMessage = (message: string, refreshAfter = false) => {
    Swal.fire({
        title: 'Success!',
        text: message,
        icon: 'success',
        confirmButtonColor: '#6366f1',
    }).then(() => {
        // Refresh halaman setelah tombol OK ditekan
        if (refreshAfter) {
            router.reload();
        }
    });
};

onMounted(() => {
    const flash = usePage().props.flash as { success?: string; error?: string };

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

const filteredUsers = computed(() => {
    let data = [...users.value];

    if (searchTerm.value) {
        const search = searchTerm.value.toLowerCase();
        data = data.filter((u) => [u.name, u.email, u.role?.name].filter(Boolean).some((v) => v!.toLowerCase().includes(search)));
    }

    data.sort((a, b) => {
        const get = (user: User) => {
            switch (sortField.value) {
                case 'email':
                    return user.email.toLowerCase();
                case 'email_verified_at':
                    return user.email_verified_at ?? '';
                case 'role':
                    return user.role?.name.toLowerCase() ?? '';
                default:
                    return user.name.toLowerCase();
            }
        };
        return sortDirection.value === 'asc' ? get(a).localeCompare(get(b)) : get(b).localeCompare(get(a));
    });

    return data;
});

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredUsers.value.slice(start, start + perPage.value);
});

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / perPage.value));
const totalItems = computed(() => filteredUsers.value.length);

const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page;
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

const deleteUser = (id: number) => {
    Swal.fire({
        title: 'Delete this user?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/userManagement/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'User has been deleted.',
                        icon: 'success',
                        confirmButtonColor: '#6366f1',
                    }).then(() => {
                        router.reload();
                    });
                },
            });
        }
    });
};

const { getInitials } = useInitials();

// Computed untuk cek apakah email telah diubah
const emailChanged = computed(() => {
    return formData.value.current_email !== formData.value.email;
});
</script>

<template>
    <Head title="User Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <Button size="sm" class="bg-indigo-500 text-white hover:bg-indigo-700" @click="openCreateModal">
                    <CirclePlus class="mr-1" /> Create
                </Button>
                <div class="relative">
                    <Search class="absolute left-2 top-2 h-4 w-4 text-gray-500" />
                    <Input v-model="searchTerm" placeholder="Search users..." class="w-64 pl-8" />
                </div>
            </div>

            <div class="overflow-auto rounded-xl border">
                <Table>
                    <TableCaption>User List (Total: {{ totalItems }})</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead></TableHead>
                            <TableHead class="cursor-pointer" @click="sortBy('name')">Name {{ getSortIcon('name') }}</TableHead>
                            <TableHead class="cursor-pointer" @click="sortBy('email')">Email {{ getSortIcon('email') }}</TableHead>
                            <TableHead class="cursor-pointer" @click="sortBy('email_verified_at')"
                                >Verified {{ getSortIcon('email_verified_at') }}</TableHead
                            >
                            <TableHead class="cursor-pointer" @click="sortBy('role')">Role {{ getSortIcon('role') }}</TableHead>
                            <TableHead class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in paginatedUsers" :key="user.id">
                            <TableCell class="text-center">
                                <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                                    <AvatarFallback class="rounded-lg text-black dark:text-white">
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </TableCell>

                            <TableCell class="font-medium">{{ user.name }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>
                                <span
                                    :class="
                                        user.email_verified_at
                                            ? 'rounded-full bg-green-500 px-3 py-1 text-white'
                                            : 'rounded-full bg-gray-500 px-3 py-1 text-white'
                                    "
                                >
                                    {{ user.email_verified_at ? 'Verified' : 'Not Verified' }}
                                </span>
                            </TableCell>
                            <TableCell>{{ user.role?.name ?? '—' }}</TableCell>
                            <TableCell class="flex justify-center gap-2">
                                <Button size="sm" class="bg-blue-500 text-white hover:bg-blue-700" @click="openEditModal(user)">
                                    <Pencil />
                                </Button>
                                <Button size="sm" class="bg-rose-500 text-white hover:bg-rose-700" @click="deleteUser(user.id)">
                                    <Trash />
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="paginatedUsers.length === 0">
                            <TableCell colspan="6" class="py-4 text-center">No users found</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="mt-4 flex items-center justify-between border-t pt-4">
                <div class="text-sm">
                    Showing
                    {{ paginatedUsers.length ? (currentPage - 1) * perPage + 1 : 0 }} to {{ Math.min(currentPage * perPage, totalItems) }} of
                    {{ totalItems }}
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <Button
                        v-for="page in totalPages"
                        :key="page"
                        variant="outline"
                        size="sm"
                        :class="{ 'bg-indigo-500 text-white': currentPage === page }"
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </Button>
                    <Button variant="outline" size="sm" :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <div v-if="createModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Create New User</h3>
                    <Button variant="ghost" size="sm" @click="closeModals">
                        <X class="h-4 w-4" />
                    </Button>
                </div>
                <form @submit.prevent="createUser">
                    <div class="mb-4">
                        <label for="name" class="mb-1 block text-sm font-medium">Name</label>
                        <Input id="name" v-model="formData.name" type="text" />
                        <p v-if="formErrors.name" class="mt-1 text-sm text-red-500">{{ formErrors.name }}</p>
                    </div>

                    <div class="mb-4">
                        <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                        <Input id="email" v-model="formData.email" type="email" />
                        <p v-if="formErrors.email" class="mt-1 text-sm text-red-500">{{ formErrors.email }}</p>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                        <Input id="password" v-model="formData.password" type="password" />
                        <p v-if="formErrors.password" class="mt-1 text-sm text-red-500">{{ formErrors.password }}</p>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="mb-1 block text-sm font-medium">Confirm Password</label>
                        <Input id="password_confirmation" v-model="formData.password_confirmation" type="password" />
                    </div>

                    <div class="mb-4">
                        <label for="role" class="mb-1 block text-sm font-medium">Role</label>
                        <select 
                            id="role" 
                            v-model="formData.role_id" 
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800"
                        >
                            <option :value="null" disabled>Select Role</option>
                            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </select>
                        <p v-if="formErrors.role_id" class="mt-1 text-sm text-red-500">{{ formErrors.role_id }}</p>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <Button variant="outline" type="button" @click="closeModals">Cancel</Button>
                        <Button type="submit" class="bg-indigo-500 text-white hover:bg-indigo-700">Create User</Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div v-if="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Edit User</h3>
                    <Button variant="ghost" size="sm" @click="closeModals">
                        <X class="h-4 w-4" />
                    </Button>
                </div>
                <form @submit.prevent="updateUser">
                    <div class="mb-4">
                        <label for="edit-name" class="mb-1 block text-sm font-medium">Name</label>
                        <Input id="edit-name" v-model="formData.name" type="text" />
                        <p v-if="formErrors.name" class="mt-1 text-sm text-red-500">{{ formErrors.name }}</p>
                    </div>

                    <div class="mb-4">
                        <label for="edit-email" class="mb-1 block text-sm font-medium">Email</label>
                        <Input id="edit-email" v-model="formData.email" type="email" />
                        <p v-if="formErrors.email" class="mt-1 text-sm text-red-500">{{ formErrors.email }}</p>
                    </div>

                    <div class="mb-4">
                        <label for="edit-password" class="mb-1 block text-sm font-medium">Password (leave blank to keep current)</label>
                        <Input id="edit-password" v-model="formData.password" type="password" />
                        <p v-if="formErrors.password" class="mt-1 text-sm text-red-500">{{ formErrors.password }}</p>
                    </div>

                    <div class="mb-4">
                        <label for="edit-password_confirmation" class="mb-1 block text-sm font-medium">Confirm Password</label>
                        <Input id="edit-password_confirmation" v-model="formData.password_confirmation" type="password" />
                    </div>

                    <div class="mb-4">
                        <label for="edit-role" class="mb-1 block text-sm font-medium">Role</label>
                        <select 
                            id="edit-role" 
                            v-model="formData.role_id" 
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800"
                        >
                            <option :value="null" disabled>Select Role</option>
                            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </select>
                        <p v-if="formErrors.role_id" class="mt-1 text-sm text-red-500">{{ formErrors.role_id }}</p>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <Button variant="outline" type="button" @click="closeModals">Cancel</Button>
                        <Button type="submit" class="bg-indigo-500 text-white hover:bg-indigo-700">Update User</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>