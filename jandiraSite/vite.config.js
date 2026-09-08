import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import {
    defineConfig
} from 'vite';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // React / Inertia
                'resources/css/admin/app.css',
                'resources/js/app.tsx',

                // Loja - global
                'resources/css/index.css',
                'resources/js/index.js',

                // Home
                'resources/css/pages/home.css',

                // Catálogo
                'resources/css/pages/products.css',

                // Produto
                'resources/css/pages/product-show.css',
                'resources/js/store/product-show.js',

                // Carrinho
                'resources/css/pages/cart.css',
                'resources/js/store/cart.js',
            ],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    esbuild: {
        jsx: 'automatic',
    },
});