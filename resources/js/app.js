import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import VueKonva from 'vue-konva';
import {createRouter, createWebHistory} from "vue-router";
import WhiteboardSelectot from "./components/WhiteboardSelectot.vue";
import Whiteboard from "./components/Whiteboard.vue";

const routes = [
    { path: '/', component: WhiteboardSelectot },
    { path: '/whiteboard/:pass', component: Whiteboard }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

const app = createApp(App)
app.use(router)
app.use(VueKonva)
app.mount('#app');



