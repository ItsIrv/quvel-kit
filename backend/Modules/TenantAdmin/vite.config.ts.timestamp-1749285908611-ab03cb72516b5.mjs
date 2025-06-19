// vite.config.ts
import { defineConfig } from "file:///home/forge/api-quvel.pdxapps.com/backend/Modules/TenantAdmin/node_modules/vite/dist/node/index.js";
import vue from "file:///home/forge/api-quvel.pdxapps.com/backend/Modules/TenantAdmin/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import laravel from "file:///home/forge/api-quvel.pdxapps.com/backend/node_modules/laravel-vite-plugin/dist/index.js";
import { resolve } from "path";
var __vite_injected_original_dirname = "/home/forge/api-quvel.pdxapps.com/backend/Modules/TenantAdmin";
var vite_config_default = defineConfig({
  build: {
    outDir: "../../public/build-tenantadmin",
    emptyOutDir: true,
    manifest: "manifest.json",
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ["vue", "vue-router", "pinia", "axios"]
        }
      }
    }
  },
  plugins: [
    vue(),
    laravel({
      publicDirectory: "../../public",
      buildDirectory: "build-tenantadmin",
      input: [
        __vite_injected_original_dirname + "/resources/js/app.ts",
        __vite_injected_original_dirname + "/resources/css/app.css"
      ],
      refresh: true
    })
  ],
  resolve: {
    alias: {
      "@": resolve(__vite_injected_original_dirname, "resources/js"),
      vue: "vue/dist/vue.esm-bundler.js"
    }
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcudHMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCIvaG9tZS9mb3JnZS9hcGktcXV2ZWwucGR4YXBwcy5jb20vYmFja2VuZC9Nb2R1bGVzL1RlbmFudEFkbWluXCI7Y29uc3QgX192aXRlX2luamVjdGVkX29yaWdpbmFsX2ZpbGVuYW1lID0gXCIvaG9tZS9mb3JnZS9hcGktcXV2ZWwucGR4YXBwcy5jb20vYmFja2VuZC9Nb2R1bGVzL1RlbmFudEFkbWluL3ZpdGUuY29uZmlnLnRzXCI7Y29uc3QgX192aXRlX2luamVjdGVkX29yaWdpbmFsX2ltcG9ydF9tZXRhX3VybCA9IFwiZmlsZTovLy9ob21lL2ZvcmdlL2FwaS1xdXZlbC5wZHhhcHBzLmNvbS9iYWNrZW5kL01vZHVsZXMvVGVuYW50QWRtaW4vdml0ZS5jb25maWcudHNcIjtpbXBvcnQgeyBkZWZpbmVDb25maWcgfSBmcm9tIFwidml0ZVwiO1xuaW1wb3J0IHZ1ZSBmcm9tIFwiQHZpdGVqcy9wbHVnaW4tdnVlXCI7XG5pbXBvcnQgbGFyYXZlbCBmcm9tIFwibGFyYXZlbC12aXRlLXBsdWdpblwiO1xuaW1wb3J0IHsgcmVzb2x2ZSB9IGZyb20gXCJwYXRoXCI7XG5cbmV4cG9ydCBkZWZhdWx0IGRlZmluZUNvbmZpZyh7XG4gICAgYnVpbGQ6IHtcbiAgICAgICAgb3V0RGlyOiBcIi4uLy4uL3B1YmxpYy9idWlsZC10ZW5hbnRhZG1pblwiLFxuICAgICAgICBlbXB0eU91dERpcjogdHJ1ZSxcbiAgICAgICAgbWFuaWZlc3Q6IFwibWFuaWZlc3QuanNvblwiLFxuICAgICAgICByb2xsdXBPcHRpb25zOiB7XG4gICAgICAgICAgICBvdXRwdXQ6IHtcbiAgICAgICAgICAgICAgICBtYW51YWxDaHVua3M6IHtcbiAgICAgICAgICAgICAgICAgICAgdmVuZG9yOiBbXCJ2dWVcIiwgXCJ2dWUtcm91dGVyXCIsIFwicGluaWFcIiwgXCJheGlvc1wiXSxcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgfSxcbiAgICAgICAgfSxcbiAgICB9LFxuICAgIHBsdWdpbnM6IFtcbiAgICAgICAgdnVlKCksXG4gICAgICAgIGxhcmF2ZWwoe1xuICAgICAgICAgICAgcHVibGljRGlyZWN0b3J5OiBcIi4uLy4uL3B1YmxpY1wiLFxuICAgICAgICAgICAgYnVpbGREaXJlY3Rvcnk6IFwiYnVpbGQtdGVuYW50YWRtaW5cIixcbiAgICAgICAgICAgIGlucHV0OiBbXG4gICAgICAgICAgICAgICAgX19kaXJuYW1lICsgXCIvcmVzb3VyY2VzL2pzL2FwcC50c1wiLFxuICAgICAgICAgICAgICAgIF9fZGlybmFtZSArIFwiL3Jlc291cmNlcy9jc3MvYXBwLmNzc1wiLFxuICAgICAgICAgICAgXSxcbiAgICAgICAgICAgIHJlZnJlc2g6IHRydWUsXG4gICAgICAgIH0pLFxuICAgIF0sXG4gICAgcmVzb2x2ZToge1xuICAgICAgICBhbGlhczoge1xuICAgICAgICAgICAgXCJAXCI6IHJlc29sdmUoX19kaXJuYW1lLCBcInJlc291cmNlcy9qc1wiKSxcbiAgICAgICAgICAgIHZ1ZTogXCJ2dWUvZGlzdC92dWUuZXNtLWJ1bmRsZXIuanNcIixcbiAgICAgICAgfSxcbiAgICB9LFxufSk7XG4iXSwKICAibWFwcGluZ3MiOiAiO0FBQXlXLFNBQVMsb0JBQW9CO0FBQ3RZLE9BQU8sU0FBUztBQUNoQixPQUFPLGFBQWE7QUFDcEIsU0FBUyxlQUFlO0FBSHhCLElBQU0sbUNBQW1DO0FBS3pDLElBQU8sc0JBQVEsYUFBYTtBQUFBLEVBQ3hCLE9BQU87QUFBQSxJQUNILFFBQVE7QUFBQSxJQUNSLGFBQWE7QUFBQSxJQUNiLFVBQVU7QUFBQSxJQUNWLGVBQWU7QUFBQSxNQUNYLFFBQVE7QUFBQSxRQUNKLGNBQWM7QUFBQSxVQUNWLFFBQVEsQ0FBQyxPQUFPLGNBQWMsU0FBUyxPQUFPO0FBQUEsUUFDbEQ7QUFBQSxNQUNKO0FBQUEsSUFDSjtBQUFBLEVBQ0o7QUFBQSxFQUNBLFNBQVM7QUFBQSxJQUNMLElBQUk7QUFBQSxJQUNKLFFBQVE7QUFBQSxNQUNKLGlCQUFpQjtBQUFBLE1BQ2pCLGdCQUFnQjtBQUFBLE1BQ2hCLE9BQU87QUFBQSxRQUNILG1DQUFZO0FBQUEsUUFDWixtQ0FBWTtBQUFBLE1BQ2hCO0FBQUEsTUFDQSxTQUFTO0FBQUEsSUFDYixDQUFDO0FBQUEsRUFDTDtBQUFBLEVBQ0EsU0FBUztBQUFBLElBQ0wsT0FBTztBQUFBLE1BQ0gsS0FBSyxRQUFRLGtDQUFXLGNBQWM7QUFBQSxNQUN0QyxLQUFLO0FBQUEsSUFDVDtBQUFBLEVBQ0o7QUFDSixDQUFDOyIsCiAgIm5hbWVzIjogW10KfQo=
