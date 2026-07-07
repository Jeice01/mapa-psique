import type { Config } from "tailwindcss";

const config: Config = {
  content: ["./index.html", "./src/**/*.{js,ts,jsx,tsx}"],
  theme: {
    extend: {
      colors: {
        brand: {
          600: "#0f766e",
          700: "#115e59",
        },
      },
    },
  },
  plugins: [],
};

export default config;
