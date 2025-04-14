<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Briefcase, FileUser, Folder, LayoutGrid, UsersRound } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage<{ props: { auth: { user: { role_id: number } } } }>();
const user = (page.props.auth as { user: { role_id: number } }).user;

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Client',
        href: '/clients', // Perbaiki href, seharusnya bukan '/employees'
        icon: Briefcase, // Anda bisa menggunakan icon lain juga jika diinginkan
    },
    {
        title: 'Employees',
        href: '/employeesSec', // Perbaiki href untuk membedakan dari Employees
        icon: UsersRound, // Gunakan icon yang sama atau pertimbangkan menggunakan icon lain
    },
    {
        title: 'Projects',
        href: '/projects', // Perbaiki href untuk Projects
        icon: Folder, // Icon Folder untuk merepresentasikan Projects
    },
    {
        title: 'Activity Logs',
        href: '/activitylog', // Perbaiki href untuk Projects
        icon: Folder, // Icon Folder untuk merepresentasikan Projects
    },
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits',
    //     icon: BookOpen,
    // },
    {
        title: 'Users Management',
        href: '/userManagement',
        icon: FileUser,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <!-- Hanya tampilkan jika user.role_id = 1 (Administrator) -->
        <SidebarFooter>
            <NavFooter v-if="user?.role_id === 1" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
