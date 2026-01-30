<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/login';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase>
        <Head title="Log in" />
        <template #header>
            <h1 class="text-3xl font-semibold">Log in</h1>
        </template>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6 rounded-lg bg-transparent px-4 py-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Password</Label>

                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full bg-purple-600 hover:bg-purple-500 cursor-pointer text-white"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    Log in
                </Button>
            </div>

            <!--            <div-->
            <!--                class="text-center text-sm text-muted-foreground"-->
            <!--                v-if="canRegister"-->
            <!--            >-->
            <!--                Don't have an account?-->
            <!--                <TextLink :href="register()" :tabindex="5">Sign up</TextLink>-->
            <!--            </div>-->
        </Form>
    </AuthBase>
</template>
