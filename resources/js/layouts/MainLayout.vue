<script setup lang="ts">
import { dashboard, home, login, register } from '@/routes';
import { Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        canRegister?: boolean;
        logoSrc?: string;
        logoAlt?: string;
    }>(),
    {
        canRegister: true,
        logoSrc: '/mindwave-logo.png',
        logoAlt: 'Mindwave',
    },
);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="h-16 w-full rounded-b-xl bg-[#111111]">
            <nav class="flex h-full items-center justify-between px-6 text-sm lg:px-8">
                <Link :href="home()" class="flex items-center">
                    <img
                        :src="logoSrc"
                        :alt="logoAlt"
                        class="h-10 w-auto"
                    />
                </Link>
                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-block rounded-sm border border-white/30 px-5 py-1.5 text-sm leading-normal text-white/90 hover:border-white/50"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-full border-2 border-transparent bg-transparent px-4 py-1.5 text-sm font-semibold"
                            style="background: linear-gradient(#111111, #111111) padding-box, linear-gradient(120deg, #ff7a2d 0%, #ff5a8a 45%, #7a2cbf 100%) border-box;"
                        >
                            <span class="text-white">Log in</span>
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-full border-2 border-transparent bg-transparent px-4 py-1.5 text-sm font-semibold"
                            style="background: linear-gradient(#111111, #111111) padding-box, linear-gradient(120deg, #ff7a2d 0%, #ff5a8a 45%, #7a2cbf 100%) border-box;"
                        >
                            <span class="text-white">Register</span>
                        </Link>
                    </template>
                </div>
            </nav>
        </header>
        <main class="flex-1">
            <slot />
        </main>
        <footer
            class="w-full border-t border-black/10 bg-white/90 px-6 py-3 text-sm text-[#1b1b18] backdrop-blur dark:border-white/10 dark:bg-[#0a0a0a]/90 dark:text-[#EDEDEC]"
        >
            <nav class="flex items-center justify-center gap-3">
                <Link href="/contact" class="hover:underline">Contact</Link>
                <span class="text-black/30 dark:text-white/30">|</span>
                <Link href="/about" class="hover:underline">About</Link>
                <span class="text-black/30 dark:text-white/30">|</span>
                <Link href="/support" class="hover:underline">Support</Link>
            </nav>
        </footer>
    </div>
</template>
