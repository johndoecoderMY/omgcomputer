const preview = document.querySelector('[data-pc-visual]');

if (preview) {
    const canvas = preview.querySelector('[data-pc-canvas]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const initializePreview = async () => {
        try {
            const { initializePcPreview } = await import('./three-pc-scene.js');
            initializePcPreview(preview, canvas, reducedMotion);
        } catch (error) {
            preview.dataset.ready = 'false';
            console.error('The 3D custom PC preview could not be initialized.', error);
        }
    };

    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(initializePreview);
    });
}
