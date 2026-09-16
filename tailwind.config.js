import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
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
                    50: '#eef1f8',
                    100: '#dee5f4',
                    200: '#c1cce8',
                    300: '#96a8dc',
                    400: '#6883d6',
                    500: '#3d68fb',
                    600: '#2a4ce0',
                    700: '#33409e',
                    800: '#2c3380',
                    900: '#272a5c',
                    950: '#191b39',
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
