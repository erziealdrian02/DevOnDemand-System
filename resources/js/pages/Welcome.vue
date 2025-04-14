<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

// Parallax effect
const handleMouseMove = (e: MouseEvent) => {
    const parallaxElements = document.querySelectorAll('.parallax');
    const mouseX = e.clientX / window.innerWidth;
    const mouseY = e.clientY / window.innerHeight;

    parallaxElements.forEach((el) => {
        const speed = parseFloat((el as HTMLElement).dataset.speed || '0.05');
        const x = (mouseX - 0.5) * speed * 100;
        const y = (mouseY - 0.5) * speed * 100;
        (el as HTMLElement).style.transform = `translate(${x}px, ${y}px)`;
    });
};

onMounted(() => {
    window.addEventListener('mousemove', handleMouseMove);
});
</script>

<template>
    <Head title="DevOnDemand-System">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col overflow-hidden bg-gradient-to-b from-[#0a0a0a] to-[#1b1b18] text-white">
        <!-- Header -->
        <header class="mx-auto w-full max-w-7xl px-6 py-6">
            <nav class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="text-2xl font-bold text-indigo-900">DevOnDemand</div>
                    <div class="rounded bg-indigo-900 px-2 py-0.5 text-xs font-semibold">System</div>
                </div>
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-white transition-all duration-300 hover:border-[#3E3E3A]"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="route('register')"
                        class="inline-block rounded-sm border border-indigo-900 bg-indigo-900 px-5 py-1.5 text-sm leading-normal text-white transition-all duration-300 hover:bg-[#e53e2e]"
                    >
                        Register
                    </Link>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="relative flex flex-1 flex-col items-center justify-center px-6">
            <!-- Parallax Elements -->
            <div class="parallax absolute left-20 top-20 h-32 w-32 rounded-full bg-indigo-900 opacity-10 blur-3xl" data-speed="0.05"></div>
            <div class="parallax absolute bottom-20 right-20 h-40 w-40 rounded-full bg-indigo-900 opacity-10 blur-3xl" data-speed="0.08"></div>
            <div class="parallax absolute left-1/4 top-1/2 h-20 w-20 rounded-full bg-white opacity-5 blur-2xl" data-speed="0.12"></div>

            <!-- Tech Badge -->
            <div class="absolute right-10 top-10 flex flex-col items-end gap-2 md:gap-3">
                <div class="flex items-center gap-2 rounded-full bg-black/30 px-3 py-1.5 backdrop-blur-sm">
                    <span class="text-xs font-medium">Powered by</span>
                    <span class="font-bold text-[#FF4433]">Laravel 12</span>
                </div>
                <div class="flex items-center gap-2 rounded-full bg-black/30 px-3 py-1.5 backdrop-blur-sm">
                    <span class="text-xs font-medium">Built with</span>
                    <span class="font-bold text-[#41B883]">Vue.js</span>
                </div>
            </div>

            <div class="relative z-10 w-full max-w-4xl text-center">
                <h1 class="mb-6 text-4xl font-bold tracking-tight md:text-6xl lg:text-7xl">
                    <span class="text-indigo-900">IT Outsourcing</span> Project Management Platform
                </h1>
                <p class="mx-auto mb-8 max-w-2xl text-lg text-gray-300 md:text-xl">
                    Streamline your IT outsourcing projects with our comprehensive management system designed for modern businesses.
                </p>

                <div class="mb-12 flex flex-col justify-center gap-4 md:flex-row">
                    <Link
                        :href="route('register')"
                        class="inline-flex items-center justify-center rounded-md bg-indigo-900 px-8 py-3 text-base font-medium text-white transition-all duration-300 hover:bg-[#e53e2e]"
                    >
                        Get Started
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                fill-rule="evenodd"
                                d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </Link>
                    <Link
                        href="#features"
                        class="inline-flex items-center justify-center rounded-md border border-[#3E3E3A] px-8 py-3 text-base font-medium text-white transition-all duration-300 hover:border-indigo-900"
                    >
                        Learn More
                    </Link>
                </div>

                <!-- 3D Floating Elements -->
                <div class="relative mb-12 h-64 overflow-hidden md:h-80">
                    <div
                        class="parallax absolute left-1/4 top-0 h-32 w-32 -translate-x-1/2 transform rounded-lg bg-gradient-to-br from-indigo-900/20 to-transparent"
                        data-speed="0.03"
                    ></div>
                    <div
                        class="parallax absolute left-3/4 top-1/4 h-40 w-40 -translate-x-1/2 transform rounded-lg bg-gradient-to-br from-indigo-900/30 to-transparent"
                        data-speed="0.05"
                    ></div>
                    <div
                        class="parallax absolute bottom-0 left-1/2 h-48 w-48 -translate-x-1/2 transform rounded-lg bg-gradient-to-br from-indigo-900/20 to-transparent"
                        data-speed="0.07"
                    ></div>

                    <!-- Dashboard Preview -->
                    <div class="absolute left-1/2 top-1/2 w-full max-w-2xl -translate-x-1/2 -translate-y-1/2 transform">
                        <div class="overflow-hidden rounded-lg border border-[#3E3E3A] bg-[#161615] shadow-2xl">
                            <div class="flex items-center gap-2 bg-[#0a0a0a] px-4 py-2">
                                <div class="flex gap-1.5">
                                    <div class="h-3 w-3 rounded-full bg-indigo-900"></div>
                                    <div class="h-3 w-3 rounded-full bg-[#FFC107]"></div>
                                    <div class="h-3 w-3 rounded-full bg-[#4CAF50]"></div>
                                </div>
                                <div class="text-xs text-gray-400">DevOnDemand Dashboard</div>
                            </div>
                            <div class="grid grid-cols-3 gap-3 p-4">
                                <div class="rounded-md bg-[#1D1D1B] p-3">
                                    <div class="mb-2 h-2 w-2/3 rounded bg-[#3E3E3A]"></div>
                                    <div class="h-10 rounded bg-[#2D2D2B]"></div>
                                </div>
                                <div class="rounded-md bg-[#1D1D1B] p-3">
                                    <div class="mb-2 h-2 w-1/2 rounded bg-[#3E3E3A]"></div>
                                    <div class="h-10 rounded bg-[#2D2D2B]"></div>
                                </div>
                                <div class="rounded-md bg-[#1D1D1B] p-3">
                                    <div class="mb-2 h-2 w-3/4 rounded bg-[#3E3E3A]"></div>
                                    <div class="h-10 rounded bg-[#2D2D2B]"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Features Section -->
        <section id="features" class="px-6 py-16">
            <div class="mx-auto max-w-7xl">
                <h2 class="mb-12 text-center text-3xl font-bold md:text-4xl">Key Features</h2>

                <div class="grid gap-8 md:grid-cols-3">
                    <div class="rounded-lg border border-[#3E3E3A] bg-[#161615] p-6 transition-all duration-300 hover:border-indigo-900">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-900/20">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6 text-indigo-900"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold">Project Management</h3>
                        <p class="text-gray-400">Comprehensive tools to manage your IT outsourcing projects from start to finish.</p>
                    </div>

                    <div class="rounded-lg border border-[#3E3E3A] bg-[#161615] p-6 transition-all duration-300 hover:border-indigo-900">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-900/20">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6 text-indigo-900"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold">Team Collaboration</h3>
                        <p class="text-gray-400">Connect your teams across different locations with real-time collaboration tools.</p>
                    </div>

                    <div class="rounded-lg border border-[#3E3E3A] bg-[#161615] p-6 transition-all duration-300 hover:border-indigo-900">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-900/20">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6 text-indigo-900"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold">Analytics & Reporting</h3>
                        <p class="text-gray-400">Gain insights into your projects with detailed analytics and customizable reports.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-[#3E3E3A] px-6 py-8">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between md:flex-row">
                <div class="mb-4 flex items-center gap-2 md:mb-0">
                    <div class="font-bold text-indigo-900">DevOnDemand</div>
                    <div class="rounded bg-indigo-900 px-1.5 py-0.5 text-xs font-semibold">System</div>
                </div>

                <div class="text-sm text-gray-400">&copy; {{ new Date().getFullYear() }} DevOnDemand-System. Built with Laravel 12 and Vue.js</div>

                <div class="mt-4 flex gap-4 md:mt-0">
                    <a href="#" class="text-gray-400 transition-colors hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"
                            />
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 transition-colors hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"
                            />
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 transition-colors hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"
                            />
                        </svg>
                    </a>
                </div>
            </div>
        </footer>
    </div>
</template>
