import { createRouter, createWebHistory } from "vue-router";
import type { RouteRecordRaw } from "vue-router";

const routes: RouteRecordRaw[] = [
    {
        path: "/",
        component: () => import("../layouts/GuestLayout.vue"),
        children: [
            {
                path: "install",
                name: "install",
                component: () => import("../pages/Install.vue"),
                meta: {
                    title: "Installation",
                },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory("/admin/tenants"),
    routes,
});

// Update page title
router.beforeEach((to, from, next) => {
    document.title = `${to.meta.title || "TenantAdmin"} - TenantAdmin`;
    next();
});

export default router;
