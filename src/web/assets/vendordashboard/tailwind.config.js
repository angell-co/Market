const defaultTheme = require('tailwindcss/defaultTheme')
const colors = require('tailwindcss/colors')

module.exports = {
  purge: [
    '../../../templates/vendor-dashboard/**/*.html',
    './src/**/*.js',
  ],
  darkMode: false, // or 'media' or 'class'
  theme: {
    extend: {
      colors: {
        brand: colors.teal,
        action: colors.fuchsia,
        // brand: {
        //   50: '#f1d4da',
        //   100: '#f3cad2',
        //   200: '#f5bcc7',
        //   300: '#f3a2b3',
        //   400: '#f194a8',
        //   500: '#F0869D',
        //   600: '#ef7590',
        //   700: '#ef6584',
        //   800: '#ef597b',
        //   900: '#f14d72',
        // },
        red: colors.rose,
        green: colors.emerald,
        orange: colors.orange,
      },
      fontFamily: {
        sans: ['Inter var', ...defaultTheme.fontFamily.sans],
      },
      height: {
        'screen-minus-header': 'calc(100vh - 13rem)',
      },
      maxHeight: {
        'screen-minus-header': 'calc(100vh - 13rem)',
      },
      maxWidth: {
        '36': '9rem',
      },
      screens: {
        'smh': {'raw': '(min-height: 560px)'},
      },
      spacing: {
        '112': '28rem',
      },
      zIndex: {
        '60': 60,
        '70': 70,
      },
      ringOffsetWidth: {
        '12': '12px',
        '16': '16px',
      }
    },
  },
  variants: {
    extend: {
      display: ['group-hover', 'group-focus'],
      borderRadius: ['focus'],
    },
  },
  plugins: [
    require('@tailwindcss/aspect-ratio'),
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
