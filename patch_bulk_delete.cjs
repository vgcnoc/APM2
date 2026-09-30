const fs = require('fs');
const path = require('path');

const baseDir = path.join(__dirname, 'resources', 'js', 'Pages', 'Customers');
const files = ['Installed.vue', 'Activation.vue', 'Active.vue', 'Index.vue'];

files.forEach(file => {
    let filePath = path.join(baseDir, file);
    if (!fs.existsSync(filePath)) {
        console.log(`Skipping ${file} - not found`);
        return;
    }
    
    let content = fs.readFileSync(filePath, 'utf8');
    
    // 1. Modify <DataTable>
    if (!content.includes('v-model:selected="selectedIds"')) {
        content = content.replace(
            /(<DataTable[\s\S]*?:searchRoute="[^"]*?")(\s*>)/,
            '$1\n            selectable\n            v-model:selected="selectedIds"$2'
        );
    }
    
    // 2. Add <template #actions>
    if (!content.includes('<template #actions>')) {
        const actionTemplate = `
            <template #actions>
                <div class="flex items-center gap-2">
                    <button v-if="canDelete && selectedIds.length > 0" 
                        @click="bulkDelete" 
                        class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-medium text-sm rounded-lg transition-colors border border-red-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Terpilih ({{ selectedIds.length }})
                    </button>
                </div>
            </template>
            
            <template #row="{ row }">`;
        
        content = content.replace(/<template #row="\{ row \}">/, actionTemplate);
    }
    
    // 3. Script block setup
    if (!content.includes('function bulkDelete()')) {
        // Need to add computed and usePage to imports if not there
        if (!content.includes('computed')) {
            content = content.replace(/import \{([^}]+)\} from 'vue';/, (match, p1) => {
                if (p1.includes('computed')) return match;
                return `import {${p1}, computed} from 'vue';`;
            });
        }
        if (!content.includes('usePage')) {
            content = content.replace(/import \{([^}]+)\} from '@inertiajs\/vue3';/, (match, p1) => {
                if (p1.includes('usePage')) return match;
                return `import {${p1}, usePage} from '@inertiajs/vue3';`;
            });
        }
        
        const scriptLogic = `
const page = usePage();
const selectedIds = ref([]);

const canDelete = computed(() => {
    try {
        if (!page || !page.props || !page.props.auth || !page.props.auth.user) return false;
        const user = page.props.auth.user;
        if (user.role && typeof user.role === 'string') {
            const roleStr = user.role.toLowerCase().trim();
            if (roleStr === 'admin' || roleStr === 'super admin' || roleStr.includes('admin')) return true;
        }
        let roles = [];
        if (Array.isArray(user.roles)) roles = user.roles;
        else if (user.roles) roles = Object.values(user.roles);
        for (let r of roles) {
            if (typeof r === 'string') {
                const rStr = r.toLowerCase().trim();
                if (rStr === 'admin' || rStr === 'super admin' || rStr.includes('admin')) return true;
            }
        }
        let perms = [];
        if (Array.isArray(user.permissions)) perms = user.permissions;
        else if (user.permissions) perms = Object.values(user.permissions);
        return perms.includes('menu_customers_survey') || perms.includes('customers_survey_delete') || perms.includes('customers_delete');
    } catch (e) {
        return false;
    }
});

function bulkDelete() {
    if (confirm(\`Hapus \${selectedIds.value.length} data terpilih secara permanen?\`)) {
        router.post('/customers/bulk-destroy', { ids: selectedIds.value }, {
            preserveScroll: true,
            onSuccess: () => selectedIds.value = []
        });
    }
}
`;
        // Find const props = defineProps(...); and insert after it
        content = content.replace(/(const props = defineProps\([^;]+;\s*)/, `$1\n${scriptLogic}\n`);
    }

    fs.writeFileSync(filePath, content);
    console.log(`Updated ${file}`);
});
