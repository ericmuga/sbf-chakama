const request = require("request");
require('dotenv/config');

const sandboxBaseUrl = "https://sandbox.safaricom.co.ke";
const apiBaseUrl = "https://api.safaricom.co.ke";

const baseUrl = process.env.MPESA_ENV == '1' ? apiBaseUrl : sandboxBaseUrl;

const accessToken = (req, res, next) => {
    try {

        const url = `${baseUrl}/oauth/v1/generate?grant_type=client_credentials`;
        const auth = new Buffer.from(`${process.env.MPESA_CONSUMER_KEY}:${process.env.MPESA_CONSUMER_SECRET}`).toString('base64');

        request(
            {
                url: url,
                headers: {
                    "Authorization": "Basic " + auth
                }
            },
            (error, response, body) => {
                if (error) {
                    res.status(401).send({
                        "message": 'Something went wrong when trying to process Auth token',
                        "error": error.message
                    })
                }
                else {
                    req.safaricom_access_token = JSON.parse(body).access_token
                    console.log('access: '+ req.safaricom_access_token)
                    next()
                }
            }
        )
    } catch (error) {

        console.error("Access token error ", error)
        res.status(401).send({
            "message": 'Something went wrong when trying to process Auth token',
            "error": error.message
        })
    }

}

module.exports = {
    accessToken,
};
