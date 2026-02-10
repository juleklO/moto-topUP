/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./src/**/*.{php,html,js}"],
  theme: {
    extend: {
      fontFamily: {
        mono: ['"Share Tech Mono"', "ui-monospace", "SFMono-Regular", "Menlo", "Monaco", "Consolas", "monospace"],
      },
      colors: {
        "moto-oil": "#1a1a1a",
        "moto-chrome": "#e5e5e5",
        "moto-alert": "#d9534f",
      },
    },
  },
  plugins: [],
};