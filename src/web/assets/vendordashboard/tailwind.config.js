const defaultTheme = require('tailwindcss/defaultTheme')
const colors = require('tailwindcss/colors')

const cgColours = {
  teal: {
    50: '#E8FFFE',
    100: '#C0F7F3',
    200: '#9BF0EB',
    300: '#62E2DA',
    400: '#5BD2CC',
    500: '#52BBB5',
    600: '#48A49E',
    700: '#408C88',
    800: '#2D726E',
    900: '#215451',
  },
  pink: {
    50: '#FFE6EB',
    100: '#F7CCD6',
    200: '#F0BCC7',
    300: '#F0A7B7',
    400: '#EC97AA',
    500: '#F0869D',
    600: '#DC778D',
    700: '#C66D80',
    800: '#AB5D6E',
    900: '#8A4A58',
  },
  yellow: {
    50: '#FFF8E1',
    100: '#FFF1C0',
    200: '#FFE89A',
    300: '#FFDE70',
    400: '#FFD340',
    500: '#FDC300',
    600: '#E6B100',
    700: '#C49702',
    800: '#9F7C06',
    900: '#614B00',
  },
};

module.exports = {
  purge: [
    '../../../templates/vendor-dashboard/**/*.html',
    './src/**/*.js',
  ],
  darkMode: false, // or 'media' or 'class'
  theme: {
    extend: {
      colors: {
        brand: cgColours.teal,
        action: cgColours.yellow,

        red: colors.rose,
        green: colors.emerald,
        orange: cgColours.yellow,
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
        '64': '16rem',
      },
      minWidth: {
        'xs': '20rem',
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
