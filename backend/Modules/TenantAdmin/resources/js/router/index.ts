import { createRouter, createWebHistory } from "vue-router";
import type { RouteRecordRaw } from "vue-router";

const routes: RouteRecordRaw[] = [
    {
        path: "/",
        redirect: "/dashboard",
    },
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
                    requiresGuest: true,
                },
            },
            {
                path: "login",
                name: "login",
                component: () => import("../pages/Login.vue"),
                meta: {
                    title: "Login",
                    requiresGuest: true,
                },
            },
        ],
    },
    {
        path: "/",
        component: () => import("../layouts/DashboardLayout.vue"),
        children: [
            {
                path: "dashboard",
                name: "dashboard",
                component: () => import("../pages/Dashboard.vue"),
                meta: {
                    title: "Dashboard",
                    requiresAuth: true,
                },
            },
            // Add more authenticated routes here
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
