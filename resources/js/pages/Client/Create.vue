<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type BreadcrumbItem = { title: string; href: string };
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
    { title: 'Create', href: '#' },
];

// Form State
const form = ref({
    name: '',
    email: '',
    company_name: '',
    metadata: {
        npwp: '',
        industri: '',
    },
    is_active: true,
});

// Reset
const resetForm = () => {
    form.value = {
        name: '',
        email: '',
        company_name: '',
        metadata: {
            npwp: '',
            industri: '',
        },
        is_active: true,
    };
};

// Submit
const submit = () => {
    router.post('/clients', form.value, {
        onSuccess: resetForm,
    });
};
</script>

<template>
    <Head title="Create Client" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-12">
            <h1 class="text-2xl font-bold">Create Client</h1>
            <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 rounded-2xl border p-6 md:grid-cols-[1fr_1px_1fr]">
                <!-- LEFT: Main Info -->
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" type="text" required placeholder="Client name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" required placeholder="Client email" />
                    </div>
                    <div class="space-y-2">
                        <Label for="company_name">Company Name</Label>
                        <Input id="company_name" v-model="form.company_name" type="text" required placeholder="Company name" />
                    </div>
                    <div class="flex items-center space-x-2 pt-4">
                        <Switch id="is_active" v-model:checked="form.is_active" />
                        <Label for="is_active">Aktif</Label>
                    </div>
                </div>

                <div class="bg-white/10"></div>

                <!-- RIGHT: Metadata -->
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="npwp">NPWP</Label>
                        <Input id="npwp" v-model="form.metadata.npwp" type="text" placeholder="Nomor NPWP" />
                    </div>
                    <div class="space-y-2">
                        <Label for="industri">Industri</Label>
                        <Input id="industri" v-model="form.metadata.industri" type="text" placeholder="Industri Klien" />
                    </div>
                </div>

                <!-- Buttons spanning full width -->
                <div class="col-span-1 flex gap-4 pt-4 md:col-span-3">
                    <Button type="submit" class="bg-indigo-500 hover:bg-indigo-600">Save</Button>
                    <Button as="a" href="/clients" variant="outline">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
