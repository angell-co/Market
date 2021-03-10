const defaultTheme = require('tailwindcss/defaultTheme')
const colors = require('tailwindcss/colors')

module.exports = {
  purge: {
    content: ['../../../templates/vendor-dashboard/**/*.html'],
  },
  darkMode: false, // or 'media' or 'class'
  theme: {
    extend: {
      colors: {
        brand: colors.teal,
        blue: colors.indigo,
        red: colors.rose,
        green: colors.emerald,
        orange: colors.orange,
      },
      fontFamily: {
        sans: ['Inter var', ...defaultTheme.fontFamily.sans],
      },
      maxHeight: {
        'screen-minus-header': 'calc(100vh - 13rem)',
      },
      screens: {
        'smh': {'raw': '(min-height: 560px)'},
      },
    },
  },
  variants: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/aspect-ratio'),
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
