# Judico Flow

## Step 1: User Requests any URL

## Step 2: Go to Gateway Services

## Step 3:  It will go to api.php of authentication services.
## Step 4: If found then provide api response.
## Step 5: If not found then will go to api gateway controller in api.php

/*
# Autherisation middleware required= 'apiPermission',
Route::middleware(['auth:sanctum'])->group(function () {
    Route::controller(ApiGatewayController::class)->group(function () {
        Route::any('{any}', 'apiGatewayService')->where('any', '.*');
    });
});
*/


## Step 6: Then it will go to api gateway services in APIGatewayController.

    public function apiGatewayService(Request $req)
    {
        try {
            $req->merge(['authRequired' => true]);
            $apiGatewayBll = new ApiGatewayBll;
            return $apiGatewayBll->getApiResponse($req);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }


## Step 7: Then it will call getApiResponse in ApiGatewayBll.

/**
     * | Get api response
     */
    public function getApiResponse($req)
    {
        $client = new Client();
        // Converting environmental variables to Services
        $baseURLs = Config::get('constants.MICROSERVICES_APIS');
        $services = json_decode($baseURLs, true);
        // Sending to Microservices
        $segments = explode('/', $req->path());
        $service = $segments[1];
        if (!array_key_exists($service, $services))
            throw new Exception("Service Not Available");

        $url = $services[$service];
        $ipAddress = getClientIpAddress();

        $authFields = [
            'token' => $req->bearerToken(),
            'ipAddress' => $ipAddress
        ];

        if ($req->authRequired) {                           // Auth Required fields
            $authFields = array_merge([
                'auth' => authUser(),
                'token' => $req->bearerToken(),
                'currentAccessToken' => $req->user()->currentAccessToken(),
                'apiToken' => $req->user()->currentAccessToken()->token,
                'ipAddress' => $ipAddress
            ]);
        }

        $req = $req->merge($authFields);                    // Merging authenticated fields

        $method = $req->method();
        $promises = [];
        $asyncMethod = in_array($method, ['POST', 'post']) ? 'postAsync' : 'getAsync';

        if (isset($req->header()['content-type']) && preg_match('/multipart/i', $req->header()["content-type"][0]) && $_FILES) {
            $promise = $client->$asyncMethod($url . $req->getRequestUri(), [                // for Multipart
                'multipart' => $this->prepareMultipartData($req),
                [
                    'headers' => $req->header()                         // Attach all headers
                ]
            ]);
        } else {
            $promise = $client->$asyncMethod(
                $url . $req->getRequestUri(),
                [
                    'json' => $req->all(),
                    [
                        'headers' => $req->header()                         // Attach all headers
                    ]
                ]
            );
        }
        // Create an async HTTP POST request
        $promises[] = $promise;
        // Wait for the promise to complete
        $responses = Promise\Utils::settle($promises)->wait();
        // Process the response
        $response = $responses[0];

        // return ($req['auth']);
        if ($response['state'] === Promise\PromiseInterface::FULFILLED) {
            $apiResponse = $response['value']->getBody()->getContents();    // Process the response body as needed
            return json_decode($apiResponse, true);
        } else {
            $apiResponse = $response['reason']->getMessage();            // Handle the error message as needed
            return $apiResponse;
        }
    }


## Step 8: This function include microservices api in config ->constants.php

return [
    "MICROSERVICES_APIS"   => env('MICROSERVICES_APIS'),
    "CUSTOM_RELATIVE_PATH" => "Uploads/Custom",
    "DOC_URL"              => env('DOC_URL'),

    #_Module Constants
    "PROPERTY_MODULE_ID"      => 1,
    "WATER_MODULE_ID"         => 2,
    "TRADE_MODULE_ID"         => 3,
    "SWM_MODULE_ID"           => 4,
    "ADVERTISEMENT_MODULE_ID" => 5,
    "WATER_TANKER_MODULE_ID"  => 11,

    "USER_TYPE" => [
        "Admin",
        "Employee",
        "JSK",
        "TC",
        "TL",
        "Pseudo User",
    ],

];


## Step 9: Microservices works based on env of egov backend.

APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:V2omu0za7GbP8Op48CE2Ofuo5MoH3wZYlCDXkI2iyjI=
APP_DEBUG=true
APP_URL=192.168.0.211:8000
DOC_URL = http://192.168.0.211:8000		->	 This is system ip of particular developer

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# DB_CONNECTION=pgsql
# DB_HOST=192.168.0.10
# DB_PORT=5432
# DB_DATABASE=db_juidco
# DB_USERNAME=postgres
# DB_PASSWORD='#Central@2023'

#_Masters
DB_MASTER_CONNECTION=pgsql
DB_MASTER_HOST=192.168.0.10		->		This is stage database server
DB_MASTER_PORT=5432
DB_MASTER_DATABASE=juidco_masters
DB_MASTER_USERNAME=postgres
DB_MASTER_PASSWORD='#Central@2023?'	 ->	 Password for stage db 

# #_Property
# DB_CONNECTION=pgsql
# DB_HOST=192.168.0.10
# DB_PORT=5432
# DB_DATABASE=juidco_property
# DB_USERNAME=postgres
# DB_PASSWORD='#Central@2023?'

# DB_CONNECTION=pgsql
# DB_HOST= 127.0.0.1 # 192.168.0.95		->  This is production database sserver
# DB_PORT=5432
# DB_DATABASE= juidco_property #juidco3 #ulbMaster juidco_masters
# DB_USERNAME=postgres
# DB_PASSWORD= '1234' #'Perfect@#@##)@?'	->	Password for prod db

#_Property
DB_CONNECTION=pgsql
DB_HOST=192.168.0.10
DB_PORT=5432
DB_DATABASE=juidco_property
DB_USERNAME=postgres
DB_PASSWORD='#Central@2023?'

#_Water
DB_WATER_CONNECTION=pgsql
DB_WATER_HOST=192.168.0.10
DB_WATER_PORT=5432
DB_WATER_DATABASE=juidco_water
DB_WATER_USERNAME=postgres
DB_WATER_PASSWORD='#Central@2023?'

#_Trade
DB_TRADE_CONNECTION=pgsql
DB_TRADE_HOST=192.168.0.10
DB_TRADE_PORT=5432
DB_TRADE_DATABASE=juidco_trade
DB_TRADE_USERNAME=postgres
DB_TRADE_PASSWORD='#Central@2023?'

# DB_TRADE_CONNECTION=pgsql
# DB_TRADE_HOST= 192.168.0.95
# DB_TRADE_PORT=5432
# DB_TRADE_DATABASE= juidco_trade #juidco3 #ulbMaster juidco_masters
# DB_TRADE_USERNAME=postgres
# DB_TRADE_PASSWORD= 'Perfect@#@##)@?'

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DRIVER=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

RAZORPAY_ID = "rzp_test_3MPOKRI8WOd54p"
RAZORPAY_KEY = "k23OSfMevkBszuPY5ZtZwutU"

	

