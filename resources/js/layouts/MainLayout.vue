<script setup lang="ts">
import { home, login } from '@/routes';
import { Link } from '@inertiajs/vue3';
import UserDropdown from '@/components/UserDropdown.vue';

defineProps<{
    contentClass?: string;
    mainClass?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-[#171717] text-white">
        <header class="bg-[#0f0f0f]">
            <div
                class="mx-auto flex h-20 w-full max-w-6xl items-center justify-between px-6"
            >
                <Link :href="home()" class="inline-flex items-center">
                    <img
                        src="/logos/mindwave-logo.png"
                        alt="Mindwave"
                        class="h-12 w-auto"
                    />
                </Link>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="login()"
                        class="rounded-full bg-gradient-to-r from-amber-500 via-rose-500 to-violet-500 p-[2px] text-sm font-medium text-white transition hover:opacity-90"
                    >
                        <span
                            class="block rounded-full bg-[#0f0f0f] px-4 py-2 text-white"
                        >
                            Log in
                        </span>
                    </Link>

                    <div v-else class="relative">
                       <UserDropdown/>
                    </div>
                </div>
            </div>
            <div
                class="h-[2px] bg-gradient-to-r from-amber-500 via-rose-500 to-violet-500"
            />
        </header>
        <main
            class="flex flex-1 justify-center px-6 py-10"
            :class="mainClass"
        >
            <div :class="['w-full', contentClass ?? 'max-w-sm']">
                <slot />
            </div>
        </main>
        <footer class="bg-[#0f0f0f]">
            <div
                class="mx-auto flex h-12 w-full max-w-6xl items-center justify-center gap-3 text-sm text-neutral-300"
            >
                <Link :href="'/contact'" class="transition hover:text-white">
                    Contact
                </Link>
                <span class="text-xs text-neutral-500">|</span>
                <Link :href="'/about'" class="transition hover:text-white">
                    About
                </Link>
                <span class="text-xs text-neutral-500">|</span>
                <Link :href="'/support'" class="transition hover:text-white">
                    Support
                </Link>
            </div>
        </footer>
    </div>
</template>
