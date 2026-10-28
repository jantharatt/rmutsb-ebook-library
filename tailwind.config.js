import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/Http/Controllers/ChatbotController.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Sarabun', ...defaultTheme.fontFamily.sans],
                display: ['"Noto Serif Thai"', 'Sarabun', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                // อัตลักษณ์ มทร.สุวรรณภูมิ (น้ำเงิน–ทอง จากโลโก้ RUS)
                ink: {
                    50: '#f2f5fb',
                    100: '#e3e9f5',
                    200: '#c3cfe8',
                    300: '#93a8d3',
                    400: '#5d78b6',
                    500: '#3a5598',
                    600: '#2a417c',
                    700: '#213465',
                    800: '#172649',
                    900: '#0f1a35',
                    950: '#0a1226',
                },
                royal: {
                    50: '#eef3ff',
                    100: '#dbe5ff',
                    500: '#2050e0',
                    600: '#0a3fd4',
                    700: '#0033b0',
                    800: '#062a8a',
                },
                gold: {
                    50: '#fff9e8',
                    100: '#ffefc2',
                    200: '#ffe08a',
                    300: '#fdcd4c',
                    400: '#fbbd1e',
                    500: '#f2a900',
                    600: '#cf8a00',
                    700: '#a56700',
                },
                paper: '#f5f6fa',
            },
            boxShadow: {
                card: '0 1px 2px rgba(15,26,53,.06), 0 4px 16px -4px rgba(15,26,53,.08)',
                lift: '0 2px 4px rgba(15,26,53,.06), 0 16px 32px -12px rgba(15,26,53,.22)',
            },
        },
    },

    plugins: [forms],
};
