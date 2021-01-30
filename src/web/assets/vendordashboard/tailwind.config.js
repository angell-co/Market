const defaultTheme = require('tailwindcss/defaultTheme')
const colors = require('tailwindcss/colors')

module.exports = {
  purge: {
    content: ['../../../templates/vendor-dashboard/**/*.html'],
    options: {
      safelist: [
        'focus:ring-blue-500',
        'focus:ring-green-500',
        'focus:ring-pink-500',
        'bg-blue-600',
        'bg-green-600',
        'bg-pink-600',
        'hover:bg-blue-700',
        'hover:bg-green-700',
        'hover:bg-pink-700',
        'bg-blue-100',
        'bg-green-100',
        'bg-pink-100',
        'text-blue-800',
        'text-green-800',
        'text-pink-800'
      ]
    },
  },
  darkMode: false, // or 'media' or 'class'
  theme: {
    extend: {
      colors: {
        brand: colors.teal,
        blue: colors.indigo,
      },
      fontFamily: {
        sans: ['Inter var', ...defaultTheme.fontFamily.sans],
      },
    },
  },
  variants: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
