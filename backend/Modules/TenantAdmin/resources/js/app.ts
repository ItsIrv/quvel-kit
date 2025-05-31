import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router";
import PrimeVue from "primevue/config";
import Aura from "@primeuix/themes/aura";

// Import styles
import "primeicons/primeicons.css"; // Icons
import "../css/app.css"; // Custom CSS (should be last)

// Import PrimeVue directives
import Tooltip from "primevue/tooltip";
import BadgeDirective from "primevue/badgedirective";
import Ripple from "primevue/ripple";
import ToastService from 'primevue/toastservice';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: ".dark",
        },
    },
    ripple: true,
});
app.use(ToastService);

// Register directives
app.directive("tooltip", Tooltip);
app.directive("badge", BadgeDirective);
app.directive("ripple", Ripple);

app.mount("#app");
