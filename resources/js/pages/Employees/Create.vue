<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type BreadcrumbItem = { title: string; href: string };
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Employees', href: '/employees' },
  { title: 'Create', href: '#' },
];

const form = ref<Partial<{ name: string; email: string; position: string; salary: number }>>({
  name: '',
  email: undefined,
  position: '',
  salary: undefined,
});

const resetForm = () => {
  form.value = { name: '', email: undefined, position: '', salary: undefined };
};

const submit = () => {
  router.post('/employees', form.value, { onSuccess: resetForm });
};
</script>

<template>
  <Head title="Create Employee" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
      <h1 class="text-2xl font-bold">Create</h1>
      <form @submit.prevent="submit" class="space-y-6 max-w-lg">
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
          <Button type="submit" class="bg-indigo-500 hover:bg-indigo-600">Save</Button>
          <Button as="a" href="/employees" variant="outline">Cancel</Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
