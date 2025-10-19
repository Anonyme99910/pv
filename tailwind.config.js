/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#004C97',
          50: '#E6F0FF',
          100: '#CCE1FF',
          200: '#99C3FF',
          300: '#66A5FF',
          400: '#3387FF',
          500: '#004C97',
          600: '#003D7A',
          700: '#002E5C',
          800: '#001F3D',
          900: '#00101F',
        },
        accent: {
          DEFAULT: '#FEC601',
          50: '#FFF9E6',
          100: '#FFF3CC',
          200: '#FFE799',
          300: '#FFDB66',
          400: '#FFCF33',
          500: '#FEC601',
          600: '#CB9E01',
          700: '#987701',
          800: '#654F00',
          900: '#332800',
        },
        background: {
          DEFAULT: '#F4F6FA',
          dark: '#1A1D29',
        },
        text: {
          DEFAULT: '#222831',
          light: '#6B7280',
          dark: '#E5E7EB',
        }
      },
      boxShadow: {
        'card': '0 2px 8px rgba(0, 0, 0, 0.08)',
        'card-hover': '0 4px 16px rgba(0, 0, 0, 0.12)',
      },
      borderRadius: {
        'card': '12px',
      }
    },
  },
  plugins: [],
}
