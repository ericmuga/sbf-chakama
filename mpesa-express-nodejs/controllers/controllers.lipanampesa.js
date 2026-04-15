const request = require("request");
require('dotenv/config');
const { getTimestamp } = require("../utils/utils.timestamp.js");
const ngrok = require("ngrok");

const sandboxBaseUrl = "https://sandbox.safaricom.co.ke/mpesa";
const apiBaseUrl = "https://api.safaricom.co.ke/mpesa";

const baseUrl = process.env.MPESA_ENV === '1' ? apiBaseUrl : sandboxBaseUrl;

let ngrokUrl = null;

// Initialize ngrok tunnel with retry and fallback
const initializeNgrok = async () => {
    if (!ngrokUrl) {
        try {
            // First, try to kill any existing ngrok processes
            await ngrok.kill().catch(() => {}); // Ignore errors when killing
            
            // Wait a moment before starting new tunnel
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            ngrokUrl = await ngrok.connect({
                proto: 'http',
                addr: parseInt(process.env.PORT),
                authtoken: process.env.NGROK_AUTHTOKEN,
                region: 'us'
            });
            console.log(`ngrok tunnel established at ${ngrokUrl}`);
        } catch (error) {
            console.error("Error starting ngrok:", error);
            
            // For development, use a public URL service or localhost
            console.warn("⚠️  ngrok failed. Using development fallback URL");
            console.warn("📝 For production, consider using a static public URL or webhook.site");
            
            // Use webhook.site as a temporary solution for testing
            // Go to https://webhook.site/ and get a unique URL
            ngrokUrl = "https://webhook.site/12345678-1234-1234-1234-123456789012"; // Replace with your webhook.site URL
            
            console.log(`Using fallback callback URL: ${ngrokUrl}`);
        }
    }
    return ngrokUrl;
};

const retryRequest = (options, retries = 3) => {
    return new Promise((resolve, reject) => {
        const attemptRequest = (retryCount) => {
            request(options, (error, response, body) => {
                if (error) {
                    if (retryCount > 0) {
                        console.log(`Retrying request... (${retries - retryCount + 1}/${retries})`);
                        return attemptRequest(retryCount - 1);
                    } else {
                        return reject(error);
                    }
                } else {
                    resolve({ response, body });
                }
            });
        };
        attemptRequest(retries);
    });
};

// @desc initiate stk push
// @method POST
// @route /stkPush
// @access public
const initiateSTKPush = async (req, res) => {
    try {
        console.log('Request body:', req.body);
        console.log('Request headers:', req.headers);
        
        if (!req.body) {
            return res.status(400).json({
                message: "Request body is missing",
                error: "No data received"
            });
        }
        
        const { amount, phone, store_number, till_number, store_name } = req.body;
        
        // Validate required fields
        if (!amount || !phone || !till_number || !store_name) {
            return res.status(400).json({
                message: "Missing required fields",
                error: "amount, phone, till_number, and store_name are required",
                received: { amount, phone, store_number, till_number, store_name }
            });
        }
        const Order_ID = '1234';
        const url = `${baseUrl}/stkpush/v1/processrequest`;
        const auth = "Bearer " + req.safaricom_access_token;

        const timestamp = getTimestamp();
        const password = Buffer.from(process.env.BUSINESS_SHORT_CODE + process.env.PASS_KEY + timestamp).toString('base64');

        const callback_url = await initializeNgrok();

        let transactionType = 'CustomerBuyGoodsOnline';
        if (till_number == '831659') {
            transactionType = 'CustomerPayBillOnline';
        }

        console.log(`Using callback URL: ${callback_url}/api/stkPushCallback/${Order_ID}`);

        const options = {
            url: url,
            method: "POST",
            headers: {
                "Authorization": auth
            },
            json: {
                "BusinessShortCode": process.env.BUSINESS_SHORT_CODE,
                "Password": password,
                "Timestamp": timestamp,
                "TransactionType": transactionType,
                "Amount": amount,
                "PartyA": phone,
                "PartyB": till_number,
                "PhoneNumber": phone,
                "CallBackURL": `${callback_url}/api/stkPushCallback/${Order_ID}`,
                "AccountReference": store_name.substring(0, 12),
                "TransactionDesc": "Paid online"
            }
        };

        const { response, body } = await retryRequest(options);

        res.status(response.statusCode).json(body);
    } catch (e) {
        console.error("Error while trying to create LipaNaMpesa details", e);
        res.status(503).send({
            message: "Something went wrong while trying to create LipaNaMpesa details. Contact IT",
            error: e.message
        });
    }
};

// @desc callback route Safaricom will post transaction status
// @method POST
// @route /stkPushCallback/:Order_ID
// @access public
const stkPushCallback = async (req, res) => {
    try {
        const { Order_ID } = req.params;

        const {
            MerchantRequestID,
            CheckoutRequestID,
            ResultCode,
            ResultDesc,
            CallbackMetadata
        } = req.body.Body.stkCallback;

        const meta = Object.values(await CallbackMetadata.Item);
        const PhoneNumber = meta.find(o => o.Name === 'PhoneNumber').Value.toString();
        const Amount = meta.find(o => o.Name === 'Amount').Value.toString();
        const MpesaReceiptNumber = meta.find(o => o.Name === 'MpesaReceiptNumber').Value.toString();
        const TransactionDate = meta.find(o => o.Name === 'TransactionDate').Value.toString();

        console.log("-".repeat(20), " OUTPUT IN THE CALLBACK ", "-".repeat(20));
        console.log(`
            Order_ID : ${Order_ID},
            MerchantRequestID : ${MerchantRequestID},
            CheckoutRequestID: ${CheckoutRequestID},
            ResultCode: ${ResultCode},
            ResultDesc: ${ResultDesc},
            PhoneNumber : ${PhoneNumber},
            Amount: ${Amount},
            MpesaReceiptNumber: ${MpesaReceiptNumber},
            TransactionDate : ${TransactionDate}
        `);

        res.json(true);

    } catch (e) {
        console.error("Error while trying to update LipaNaMpesa details from the callback", e);
        res.status(503).send({
            message: "Something went wrong with the callback",
            error: e.message
        });
    }
};

// @desc Check from safaricom servers the status of a transaction
// @method GET
// @route /confirmPayment/:CheckoutRequestID
// @access public
const confirmPayment = async (req, res) => {
    try {
        const url = `${baseUrl}/stkpushquery/v1/query`;
        const auth = "Bearer " + req.safaricom_access_token;

        const timestamp = getTimestamp();
        const password = Buffer.from(process.env.BUSINESS_SHORT_CODE + process.env.PASS_KEY + timestamp).toString('base64');

        const options = {
            url: url,
            method: "POST",
            headers: {
                "Authorization": auth
            },
            json: {
                "BusinessShortCode": process.env.BUSINESS_SHORT_CODE,
                "Password": password,
                "Timestamp": timestamp,
                "CheckoutRequestID": req.params.CheckoutRequestID
            }
        };

        const { response, body } = await retryRequest(options);

        res.status(response.statusCode).json(body);
    } catch (e) {
        console.error("Error while trying to create LipaNaMpesa details", e);
        res.status(503).send({
            message: "Something went wrong while trying to create LipaNaMpesa details. Contact admin",
            error: e.message
        });
    }
};

module.exports = {
    initiateSTKPush,
    stkPushCallback,
    confirmPayment
};
