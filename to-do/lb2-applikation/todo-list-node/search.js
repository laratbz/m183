const axios = require('axios');

async function getHtml(req) {
    if (!req.body.provider || !req.body.terms){
        return "Missing data";
    }

    const allowedProviders = ['/search/v2/'];

    if (!allowedProviders.includes(req.body.provider)) {
        return "Invalid provider";
    }

    const provider = req.body.provider;
    const terms = encodeURIComponent(req.body.terms);

    // 🔒 FIX: userid aus SESSION
    const userid = encodeURIComponent(req.session.userid);

    const url = 'http://localhost:3000' + provider + '?userid=' + userid + '&terms=' + terms;

    try {
        const response = await axios.get(url);
        return response.data;
    } catch {
        return "No results";
    }
}

module.exports = { html: getHtml };