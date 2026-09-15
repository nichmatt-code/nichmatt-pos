import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#eef3ff',
                    100: '#dfe8ff',
                    200: '#c2d3ff',
                    300: '#96b4ff',
                    400: '#638dff',
                    500: '#3d68fb',
                    600: '#2a4ce0',
                    700: '#213bb8',
                    800: '#1e3392',
                    900: '#1c2e74',
                    950: '#141d47',
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06)',
                soft: '0 12px 32px -12px rgb(15 23 42 / 0.18)',
            },
        },
    },

    plugins: [forms],
};
