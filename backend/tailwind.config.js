import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Roboto', ...defaultTheme.fontFamily.sans], // Match Quasar
            },
            colors: {
                primary: '#4f46e5', // Match Quasar Primary Color
                secondary: '#26A69A', // Match Quasar Secondary Color
                accent: '#9C27B0',
                dark: '#1D1D1D',
                'dark-page': '#121212',
                positive: '#21BA45',
                negative: '#C10015',
                info: '#31CCEC',
                warning: '#F2C037',
            },
        },
    },
    plugins: [],
};
