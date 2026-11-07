const defaultTheme = require('tailwindcss/defaultTheme');
const plugin = require('tailwindcss/plugin');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        'object-center',
        'object-top',
        'object-bottom',
        'object-left',
        'object-right',
        'object-left-top',
        'object-left-bottom',
        'object-right-top',
        'object-right-bottom',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        // `can-hover:` applies only on devices with a real hover (mouse/trackpad).
        // Use it to hide controls until hover, so touch users still see them.
        plugin(({ addVariant }) => {
            addVariant('can-hover', '@media (hover: hover) and (pointer: fine)');
        }),
    ],
};
