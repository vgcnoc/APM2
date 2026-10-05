const { JSDOM } = require('jsdom');
const http = require('https');

http.get('https://bill.viruzs.my.id/login', (res) => {
    let body = '';
    res.on('data', d => body += d);
    res.on('end', () => {
        const dom = new JSDOM(body, {
            runScripts: "dangerously",
            resources: "usable",
            url: "https://bill.viruzs.my.id/login"
        });
        const window = dom.window;

        window.addEventListener("error", (event) => {
            console.error("DOM ERROR:", event.error || event.message);
        });

        // wait for scripts to load
        setTimeout(() => {
            console.log("Done waiting.");
        }, 3000);
    });
});
