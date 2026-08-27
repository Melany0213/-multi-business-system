import { ref } from 'vue';

const isDark = ref(document.documentElement.classList.contains('dark'));

function apply(dark) {
    isDark.value = dark;
    document.documentElement.classList.toggle('dark', dark);
    localStorage.setItem('theme', dark ? 'dark' : 'light');
}

export function useTheme() {
    return {
        isDark,
        setLight: () => apply(false),
        setDark: () => apply(true),
        toggle: () => apply(!isDark.value),
    };
}
