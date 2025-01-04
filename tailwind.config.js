/** @type {import('tailwindcss').Config} */
module.exports = {
    mode: 'jit',
    content: [
        './assets/**/*.js',
        './templates/theme/tailwind/**/*.{html.twig,js}',
        './node_modules/flowbite/**/*.js',
    ],
    safelist: [
        'bg-red-50',
        'bg-green-50',
        'bg-blue-50',
        'bg-sky-50',
        'bg-orange-50',
        'bg-purple-50',
        'bg-pink-50',
        'bg-cyan-50',
        'bg-stone-50',
        'bg-yellow-50',
        'bg-gray-50',
        'bg-lime-50',
        'bg-emerald-50',
        'bg-amber-50',
        'bg-violet-50',
        'bg-fuchsia-50',
        'bg-slate-50',
        'bg-indigo-50',
    ],
    theme: {
        fontFamily: {
            'oswald': ['Oswald'],
            'montserrat': ['Montserrat'],
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