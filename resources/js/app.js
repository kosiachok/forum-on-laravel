require('bootstrap');
window.Vue = require('vue').default;

import { createApp } from "vue";
import App from './components/App.vue';

const app = createApp(App);
app.mount("#app");
