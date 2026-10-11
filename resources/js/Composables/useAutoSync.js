import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Auto sinkron data halaman Inertia secara berkala (partial reload).
 * - Hanya me-reload props tertentu (`only`)
 * - Berhenti sementara saat tab tidak aktif atau saat `isPaused()` bernilai true (mis. modal terbuka)
 * - Langsung sinkron saat tab kembali aktif
 */
export function useAutoSync(only, { interval = 10000, isPaused = () => false } = {}) {
    const lastSynced = ref(new Date());
    const syncing = ref(false);
    let timer = null;

    const sync = (force = false) => {
        if (syncing.value) return;
        if (!force && (document.hidden || isPaused())) return;
        syncing.value = true;
        router.reload({
            only,
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                syncing.value = false;
                lastSynced.value = new Date();
            },
        });
    };

    const onVisible = () => {
        if (!document.hidden) sync();
    };

    onMounted(() => {
        timer = setInterval(() => sync(), interval);
        document.addEventListener('visibilitychange', onVisible);
        window.addEventListener('focus', onVisible);
    });

    onBeforeUnmount(() => {
        clearInterval(timer);
        document.removeEventListener('visibilitychange', onVisible);
        window.removeEventListener('focus', onVisible);
    });

    return { lastSynced, syncing, sync };
}
