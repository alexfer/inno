/** @type {import('tailwindcss').Config} */
module.exports = {
    mode: 'jit',
    content: [
        "./assets/**/*.js",
        "./templates/**/*.html.twig",
        "./node_modules/flowbite/**/*.js",
        "./node_modules/tw-elements/js/**/*.js"
    ],
    theme: {
        fontFamily: {
            'oswald': ['Oswald'],
            'sans': ['Helvetica', 'Arial', 'sans-serif'],
        },
        extend: {
            height: {
                '100': '100px',
                '150': '150px',
                '200': '200px',
                '250': '250px',
                '300': '300px',
                '400': '400px',
            },
            width: {
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
        },
    },
    plugins: [
        require("tw-elements/plugin.cjs"),
        require('flowbite/plugin'),
    ],
    darkMode: "class"
}