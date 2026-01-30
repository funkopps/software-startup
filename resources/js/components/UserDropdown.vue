<template>
    <div>
        <button
            class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium hover:bg-white/10 cursor-pointer"
            @click="toggleDropdown"
        >
            {{ $page.props.auth.user.name }}
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </button>

        <div
            v-show="dropdownIsOpen"
            class="absolute right-0 mt-2 w-32 rounded-md bg-[#0f0f0f] shadow-lg ring-1 ring-black/20"
            role="menu"
        >
            <Link
                method="post"
                as="button"
                type="button"
                href="/logout"
                class="block w-full px-4 py-2 text-left text-sm hover:bg-white/10 cursor-pointer"
            >
                Log out
            </Link>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const dropdownIsOpen = ref(false)

const toggleDropdown = () => {
    dropdownIsOpen.value = ! dropdownIsOpen.value
}
</script>
