const fetch = require("node-fetch");
const defaultHeaders = {};
const baseUrl = 'http://127.0.0.1:8322';

/**
 *
 * @param uri {string}
 * @param params {object}
 * @returns {Promise<T>}
 */
function httpPost(uri, params= {}) {
    return fetch(baseUrl + uri, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...defaultHeaders
        },
        body: JSON.stringify(params),
    })
        .then((response) => {


            if (response.status !== 200) {
                response.text().then(v => {
                    console.error(v);
                });

                return Promise.reject('Error from server. HTTP code: ' + response.status)
            }

            return response.json();
        })
}

module.exports = {
    httpPost
}
