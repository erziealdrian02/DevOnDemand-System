<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import { Client } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { onMounted, reactive } from 'vue';

type BreadcrumbItem = { title: string; href: string };
const { props } = usePage();
const client = props.client as Client;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
    { title: 'Edit', href: '#' },
];

console.log(client);

const form = reactive({
    name: '',
    email: '',
    company_name: '',
    metadata: {
        npwp: '',
        industri: '',
    },
    is_active: true,
});

onMounted(() => {
    form.name = client.name ?? '';
    form.email = client.email ?? '';
    form.company_name = client.company_name ?? '';
    form.metadata.npwp = client.metadata?.npwp ?? '';
    form.metadata.industri = client.metadata?.industri ?? '';
    form.is_active = client.is_active ?? true;
});

const resetForm = () => {
    form.name = '';
    form.email = '';
    form.company_name = '';
    form.metadata.npwp = '';
    form.metadata.industri = '';
    form.is_active = true;
};

const submit = () => {
    router.put(`/clients/${client.id}`, form, {
        onSuccess: resetForm,
    });
};
</script>

<template>
    <Head title="Edit Client" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-12">
            <h1 class="text-2xl font-bold">Edit Client</h1>
            <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 rounded-2xl border p-6 md:grid-cols-[1fr_1px_1fr]">
                <!-- LEFT -->
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

                <!-- Divider -->
                <div class="h-full w-full rounded-full bg-white/10" />

                <!-- RIGHT -->
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

                <!-- Actions -->
                <div class="col-span-1 flex gap-4 pt-4 md:col-span-3">
                    <Button type="submit" class="bg-indigo-500 hover:bg-indigo-600">Update</Button>
                    <Button as="a" href="/clients" variant="outline">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
