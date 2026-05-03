const axios = require('axios');

async function getHtml(req) {
    if (!req.body.provider || !req.body.terms || !req.body.userid){
        return "Not enough information provided";
    }

    const allowedProviders = ['/search/v2/'];

    if (!allowedProviders.includes(req.body.provider)) {
        return "Invalid provider";
    }

    let provider = req.body.provider;
    let terms = encodeURIComponent(req.body.terms);
    let userid = encodeURIComponent(req.body.userid);

    await sleep(1000);

    let theUrl='http://localhost:3000'+provider+'?userid='+userid+'&terms='+terms;
    let result = await callAPI('GET', theUrl, false);
    return result;
}

async function callAPI(method, url){
    let noResults = 'No results found!';

    try {
        const response = await axios.get(url);
        return response.data;
    } catch {
        return noResults;
    }
}

function sleep(ms) {
    return new Promise((resolve) => {
        setTimeout(resolve, ms);
    });
}

module.exports = { html: getHtml };