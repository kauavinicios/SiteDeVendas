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
                // Admin (React / Inertia) — o CSS é importado pelo próprio app.tsx
                'resources/js/app.tsx',

                // Loja - global
                'resources/css/shop/index.css',
                'resources/js/shop/index.js',

                // Home
                'resources/css/shop/pages/home.css',

                // Catálogo
                'resources/css/shop/pages/products.css',

                // Produto
                'resources/css/shop/pages/product-show.css',
                'resources/js/shop/product-show.js',

                // Carrinho
                'resources/css/shop/pages/cart.css',
                'resources/js/shop/cart.js',
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
