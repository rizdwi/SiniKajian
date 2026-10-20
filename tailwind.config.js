/** Tailwind config — cermin persis konfigurasi inline yang dulu dimuat
 *  via CDN Play (resources/views/layouts/app.blade.php). Sejak 2026-10-20
 *  CSS dikompilasi statis (npm run build:css) agar browser tidak lagi
 *  mengompilasi utility di setiap pindah halaman (upgrade kecepatan). */
/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './resources/views/**/*.blade.php',
    './public/js/**/*.js',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
        arabic: ['Amiri', 'serif'],
      },
      colors: {
        primary: '#1D4D4F',
        secondary: '#235B5E',
        mint: '#E6F4F1',
        'mint-dark': '#D1EAE5',
        neutral: '#F8FAF9',
        surface: '#FFFFFF',
        'card-border': '#E5ECEB',
        'text-primary': '#1C2224',
        'text-secondary': '#607072',
        'text-inverse': '#FFFFFF',
        'gold-accent': '#FCD34D',
        brand: {
          50: '#F0F7F6',
          100: '#E6F4F1',
          200: '#D1EAE5',
          300: '#A4D3CC',
          400: '#5FABA2',
          500: '#2E7A7D',
          600: '#235B5E',
          700: '#1D4D4F',
          800: '#153A3B',
          900: '#0F292A',
          950: '#0A1B1C',
        },
      },
    },
  },
  plugins: [],
};
