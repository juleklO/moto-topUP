/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./src/**/*.{php,html,js}"],
  theme: {
    extend: {
      fontFamily: {
        mono: ['"Share Tech Mono"', "ui-monospace", "SFMono-Regular", "Menlo", "Monaco", "Consolas", "monospace"],
        sans: ['"Inter"', "ui-sans-serif", "system-ui", "sans-serif"],
      },
      colors: {
        "moto-dark": "#0f172a",
        "moto-panel": "#1e293b",
        "moto-chrome": "#e2e8f0",
        "moto-dim": "#94a3b8",
        "moto-accent": "#f59e0b",
        "moto-alert": "#ef4444",
        "moto-success": "#10b981",
      },
      backgroundImage: {
        "grid-pattern":
          "linear-gradient(to right, #334155 1px, transparent 1px), linear-gradient(to bottom, #334155 1px, transparent 1px)",
      },
      keyframes: {
        "fade-in-down": {
          "0%": { opacity: "0", transform: "translateY(-10px)" },
          "100%": { opacity: "1", transform: "translateY(0)" },
        },
      },
      animation: {
        "fade-in-down": "fade-in-down 250ms ease-out",
      },
    },
  },
  plugins: [],
};
