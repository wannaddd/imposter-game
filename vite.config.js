import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";
import os from "node:os";

function getLocalIP() {
    const interfaces = os.networkInterfaces();

    const adapters = Object.entries(interfaces).sort(([a], [b]) => {
        const priority = (name) =>
            /wi-fi|wifi|wireless|ethernet/i.test(name) ? 0 : 1;

        return priority(a) - priority(b);
    });

    for (const [, addresses] of adapters) {
        for (const address of addresses ?? []) {
            if (
                address.family === "IPv4" &&
                !address.internal &&
                !address.address.startsWith("169.254.")
            ) {
                return address.address;
            }
        }
    }

    return "127.0.0.1";
}

const localIP = getLocalIP();

console.log(`Laravel: http://${localIP}:8000`);
console.log(`Vite: http://${localIP}:5173`);

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/js/app.js"],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],

    server: {
        host: "0.0.0.0",
        port: 5173,
        strictPort: true,

        origin: `http://${localIP}:5173`,

        // Allow Laravel to load assets from Vite.
        cors: {
            origin: [
                `http://${localIP}:8000`,
                "http://localhost:8000",
                "http://127.0.0.1:8000",
                "http://imposter-game.test",
            ],
        },

        hmr: {
            host: localIP,
            protocol: "ws",
            clientPort: 5173,
        },
    },
});
