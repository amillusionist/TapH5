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
                sans: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#ecfdf5',
                    100: '#d1fae5',
                    200: '#a7f3d0',
                    300: '#6ee7b7',
                    400: '#34d399',
                    500: '#10b981', // Neon Emerald
                    600: '#059669',
                    700: '#047857',
                    800: '#065f46',
                    900: '#064e3b',
                    950: '#022c22',
                },
                accent: {
                    cyan: '#06b6d4',
                    purple: '#8b5cf6',
                    amber: '#f59e0b',
                    rose: '#f43f5e',
                },
            },
            boxShadow: {
                'glow-emerald': '0 0 25px -5px rgba(16, 185, 129, 0.3)',
                'glow-cyan': '0 0 25px -5px rgba(6, 182, 212, 0.3)',
                'glow-purple': '0 0 25px -5px rgba(139, 92, 246, 0.3)',
            },
        },
    },

    plugins: [forms],
};
