import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: [
                path.resolve(__dirname, 'resources/js/app.js'), // ajuste o caminho se necessário
                path.resolve(__dirname, 'resources/css/app.css') // se você tiver arquivos CSS
            ],
        }),
    ],
});
