<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Briefcase, Folder, TableProperties, UsersRound } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    users: Array,
    roles: Array,
    clients: Array,
    employees: Array<{ skillset?: string | string[]; is_available?: boolean }>,
    assignments: Array,
    projects: Array<{ is_approved?: boolean }>,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Calculate summary statistics
const totalProjects = computed(() => props.projects?.length || 0);
const totalClients = computed(() => props.clients?.length || 0);
const totalEmployees = computed(() => props.employees?.length || 0);
const totalAssignments = computed(() => props.assignments?.length || 0);

// Calculate active vs inactive clients
const activeClients = computed(() => props.clients?.filter((client: { is_active?: boolean }) => client.is_active).length || 0);
const inactiveClients = computed(() => props.clients?.filter((client) => !client.is_active).length || 0);

// Calculate approved vs unapproved projects
const approvedProjects = computed(() => props.projects?.filter((project) => project.is_approved).length || 0);
const unapprovedProjects = computed(() => props.projects?.filter((project: { is_approved?: boolean }) => !project.is_approved).length || 0);

// Calculate available vs unavailable employees
const availableEmployees = computed(() => props.employees?.filter((employee) => employee.is_available).length || 0);
const unavailableEmployees = computed(() => props.employees?.filter((employee) => !employee.is_available).length || 0);

// Project Status Chart Options
const projectStatusOptions = ref({
    chart: {
        type: 'donut',
        foreColor: '#EDEDEC',
    },
    labels: ['Approved', 'Unapproved'],
    colors: ['#4CAF50', '#FF5252'],
    legend: {
        position: 'bottom',
        labels: {
            colors: '#EDEDEC',
        },
    },
    dataLabels: {
        enabled: false,
    },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
            },
        },
    },
});

const projectStatusSeries = computed(() => [approvedProjects.value, unapprovedProjects.value]);

// Client Status Chart Options
const clientStatusOptions = ref({
    chart: {
        type: 'donut',
        foreColor: '#EDEDEC',
    },
    labels: ['Active', 'Inactive'],
    colors: ['#2196F3', '#9E9E9E'],
    legend: {
        position: 'bottom',
        labels: {
            colors: '#EDEDEC',
        },
    },
    dataLabels: {
        enabled: false,
    },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
            },
        },
    },
});

const clientStatusSeries = computed(() => [activeClients.value, inactiveClients.value]);

// Employee Skills Chart
const skillsDistribution = computed(() => {
    const skillsCount: Record<string, number> = {};

    props.employees?.forEach((employee: { skillset?: string | string[] }) => {
        if (employee.skillset) {
            const skills = typeof employee.skillset === 'string' ? JSON.parse(employee.skillset) : employee.skillset;

            skills.forEach((skill: string) => {
                skillsCount[skill] = (skillsCount[skill] || 0) + 1;
            });
        }
    });

    // Get top 5 skills
    const sortedSkills = Object.entries(skillsCount)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 5);

    return {
        categories: sortedSkills.map(([skill]) => skill),
        data: sortedSkills.map(([, count]) => count),
    };
});

const skillsChartOptions = ref<{
    chart: { type: string; foreColor: string };
    plotOptions: { bar: { horizontal: boolean; columnWidth: string; borderRadius: number } };
    dataLabels: { enabled: boolean };
    colors: string[];
    xaxis: { categories: string[]; labels: { style: { colors: string } } };
    yaxis: { labels: { style: { colors: string } } };
    grid: { borderColor: string };
}>({
    chart: {
        type: 'bar',
        foreColor: '#EDEDEC',
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            borderRadius: 4,
        },
    },
    dataLabels: {
        enabled: false,
    },
    colors: ['#FF4433'],
    xaxis: {
        categories: [],
        labels: {
            style: {
                colors: '#EDEDEC',
            },
        },
    },
    yaxis: {
        labels: {
            style: {
                colors: '#EDEDEC',
            },
        },
    },
    grid: {
        borderColor: 'rgba(255, 255, 255, 0.1)',
    },
});

const skillsSeries = ref<{ name: string; data: number[] }[]>([
    {
        name: 'Employees',
        data: [],
    },
]);

// Timeline Chart
const timelineChartOptions = ref({
    chart: {
        type: 'area',
        foreColor: '#EDEDEC',
        toolbar: {
            show: false,
        },
    },
    dataLabels: {
        enabled: false,
    },
    stroke: {
        curve: 'smooth',
        width: 2,
    },
    colors: ['#FF4433', '#4CAF50'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.3,
            opacityTo: 0.1,
            stops: [0, 90, 100],
        },
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        labels: {
            style: {
                colors: '#EDEDEC',
            },
        },
    },
    yaxis: {
        labels: {
            style: {
                colors: '#EDEDEC',
            },
        },
    },
    grid: {
        borderColor: 'rgba(255, 255, 255, 0.1)',
    },
    legend: {
        position: 'top',
        labels: {
            colors: '#EDEDEC',
        },
    },
});

const timelineSeries = ref([
    {
        name: 'Projects',
        data: [1, 2, 3, 2, 4, 2],
    },
    {
        name: 'Assignments',
        data: [2, 3, 5, 4, 6, 3],
    },
]);

// Recent activity logs
const recentActivities = ref([
    { id: 1, type: 'Project', action: 'Create', user: 'Admin User', item: 'Mobile System ERP', date: '2025-04-14' },
    { id: 2, type: 'Assignment', action: 'Create', user: 'Admin User', item: 'ASS250001', date: '2025-04-14' },
    { id: 3, type: 'Client', action: 'Update', user: 'Admin User', item: 'Securindo Jaya', date: '2025-04-14' },
    { id: 4, type: 'Employee', action: 'Create', user: 'Muhamad Erzie', item: 'Test Name', date: '2025-04-14' },
]);

onMounted(() => {
    // Update skills chart data after component is mounted
    skillsChartOptions.value.xaxis.categories = skillsDistribution.value.categories;
    skillsSeries.value[0].data = skillsDistribution.value.data;
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Summary Cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-4">
                <div
                    class="flex flex-col justify-between rounded-xl border border-sidebar-border/70 bg-sidebar-accent p-4 dark:border-sidebar-border"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-medium text-sidebar-accent-foreground">Total Projects</h3>
                        <div class="rounded-full bg-[#FF4433]/20 p-2">
                            <Folder class="h-5 w-5 text-[#FF4433]" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-sidebar-accent-foreground">{{ totalProjects }}</div>
                    <div class="mt-2 text-xs text-sidebar-accent-foreground/70">
                        <span class="text-[#4CAF50]">{{ approvedProjects }} approved</span> /
                        <span class="text-[#FF5252]">{{ unapprovedProjects }} pending</span>
                    </div>
                </div>

                <div
                    class="flex flex-col justify-between rounded-xl border border-sidebar-border/70 bg-sidebar-accent p-4 dark:border-sidebar-border"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-medium text-sidebar-accent-foreground">Total Clients</h3>
                        <div class="rounded-full bg-[#2196F3]/20 p-2">
                            <Briefcase class="h-5 w-5 text-[#2196F3]" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-sidebar-accent-foreground">{{ totalClients }}</div>
                    <div class="mt-2 text-xs text-sidebar-accent-foreground/70">
                        <span class="text-[#4CAF50]">{{ activeClients }} active</span> /
                        <span class="text-[#9E9E9E]">{{ inactiveClients }} inactive</span>
                    </div>
                </div>

                <div
                    class="flex flex-col justify-between rounded-xl border border-sidebar-border/70 bg-sidebar-accent p-4 dark:border-sidebar-border"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-medium text-sidebar-accent-foreground">Total Employees</h3>
                        <div class="rounded-full bg-[#9C27B0]/20 p-2">
                            <UsersRound class="h-5 w-5 text-[#9C27B0]" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-sidebar-accent-foreground">{{ totalEmployees }}</div>
                    <div class="mt-2 text-xs text-sidebar-accent-foreground/70">
                        <span class="text-[#4CAF50]">{{ availableEmployees }} available</span> /
                        <span class="text-[#FF5252]">{{ unavailableEmployees }} assigned</span>
                    </div>
                </div>

                <div
                    class="flex flex-col justify-between rounded-xl border border-sidebar-border/70 bg-sidebar-accent p-4 dark:border-sidebar-border"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-medium text-sidebar-accent-foreground">Total Assignments</h3>
                        <div class="rounded-full bg-[#FF9800]/20 p-2">
                            <TableProperties class="h-5 w-5 text-[#FF9800]" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-sidebar-accent-foreground">{{ totalAssignments }}</div>
                    <div class="mt-2 text-xs text-sidebar-accent-foreground/70">
                        <span>Across {{ totalProjects }} projects</span>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-sidebar-accent p-4 dark:border-sidebar-border">
                    <h3 class="mb-4 text-sm font-medium text-sidebar-accent-foreground">Project Status</h3>
                    <div class="h-[200px]">
                        <VueApexCharts type="donut" height="200" :options="projectStatusOptions" :series="projectStatusSeries" />
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-sidebar-accent p-4 dark:border-sidebar-border">
                    <h3 class="mb-4 text-sm font-medium text-sidebar-accent-foreground">Client Status</h3>
                    <div class="h-[200px]">
                        <VueApexCharts type="donut" height="200" :options="clientStatusOptions" :series="clientStatusSeries" />
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-sidebar-accent p-4 dark:border-sidebar-border">
                    <h3 class="mb-4 text-sm font-medium text-sidebar-accent-foreground">Top Employee Skills</h3>
                    <div class="h-[200px]">
                        <VueApexCharts type="bar" height="200" :options="skillsChartOptions" :series="skillsSeries" />
                    </div>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="grid gap-4 md:grid-cols-3">
                <!-- Project Timeline Chart -->
                <div
                    class="relative col-span-2 overflow-hidden rounded-xl border border-sidebar-border/70 bg-sidebar-accent p-4 dark:border-sidebar-border"
                >
                    <h3 class="mb-4 text-sm font-medium text-sidebar-accent-foreground">Project & Assignment Timeline</h3>
                    <div class="h-[300px]">
                        <VueApexCharts type="area" height="300" :options="timelineChartOptions" :series="timelineSeries" />
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-sidebar-accent p-4 dark:border-sidebar-border">
                    <h3 class="mb-4 text-sm font-medium text-sidebar-accent-foreground">Recent Activity</h3>
                    <div class="space-y-3">
                        <div
                            v-for="activity in recentActivities"
                            :key="activity.id"
                            class="flex items-start gap-3 rounded-lg border border-sidebar-border/50 p-3"
                        >
                            <div class="rounded-full bg-sidebar-accent p-1.5">
                                <Folder v-if="activity.type === 'Project'" class="h-4 w-4 text-[#FF4433]" />
                                <TableProperties v-else-if="activity.type === 'Assignment'" class="h-4 w-4 text-[#FF9800]" />
                                <Briefcase v-else-if="activity.type === 'Client'" class="h-4 w-4 text-[#2196F3]" />
                                <UsersRound v-else-if="activity.type === 'Employee'" class="h-4 w-4 text-[#9C27B0]" />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-medium text-sidebar-accent-foreground">{{ activity.action }} {{ activity.type }}</p>
                                    <span class="text-xs text-sidebar-accent-foreground/70">{{ activity.date }}</span>
                                </div>
                                <p class="mt-1 text-xs text-sidebar-accent-foreground/70">{{ activity.item }} by {{ activity.user }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
