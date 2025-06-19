import defaultTheme from "tailwindcss/defaultTheme";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ["Roboto", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: "#2571eb", // Vibrant Blue - Primary actions, links, focus states
                secondary: "#64748b", // Slate - Secondary elements, subdued content
                accent: "#8b5cf6", // Purple - Accents, highlights, feature callouts
                dark: "#071330", // Dark Blue (Rich, Deep Dark Mode Base for High Contrast)
                "dark-page": "#0c4160", // Midnight Blue Background for UI Consistency
                positive: "#27ae60", // Success Green (Bright, Visible for Confirmation Messages)
                negative: "#c0392b", // Strong Red (Better for Error Visibility)
                info: "#2980b9", // Informational Blue (Well-balanced and Noticeable)
                warning: "#f39c12", // Golden Yellow (Softer but Attention-Grabbing)
            },
        },
    },
    plugins: [],
};
