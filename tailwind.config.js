import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
import rtl from "tailwindcss-rtl";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                en: ["Figtree", ...defaultTheme.fontFamily.sans],
                fa: ["Vazirmatn", "Tahoma", "sans-serif"],
            },
            colors: {
                light: "#fdfdfc",
                dark: "#0a0a0a",
            },
        },
    },

    plugins: [forms, rtl],
};
