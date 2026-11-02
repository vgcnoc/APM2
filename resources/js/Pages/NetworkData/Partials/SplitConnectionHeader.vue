<template>
    <div class="split-connection">
        <div class="split-connection__heading">
            <div>
                <p class="split-connection__eyebrow">Jaringan {{ kind }} · Split</p>
                <p class="split-connection__title">Hubungan Induk → Lanjutan</p>
            </div>
            <div class="split-connection__summary"><slot /></div>
        </div>
        <div class="split-connection__nodes">
            <div
                v-for="node in nodes"
                :key="node.id"
                class="split-connection__node"
                :class="node.role === 'induk' ? 'split-connection__node--parent' : 'split-connection__node--child'"
            >
                <span class="split-connection__dot" aria-hidden="true"></span>
                <div class="split-connection__identity">
                    <span class="split-connection__role">{{ node.role === 'induk' ? 'Unit Induk' : 'Unit Lanjutan' }}</span>
                    <p class="split-connection__name">{{ node.name }}</p>
                    <p class="split-connection__meta">{{ node.detail }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    kind: { type: String, required: true },
    nodes: { type: Array, required: true },
});
</script>

<style scoped>
.split-connection {
    padding: 12px;
    border: 1px solid var(--border-dark, #e2e8f0);
    border-radius: 12px;
    background: var(--bg-dark-card, #fff);
    color: var(--split-text, #1e293b);
}
.split-connection__heading { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
.split-connection__eyebrow { margin: 0 0 4px; color: var(--split-muted, #64748b); font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
.split-connection__title { margin: 0; font-size: 14px; font-weight: 750; }
.split-connection__summary { display: flex; flex-wrap: wrap; gap: 8px; font-size: 12px; }
.split-connection__nodes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.split-connection__node { display: flex; align-items: flex-start; gap: 10px; min-width: 0; padding: 10px 12px; border: 1px solid var(--border-dark, #e2e8f0); border-left: 3px solid var(--split-accent); border-radius: 8px; background: var(--split-node-bg); }
.split-connection__node--parent { --split-accent: #34d399; --split-node-bg: #163e36; }
.split-connection__node--child { --split-accent: #c084fc; --split-node-bg: #3d205a; }
.split-connection__dot { width: 8px; height: 8px; margin-top: 4px; flex-shrink: 0; border-radius: 50%; background: var(--split-accent); }
.split-connection__identity { min-width: 0; }
.split-connection__role { color: #e2e8f0; font-size: 10px; font-weight: 750; text-transform: uppercase; letter-spacing: .05em; }
.split-connection__name { margin: 4px 0; color: #fff !important; font-size: 13px; line-height: 1.5; font-weight: 750; overflow-wrap: anywhere; }
.split-connection__meta { margin: 0; font-size: 11px; color: #cbd5e1; }
:global(html.dark) .split-connection { --split-text: #f1f5f9; --split-muted: #c4b5d4; }
@media (max-width: 480px) { .split-connection { padding: 12px; } }
</style>
