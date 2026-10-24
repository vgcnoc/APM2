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
                // Tema Mode Gelap: Hitam Agak Keunguan (Dark Purple-tinted Black)
                slate: {
                    750: '#251a4a',
                    800: '#1c1438', // purple-tinted dark surface & inner wells
                    850: '#160f2e',
                    900: '#110b24', // deep dark purple-black cards & sidebars
                    950: '#0b0718', // deepest void purple-black background
                },
                gray: {
                    800: '#1c1438',
                    850: '#160f2e',
                    900: '#110b24',
                    950: '#0b0718',
                },
                brown: {
                    50: '#fdf8f5',
                    100: '#f5e9e2',
                    200: '#e8d2c4',
                    300: '#d4b29d',
                    400: '#ba8d72',
                    500: '#9e6b4e',
                    600: '#7c4a2d',
                    700: '#643820',
                    800: '#4f2a17',
                    900: '#3d1f10',
                    950: '#291309',
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
