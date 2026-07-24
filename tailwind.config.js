/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.{html,js}', // Matches all .html and .js files in all folders
  ],
  theme: {
    extend: {
      fontFamily: {
        montserrat: ['Montserrat', 'sans-serif'],
        poppins: ['Poppins', 'sans-serif'],
      },
      keyframes: {
        'nav-slide-down': {
          '0%': {
            transform: 'translateY(-100%)',
            opacity: '0',
          },
          '100%': {
            transform: 'translateY(0)',
            opacity: '1',
          },
        },
        'marquee-scroll': {
          '0%': {
            transform: 'translateX(0)',
          },
          '100%': {
            transform: 'translateX(-50%)',
          },
        },
      },
      animation: {
        'nav-slide-down': 'nav-slide-down 0.5s cubic-bezier(.4, 0, .2, 1) both',
        'marquee-scroll': 'marquee-scroll 30s linear infinite',
      },
    },
  },
  plugins: [],
}
