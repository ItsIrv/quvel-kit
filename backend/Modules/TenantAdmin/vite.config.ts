import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import laravel from "laravel-vite-plugin";
import { resolve } from "path";

export default defineConfig({
    build: {
        outDir: "../../public/build-tenantadmin",
        emptyOutDir: true,
        manifest: "manifest.json",
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ["vue", "vue-router", "pinia", "axios"],
                },
            },
        },
    },
    plugins: [
        vue(),
        laravel({
            publicDirectory: "../../public",
            buildDirectory: "build-tenantadmin",
            input: [
                __dirname + "/resources/js/app.ts",
                __dirname + "/resources/css/app.css",
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            "@": resolve(__dirname, "resources/js"),
            vue: "vue/dist/vue.esm-bundler.js",
        },
    },
});
