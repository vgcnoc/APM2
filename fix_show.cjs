const fs = require('fs');
const path = 'c:/Users/v/Documents/XAMPP/htdocs/Manajement Pelanggan/resources/js/Pages/Customers/Show.vue';

let content = fs.readFileSync(path, 'utf8');

// Replace customer.ont. with customer.ont?. in the template
// but avoid customer.ont?.?. if it was already replaced
content = content.replace(/customer\.ont\.(?!(\?))/g, 'customer.ont?.');
content = content.replace(/props\.customer\.ont\.(?!(\?))/g, 'props.customer.ont?.');

fs.writeFileSync(path, content);
console.log('Done replacing customer.ont.');
