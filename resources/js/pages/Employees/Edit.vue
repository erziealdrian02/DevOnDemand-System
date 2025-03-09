<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { onMounted, reactive } from 'vue';
import { Employee } from '@/types';

type BreadcrumbItem = { title: string; href: string };
const { props } = usePage();
const employee = props.employee as Employee;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Employees', href: '/employees' },
    { title: 'Edit', href: '#' },
];

const form = reactive<Partial<{ name: string; email: string | undefined; position: string; salary: number | undefined }>>({
    name: '',
    email: undefined,
    position: '',
    salary: undefined,
});

onMounted(() => {
    form.name = employee.name ?? '';
    form.email = employee.email ?? '';
    form.position = employee.position ?? '';
    form.salary = employee.salary ?? undefined;
});

const resetForm = () => {
    form.name = '';
    form.email = '';
    form.position = '';
    form.salary = undefined;
};

const submit = () => {
    router.put(`/employees/${employee.id}`, form, { onSuccess: resetForm });
};
</script>

<template>
    <Head title="Edit Employee" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <h1 class="text-2xl font-bold">Edit Employee</h1>
            <form @submit.prevent="submit" class="max-w-lg space-y-6">
                <div v-for="(label, key) in { name: 'Name', email: 'Email', position: 'Position', salary: 'Salary' }" :key="key" class="space-y-2">
                    <Label :for="key">{{ label }}</Label>
                    <Input
                        :id="key"
                        v-model="form[key]"
                        :type="key === 'salary' ? 'number' : key === 'email' ? 'email' : 'text'"
                        :placeholder="label"
                        :required="key !== 'email'"
                        :step="key === 'salary' ? '0.01' : undefined"
                    />
                </div>
                <div class="flex gap-4">
                    <Button type="submit" class="bg-indigo-500 hover:bg-indigo-600">Update</Button>
                    <Button as="a" href="/employees" variant="outline">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
