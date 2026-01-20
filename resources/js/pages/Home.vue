<script setup lang="ts">
import { dashboard, home, login, register } from '@/routes';
import MixAnalyzer from '@/components/MixAnalyzer.vue';
import { Head, Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

</script>

<template>
    <Head title="Home">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header
            class="w-full h-16 rounded-b-xl"
            style="background: radial-gradient(120% 80% at 0% 0%, rgba(255, 142, 61, 0.6) 0%, rgba(255, 142, 61, 0) 55%), radial-gradient(120% 80% at 100% 0%, rgba(167, 76, 255, 0.6) 0%, rgba(167, 76, 255, 0) 55%), radial-gradient(140% 120% at 50% 100%, rgba(255, 106, 0, 0.5) 0%, rgba(255, 106, 0, 0) 60%), linear-gradient(120deg, #5a2a82 0%, #ff7a2d 55%, #7a2cbf 100%);"
        >
            <nav class="flex h-full items-center justify-between px-6 text-sm lg:px-8">
                <Link
                    :href="home()"
                    class="text-3xl font-bold tracking-wide text-white"
                >
                    mindwave
                </Link>
                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-full border-2 border-white/70 px-4 py-1.5 text-sm font-semibold text-white/90"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-full border-2 border-white/70 px-4 py-1.5 text-sm font-semibold text-white/90"
                        >
                            Register
                        </Link>
                    </template>
                </div>
            </nav>
        </header>
        <div class="relative flex-1">
            <div class="fixed left-1/2 top-[18vh] -translate-x-1/2 text-center">
                <h1 class="text-8xl font-bold tracking-wide">
                    <span class="font-extrabold text-white">Mix</span>
                    <span class="font-extrabold text-[#FF7A2D]">Lens</span>
                </h1>
                <p class="mt-4 text-lg font-medium text-black/70 dark:text-white/90">
                    Every track. Every transition. One mix.
                </p>
            </div>
            <div class="mx-auto w-full max-w-3xl px-6 pt-[45vh]">
                <MixAnalyzer />
            </div>
        </div>
        <footer
            class="w-full border-t border-black/10 bg-white/90 px-6 py-3 text-sm text-[#1b1b18] backdrop-blur dark:border-white/10 dark:bg-[#0a0a0a]/90 dark:text-[#EDEDEC]"
        >
            <nav class="flex items-center justify-center gap-6">
                <Link href="/contact" class="hover:underline">Contact</Link>
                <Link href="/about" class="hover:underline">About</Link>
                <Link href="/support" class="hover:underline">Support</Link>
            </nav>
        </footer>
    </div>
</template>
