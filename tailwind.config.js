/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                mono: ['JetBrains Mono', 'Fira Code', 'monospace'],
            },
            colors: {
                gray: {
                    950: '#0a0e1a',
                },
            },
            animation: {
                'fade-in-up': 'fadeInUp 0.4s ease-out forwards',
                'slide-in': 'slideIn 0.3s ease-out forwards',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
};
