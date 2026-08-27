import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Instrument Sans', ...defaultTheme.fontFamily.sans],
                mono: ['IBM Plex Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                bg: 'var(--bg)',
                surface: 'var(--surface)',
                'surface-2': 'var(--surface-2)',
                border: 'var(--border)',
                'border-strong': 'var(--border-strong)',
                text: 'var(--text)',
                'text-2': 'var(--text-2)',
                'text-3': 'var(--text-3)',
                primary: {
                    DEFAULT: 'var(--primary)',
                    hover: 'var(--primary-hover)',
                    soft: 'var(--primary-soft)',
                    fg: 'var(--primary-fg)',
                },
                success: {
                    DEFAULT: 'var(--success)',
                    soft: 'var(--success-soft)',
                },
                warning: {
                    DEFAULT: 'var(--warning)',
                    soft: 'var(--warning-soft)',
                },
                danger: {
                    DEFAULT: 'var(--danger)',
                    soft: 'var(--danger-soft)',
                },
                info: {
                    DEFAULT: 'var(--info)',
                    soft: 'var(--info-soft)',
                },
            },
        },
    },

    plugins: [forms],
};
