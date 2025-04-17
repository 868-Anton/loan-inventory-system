import preset from "../../../../vendor/filament/filament/tailwind.config.preset";

export default {
    presets: [preset],
    content: ["./resources/**/*.blade.php", "./vendor/filament/**/*.blade.php"],
    theme: {
        extend: {
            // Add any custom theme extensions here
        },
    },
};
