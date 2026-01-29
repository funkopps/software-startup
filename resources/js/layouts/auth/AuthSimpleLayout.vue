<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthLayout>
        <slot />
    </AuthLayout>
</template>
