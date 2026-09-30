const fs = require('fs');
const path = require('path');

const baseDir = path.join(__dirname, 'resources', 'js', 'Pages', 'Customers');
const files = ['Installed.vue', 'Activation.vue', 'Active.vue', 'Index.vue'];

files.forEach(file => {
    let filePath = path.join(baseDir, file);
    if (!fs.existsSync(filePath)) return;
    
    let content = fs.readFileSync(filePath, 'utf8');
    
    if (!content.includes('v-model:selected="selectedIds"')) {
        content = content.replace(
            /searchRoute="[^"]*"/,
            `$&
            selectable
            v-model:selected="selectedIds"`
        );
        fs.writeFileSync(filePath, content);
        console.log(`Patched DataTable in ${file}`);
    }
});
