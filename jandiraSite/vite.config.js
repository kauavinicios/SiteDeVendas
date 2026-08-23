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
                'resources/css/app.css',
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
                'resources/js/pages/product-show.js',

                // Carrinho
                'resources/css/pages/cart.css',
                'resources/js/pages/cart.js',
            ],
            ssr: 'resources/js/ssr.jsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    esbuild: {
        jsx: 'automatic',
    },
});