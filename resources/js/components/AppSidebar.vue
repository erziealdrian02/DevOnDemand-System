<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Briefcase, Folder, LayoutGrid, UsersRound } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Employees',
        href: '/employees',
        icon: UsersRound,
    },
    {
        title: 'Client',
        href: '/clients', // Perbaiki href, seharusnya bukan '/employees'
        icon: Briefcase, // Anda bisa menggunakan icon lain juga jika diinginkan
    },
    {
        title: 'Employees Another',
        href: '/employeesSec', // Perbaiki href untuk membedakan dari Employees
        icon: UsersRound, // Gunakan icon yang sama atau pertimbangkan menggunakan icon lain
    },
    {
        title: 'Projects',
        href: '/projects', // Perbaiki href untuk Projects
        icon: Folder, // Icon Folder untuk merepresentasikan Projects
    },
    {
        title: 'Assignments',
        href: '/assignments', // Perbaiki href dan eja "Asignments" menjadi "Assignments"
        icon: BookOpen, // Icon BookOpen untuk merepresentasikan daftar tugas
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

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
