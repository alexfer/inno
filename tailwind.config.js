/** @type {import('tailwindcss').Config} */
import {createRequire} from 'node:module';

const _require = createRequire(import.meta.url);
const colors = require('tailwindcss/colors');
delete colors.lightBlue;
delete colors.warmGray;
delete colors.trueGray;
delete colors.coolGray;
delete colors.blueGray;

module.exports = {
    mode: 'jit',
    content: [
        './assets/**/*.js',
        './templates/theme/tailwind/**/*.{html.twig,js}',
        './node_modules/flowbite/**/*.js',
    ],
    theme: {
        fontFamily: {
            'oswald': ['Oswald'],
            'montserrat': ['Montserrat'],
            'poppins': ['Poppins'],
            'sans': ['Helvetica', 'Arial', 'sans-serif'],
        },
        extend: {
            colors: {
                stone: colors.warmGray,
                sky: colors.lightBlue,
                neutral: colors.trueGray,
                gray: colors.coolGray,
                slate: colors.blueGray,
                ...colors,
            },
            height: {
                '100': '100px',
                '150': '150px',
                '200': '200px',
                '250': '250px',
                '300': '300px',
                '400': '400px',
                '500': '500px',
                '600': '600px',
            },
            width: {
                '100': '100px',
                '150': '150px',
                '295': '295px'
            },
            gridTemplateColumns: {
                'auto-fill-200': 'repeat(auto-fill, minmax(200px, 1fr))',
                'auto-fit-200': 'repeat(auto-fit, minmax(200px, 1fr))',
                'auto-fill-150': 'repeat(auto-fill, minmax(150px, 1fr))',
                'auto-fit-150': 'repeat(auto-fit, minmax(150px, 1fr))',
                'auto-fill-300': 'repeat(auto-fill, minmax(300px, 1fr))',
                'auto-fit-300': 'repeat(auto-fit, minmax(300px, 1fr))',
            },
            keyframes: {
                blinking: {
                    '0%, 100%': { backgroundColor: '#ef4444' },
                    '50%': { backgroundColor: '#fee2e2' },
                }
            },
            animation: {
                blinking: 'blinking 2s ease-in-out infinite',
            },
        },
    },
    plugins: [
        'tw-elements/plugin.cjs',
        'flowbite/plugin',
        '@tailwindcss/typography',
        '@tailwindcss/forms',
        '@tailwindcss/aspect-ratio',
        'tailwindcss/inset',
        'tailwindcss/plugin',
        '@tailwindcss/container-queries',
        '@tailwindcss/line-clamp',
    ],
    darkMode: "class"
}