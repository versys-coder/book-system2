import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import path from "path";

export default defineConfig({
  base: "/graph2/",
  plugins: [react()],
  resolve: {
    alias: {
      "@app1/api-client": path.resolve(__dirname, "../api-client/src")
    }
  },
  build: {
    outDir: "/var/www/app1/graph2/dist",
    emptyOutDir: true
  },
  server: { host: true, port: 5182 }
});