import { onMounted, ref } from 'vue';

type Appearance = 'dark';

export function updateTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    document.documentElement.classList.add('dark');
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    updateTheme();
    localStorage.setItem('appearance', 'dark');
    setCookie('appearance', 'dark');
}

const appearance = ref<Appearance>('dark');

export function useAppearance() {
    onMounted(() => {
        appearance.value = 'dark';
    });

    function updateAppearance() {
        appearance.value = 'dark';
        localStorage.setItem('appearance', 'dark');
        setCookie('appearance', 'dark');
        updateTheme();
    }

    return {
        appearance,
        updateAppearance,
    };
}
