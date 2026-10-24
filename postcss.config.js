import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';
import darkUtilities from './resources/css/dark-utilities.cjs';

export default {
    plugins: [tailwindcss(), darkUtilities(), autoprefixer()],
};
