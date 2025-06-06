import { createRouter, createWebHistory } from "vue-router";
import type { RouteRecordRaw } from "vue-router";
import { useAuthStore } from "../stores/useAuthStore";

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
            {
                path: "tenants/:id/edit",
                name: "tenant-edit",
                component: () => import("../pages/TenantEdit.vue"),
                meta: {
                    title: "Edit Tenant",
                },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory("/admin/tenants"),
    routes,
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Update page title
    document.title = `${to.meta.title || "TenantAdmin"} - TenantAdmin`;

    // Check if route requires authentication
    if (to.meta.requiresAuth) {
        // Check authentication status
        const isAuthenticated = await authStore.checkAuth();

        if (!isAuthenticated) {
            // Redirect to login
            return next({ name: "login", query: { redirect: to.fullPath } });
        }
    }

    // Check if route requires guest (not authenticated)
    if (to.meta.requiresGuest) {
        const isAuthenticated = await authStore.checkAuth();

        if (isAuthenticated) {
            // Redirect to dashboard
            return next({ name: "dashboard" });
        }
    }

    next();
});

export default router;
