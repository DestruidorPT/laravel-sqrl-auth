Laravel SQRL Authentication <img align="right" width="100" height="100" src="https://sqrl.grc.com/image/100x100%20SQRL%20Logo.png">
=======================

[![Issues](https://img.shields.io/github/issues/DestruidorPT/laravel-sqrl-auth?style=flat)](https://github.com/DestruidorPT/laravel-sqrl-auth/issues)
[![Stars](https://img.shields.io/github/stars/DestruidorPT/laravel-sqrl-auth?style=flat)](https://github.com/DestruidorPT/laravel-sqrl-auth/stargazers)
[![License](https://img.shields.io/github/license/DestruidorPT/laravel-sqrl-auth?style=flat)](https://github.com/DestruidorPT/laravel-sqrl-auth/blob/master/LICENSE)

- [Introduction](#introduction)
- [SQRL versions supported](#sqrl-versions-supported)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configure Log System](#configure-log-system)
- [Details to get the project working with full functionality](#details-to-get-the-project-working-with-full-functionality)
  - [SQRL Authentication](#sqrl-authentication)
  - [SQRL Question](#sqrl-question)
  - [IP Address Verification](#ip-address-verification)
  - [SQRL Disabled](#sqrl-disabled)
  - [SQRL Only Allowed](#sqrl-only-allowed)
  - [SQRL Hardlock](#sqrl-hardlock)
- [Example Instalation](#example-instalation)
- [Classes And Data](#classes-and-data)
  - [Sqrl_nonce](#sqrl_nonce-destruidorptlaravelsqrlauthappsqrl_nonce)
  - [Sqrl_pubkey](#sqrl_pubkey-destruidorptlaravelsqrlauthappsqrl_pubkey)
- [Functions Availables](#functions-availables)
  - [SQRLController](#sqrlcontroller-destruidorptlaravelsqrlauthapphttpcontrollerssqrlsqrlcontroller)
    - [Function to Generate Authenticated Nonce](#function-to-generate-authenticated-nonce)
    - [Function to Generate Question Nonce](#function-to-generate-question-nonce)
    - [Function to Check If User Can Auth By Normal Login](#function-to-check-if-user-can-auth-by-normal-login)
    - [Function to Check If User Can Use Recover Password](#function-to-check-if-user-can-use-recover-password)
    - [Function to Check If User Can Auth By SQRL](#function-to-check-if-user-can-auth-by-sqrl)
    - [Function to Check If The Nonce Is Ready](#function-to-check-if-the-nonce-is-ready)
    - [Function to Get User By Original Nonce If Can Be Authenticated](#function-to-get-user-by-original-nonce-if-can-be-authenticated)
  - [SQRLControllerAPI](#sqrlcontrollerapi-destruidorptlaravelsqrlauthapphttpcontrollerssqrlsqrlcontrollerapi)
    - [API Function to SQRL](#api-function-to-sqrl)
    - [API Function to Check If The Nonce Is Ready](#api-function-to-check-if-the-nonce-is-ready)
- [Demo](#demo)
- [Contacts](#contacts)

# Introduction
SQRL(Secure, Quick, Reliable Login) is a draft open standard for anonymous and secure user identification and authentication to websites and web applications, designed to eliminate username and password authentication to remote websites. Users need only to provide one password to unlock their signing keys, which are stored locally on their device and never disclosed to any website. The password is verified locally on the device that stores the signing keys.

Laravel is a free, open-source PHP web framework, created by Taylor Otwell and intended for the development of web applications following the model–view–controller (MVC) architectural pattern and based on Symfony. One of the features of Laravel is a modular packaging system with a dedicated dependency manager.

The following project consists of a laravel module designed to integrate SQRL authentication system to any laravel project.

# SQRL versions supported
List of SQRL versions supported in this package and the features that were developed.
- [SQRL version 1](#sqrl-version-1)

We recommend reading these documents:
- [SQRL Explained](https://www.grc.com/sqrl/sqrl_explained.pdf)
- [SQRL Operating](https://www.grc.com/sqrl/sqrl_operating_details.pdf)
- [SQRL Cryptography](https://www.grc.com/sqrl/sqrl_cryptography.pdf)
- [SQRL On The Wire (Strongly Recommended)](https://www.grc.com/sqrl/sqrl_on_the_wire.pdf)

## SQRL version 1
- [x] [SQRL Authentication](#sqrl-authentication);
- [x] [SQRL Question](#sqrl-question) (Make question by SQRL features);
- [x] [IP Address Verification](#ip-address-verification);
- [x] [SQRL Disabled](#sqrl-disable) (Disable all SQRL on User Account, if SQRL Client says so);
- [x] [SQRL Only Allowed](#sqrl-only-allowed) (Block any normal login and allow only by SQRL Authentication, if SQRL Client says so);
- [x] [SQRL Hardlock](#sqrl-hardlock) (Block any type of recory password or account, if SQRL Client says so);

# Requirements
 - PHP >= 7.2.0
 - Laravel >= 6.0.0

# Installation
First, install laravel , and make sure that the database connection settings are correct.

```
composer require destruidorpt/laravel-sqrl-auth
```

Then run this command to create the necessary tables：

```
php artisan migrate
```

Add the following lines to this file `.env`.

```
APP_URL=https://sqrl.test               # This one already exists in the .env file is the URL of your aplicacion 

SQRL_KEY_DOMAIN=sqrl.test               # URL to yours SQRL Server without http:// and https://
SQRL_ROUTE_TO_SQRL_AUTH=/api/sqrl       # Route to SQRL Server API, it must be pointed to the controller `SQRLControllerAPI` in the function `sqrl()`
SQRL_URL_LOGIN=https://sqrl.test/login  # URL to your login page
SQRL_NONCE_MAX_AGE_MINUTES=5            # Max age in minutes of the valid nonce
SQRL_NONCE_SALT=RANDOM                  # Generate a random salt value to calculate the nonce
```

Verify that the csrf token is not being verified in the route configured in `SQRL_ROUTE_TO_SQRL_AUTH` (file `.env`), can be disabled in `app/Http/Middleware/VerifyCsrfToken.php` with the variable `$except` by adding the information in SQRL_ROUTE_TO_SQRL_AUTH. 
(If it's not disabled, the SQRL Client will not be able to communicate with the SQRL Server).

The next step is to copy the routes below to `routes/api.php` and past the route in the `SQRL_ROUTE_TO_SQRL_AUTH` (file `.env`) in this case, below the route the value will be `SQRL_ROUTE_TO_SQRL_AUTH=/api/sqrl`.

```
Route::group(['namespace'=>'\DestruidorPT\LaravelSQRLAuth\App\Http\Controllers'], function() {
    Route::post('/sqrl', 'SQRL\SQRLControllerAPI@sqrl');                # Route of API SQRL
});
```
Currently Laravel has a limitation for API calls per user, if your Laravel project locks API calls, consider tinkering with the following file:
```
App\Http\Kernel.php
```
And edit in `$middlewareGroups` the value `throttle:60,1` of `api`, if you don t want to laravel lock the API calls comment `throttle:60,1`.
```
protected $middlewareGroups = [
        'api' => [
            'throttle:60,1', #edit this value 
            'bindings',
        ],
    ];
```

<h3>Important notice when developing the project, SQRL is only ready to work with https, which means you must have the certificates working.</h3> 


# Configure Log System
This configuration is optional, but strongly recommended for debug purposes in case of any problems.
If you want to register or log the information between SQRL server and the SQRL client, this is good for debug purposes, follow the steps below.

Put the code below in the file `config\logging.php` on the array `channels`, this will separate the log file per day. 
```
'LaravelSQRLAuth' => [
    'driver' => 'daily',
    'path' => storage_path('logs/LaravelSQRLAuth/' . date('Y/m/') . 'sqrl.log'), // add dynamic folder structure
    'level' => 'debug',
    'days' => 31, // set the maximum number of days in a month
]
```

# Details to get the project working with full functionality
Here we will talk about how to apply all the available features gradually, in case you do not understand, you always have the [Example Instalation](#example-instalation) chapter and there you can install the example and see how it was implemented while being able to see the SQRL working.

It will be divided into following parts:
- [SQRL Authentication](#sqrl-authentication)
- [SQRL Question](#sqrl-question)
- [IP Address Verification](#ip-address-verification)
- [SQRL Disabled](#sqrl-disable)
- [SQRL Only Allowed](#sqrl-only-allowed)
- [SQRL Hardlock](#sqrl-hardlock)


### SQRL Authentication
This is the functionality to authenticate users to the site.
First step, create a nonce for the authenticate user and send it to your login view, like the code below:
```
return view('LaravelSQRLAuthExemples.login', SQRLController::getNewAuthNonce());
```
More details in [Function to Generate Authenticated Nonce](#function-to-generate-authenticated-nonce).

Second step, check if you have the code below in `routes/api.php`, this will be the route to communicate to the SQRL Server and to check if the nonce is authenticated.
```
Route::group(['namespace'=>'\DestruidorPT\LaravelSQRLAuth\App\Http\Controllers'], function() {
    Route::get('/sqrl', 'SQRL\SQRLControllerAPI@checkIfisReady');       # Route to check if the nonce is verified
    Route::post('/sqrl', 'SQRL\SQRLControllerAPI@sqrl');                # Route of API SQRL
});
```
More details in [API Function to SQRL](#api-function-to-sqrl) and in [API Function to Check If is Ready the Nonce](#api-function-to-check-if-is-ready-the-nonce).

Third step, put the code exemple below anywhere in your login page , this will be the link and the QR Code for the user to use with the SQRL Client.
```
<a class="mx-auto" id="sqrl" href="{{$url_login_sqrl}}" onclick="sqrlLinkClick(this);return true;" encoded-sqrl-url="{{$encoded_url_login_sqrl}}" tabindex="-1">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SQRL_icon_vector_outline.svg/1200px-SQRL_icon_vector_outline.svg.png" style="margin-left:30px;margin-top:30px" class="card-img sqrl-logo" border="0" alt=" SQRL Code - Click to authenticate your SQRL identity ">
    <p style="margin-left:30px;margin-top:20px"> {!! QrCode::size(100)->generate($url_login_sqrl); !!} </p>
</a>
```
More details about [QR Code Generator](https://www.simplesoftware.io/simple-qrcode/).

Fourth step, copy the script to your html page, this will verify if the next page is ready by the nonce value, it will check every 500 milliseconds (Recommend changing the value).
```
<script>
    var syncQuery = window.XMLHttpRequest ? new window.XMLHttpRequest() : new ActiveXObject('MSXML2.XMLHTTP.3.0');
    var url = '{{$check_state_on}}';	// the location of the SQRL server
    var newSync, lastSync, encodedSqrlUrl = false, sqrlScheme = true;
    var gifProbe = new Image(); 					// create an instance of a memory-based probe image
    var localhostRoot = 'http://localhost:25519/';	// the SQRL client listener

    gifProbe.onload = function() {  // define our load-success function
        sqrlScheme = false;			// prevent retriggering of the SQRL QR code.
        document.location.href = localhostRoot + encodedSqrlUrl;
    };
    gifProbe.onerror = function() { // define our load-failure function
        setTimeout( function(){ gifProbe.src = localhostRoot + Date.now() + '.gif';	}, 250 );
    }
    function pollForNextPage() {
        if (document.hidden) {					// before probing for any page change, we check to 
            setTimeout(pollForNextPage, 5000);	// see whether the page is visible. If the user is 
            return;								// not viewing the page, check again in 5 seconds.
        }
        syncQuery.open( 'GET', url);	// the page is visible, so let's check for any update
        syncQuery.onreadystatechange = function() {
            if ( syncQuery.readyState === 4 ) {
                if ( syncQuery.status === 200 ) {
                    console.log(syncQuery.response);
                    var response = JSON.parse(syncQuery.response);
                    if(response.isReady == true) {
                        document.location.href = response.nextPage;
                    } else {
                        if(response.msg === "Time out, reload nonce!" || response.msg === "IP Doesnt Match!" || response.msg === "SQRL is disable for this user!") {
                            console.log(response.msg);
                            var div = document.getElementById("ErroMessage");
                            div.innerHTML = response.msg+" Reload The Page and try again! If you want to Authenticate by SQRL";
                            div.removeAttribute("hidden"); 
                        } else {
                            setTimeout(pollForNextPage, 500); // next check in 500 milliseconds 
                        }
                    }
                } else {
                    setTimeout(pollForNextPage, 500); // next check in 500 milliseconds 
                }
            }	
        };
        syncQuery.send(); // initiate the query to the 'sync.txt' object.
    };
    function sqrlLinkClick(e) {
        encodedSqrlUrl = e.getAttribute('encoded-sqrl-url');
        // if we have an encoded URL to jump to, initiate our GIF probing before jumping
        if ( encodedSqrlUrl ) { gifProbe.onerror(); };	// trigger the initial image probe query
    }
    pollForNextPage();
</script>
```

Fifth step, is to put this code:
```
if(isset($_GET["nut"]) && !empty($_GET["nut"])) { // Check if the nut exist or if it's past on URL https://site.test?nut=<nonce value>
    $object = SQRLController::getUserByOriginalNonceIfCanBeAuthenticated($_GET["nut"]); //Get the user by the original nonce
    if(isset($object)) { //Will be null if the nonce expired or is invalid
        if($object instanceof Sqrl_pubkey) { // This only happen when no SQRL Client is associated to the user, then Sqrl_pubkey from SQRL CLient is returned
            //new user
            return view('LaravelSQRLAuthExemples.newsqrl');//View for the user to create account or associate to one already created
        } else if($object > 0) { //This happen when SQRL Client is associated to a user, so the value is number and is the id of the user
            Auth::loginUsingId($object); //This is for authenticate the user with that id
        }
    }
}
```
on the function was pointed in the variable `SQRL_URL_LOGIN`(file `.env`), you can see the function name and controller name in the `routes/web.php`. 
You can see the exemples below.
`SQRL_URL_LOGIN`(file `.env`):
```
SQRL_URL_LOGIN=https://sqrl.test/login  # URL to your login page
```
`routes/web.php`:
```
Route::get('/login', 'LaravelSQRLAuthExemples\ExempleController@getAuthPage')->name('login');
```
Done, now it will be ready for use and testing.

### SQRL Question
This is a feature for questioning users by the SQRL application.
First step, create a nonce to question user and send it to your login view, like the code below:
```
$data = SQRLController::getNewQuestionNonce("https://sqrl.test/okbutton", "https://sqrl.test/cancelbutton", "Do you confirm 5$ tranfering?", "I accept", https://sqrl.test/iacceptbutton", "Cancel", https://sqrl.test/cancelbutton");
return view('LaravelSQRLAuthExemples.transfer', $data);
```
More details in [Function to Generate Question Nonce](#function-to-generate-question-nonce).

Second step, check if you have the code below in `routes/api.php`, this will be the route to communicate to the SQRL Server and to check if the nonce is authenticated.
```
Route::group(['namespace'=>'\DestruidorPT\LaravelSQRLAuth\App\Http\Controllers'], function() {
    Route::get('/sqrl', 'SQRL\SQRLControllerAPI@checkIfisReady');       # Route to check if the nonce is verified
    Route::post('/sqrl', 'SQRL\SQRLControllerAPI@sqrl');                # Route of API SQRL
});
```
More details in [API Function to SQRL](#api-function-to-sqrl) and in [API Function to Check If is Ready the Nonce](#api-function-to-check-if-is-ready-the-nonce).

Third step, put the code exemple below anywhere in your page, this will be the link and the QR Code for the user to use with the SQRL Client.
```
<a class="mx-auto" id="sqrl" href="{{$url_question_sqrl}}" onclick="sqrlLinkClick(this);return true;" encoded-sqrl-url="{{$encoded_url_question_sqrl}}" tabindex="-1">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SQRL_icon_vector_outline.svg/1200px-SQRL_icon_vector_outline.svg.png" style="margin-left:30px;margin-top:30px" class="card-img sqrl-logo" border="0" alt=" SQRL Code - Click to authenticate your SQRL identity ">
    <p style="margin-left:30px;margin-top:20px"> {!! QrCode::size(100)->generate($url_question_sqrl); !!} </p>
</a>
```
More Details about [QR Code Generator](https://www.simplesoftware.io/simple-qrcode/).

Fourth step, copy the script to your html page, this will verify if the next page is ready by the nonce value, it will check every 500 milliseconds (Recommend changing the value).
```
<script>
    var syncQuery = window.XMLHttpRequest ? new window.XMLHttpRequest() : new ActiveXObject('MSXML2.XMLHTTP.3.0');
    var url = '{{$check_state_on}}';	// the location of the SQRL server
    var newSync, lastSync, encodedSqrlUrl = false, sqrlScheme = true;
    var gifProbe = new Image(); 					// create an instance of a memory-based probe image
    var localhostRoot = 'http://localhost:25519/';	// the SQRL client listener

    gifProbe.onload = function() {  // define our load-success function
        sqrlScheme = false;			// prevent retriggering of the SQRL QR code.
        document.location.href = localhostRoot + encodedSqrlUrl;
    };
    gifProbe.onerror = function() { // define our load-failure function
        setTimeout( function(){ gifProbe.src = localhostRoot + Date.now() + '.gif';	}, 250 );
    }
    function pollForNextPage() {
        if (document.hidden) {					// before probing for any page change, we check to 
            setTimeout(pollForNextPage, 500);	// see whether the page is visible. If the user is 
            return;								// not viewing the page, check again in 5 seconds.
        }
        syncQuery.open( 'GET', url);	// the page is visible, so let's check for any update
        syncQuery.onreadystatechange = function() {
            if ( syncQuery.readyState === 4 ) {
                if ( syncQuery.status === 200 ) {
                    console.log(syncQuery.response);
                    var response = JSON.parse(syncQuery.response);
                    if(response.isReady == true) {
                        if(response.btn) {
                            var div = document.getElementById("ErroMessage");
                            div.innerHTML = "Button "+response.btn+": "+response.msg;
                            if(response.nextPage) {
                                var a = document.createElement('a');
                                var linkText = document.createTextNode("Click here to go to button reference.");
                                a.appendChild(linkText);
                                a.title = "Button href";
                                a.href = response.nextPage;
                                div.appendChild(a);
                            }
                            div.removeAttribute("hidden"); 
                        }
                    } else {
                        if(response.msg === "Time out, reload nonce!" || response.msg === "IP Doesnt Match!" || response.msg === "SQRL is disable for this user!") {
                            console.log(response.msg);
                            var div = document.getElementById("ErroMessage");
                            div.innerHTML = response.msg+" Reload The Page and try again! If you want to Authenticate by SQRL";
                            div.removeAttribute("hidden"); 
                        } else {
                            setTimeout(pollForNextPage, 500);
                        }
                    }
                } else {
                    setTimeout(pollForNextPage, 500);
                }
            }	
        };
        syncQuery.send(); // initiate the query to the 'sync.txt' object.
    };
    function sqrlLinkClick(e) {
        encodedSqrlUrl = e.getAttribute('encoded-sqrl-url');
        // if we have an encoded URL to jump to, initiate our GIF probing before jumping
        if ( encodedSqrlUrl ) { gifProbe.onerror(); };	// trigger the initial image probe query
    }
    pollForNextPage();
</script>
```

Fifth step, is to register the user choice on your personal controller, because when the user selects the option it will be redirected to that URL, in that url you save the user choice. The next url depends on the choice and depends on the url you submited on the function of the first step.

Done, noe it will be ready for use and testing.

### IP Address Verification
This is the functionality that verifies that the IP Address of the first nonce request is the same as the next requests around that nonce, in some cases this check is not done, for exemple the SQRL Client Mobile, as this is another device that does not have the same IP Address it was saved in request to create nonce.
No implementation is required but it is good to know that you have this feature already inserted in the project, so when creating nonces always use the functions of the package, you can check the IP Address in `Sqrl_nonce->ip_address`.
More details in [Sqrl_nonce](#sqrl_nonce-destruidorptlaravelsqrlauthappsqrl_nonce).

### SQRL Disabled
This feature is for the SQRL Client to disable SQRL authentication or to disable SQRL Client keys.
No implementation is required but it is good to know that you have this feature already inserted in the project, to check if a pubkey is disabled you can check `Sqrl_pubkey->disabled`, '0' means not disable and '1' is disable.
More details in [Sqrl_pubkey](#sqrl_pubkey-destruidorptlaravelsqrlauthappsqrl_pubkey).

### SQRL Only Allowed
<h4>Optional Functionality</h4>
This feature is for the user to block username and password login feature, this feature is enabled by SQRL Client application.

In order to know if the user as SQRL only allowed, you can perform this function:
```
SQRLController::checkIfUserCanAuthByNormalLogin($user_id);
```
More details in [Function to Check If User Can Auth By Normal Login](#function-to-check-if-user-can-auth-by-normal-login).
For example you can add this code before allowing user authentication, check if it is allowed:
```
if(isset($user)){ //Check if the user exists
    if(!SQRLController::checkIfUserCanAuthByNormalLogin($user->id)) { //Check if the user can not be authentication by normal login authentication
        return redirect()->intended('login')->withErrors(['SQRL Only Allowed!!!']);//If returned false then the user only can authenticate by SQRL
    }
}
```

### SQRL Hardlock
<h4>Optional Functionality</h4>
This feature is so that the user can lock the password recovery feature, this feature is enabled by SQRL Client application.

In order to know if user as SQRL hardlock, you can perform this function:
```
SQRLController::checkIfUserCanUseRecoverPassword($user_id);
```
More details in [Function to Check If User Can Use Recover Password](#function-to-check-if-user-can-use-recover-password).
For example you can add this code before allowing the user to recover his password, check if it is allowed:
```
if(isset($user)){ //Check if the user exists
    if(!SQRLController::checkIfUserCanUseRecoverPassword($user->id)) { //Check if the user can not recovery the password
        return redirect()->intended('resetpw')->withErrors(['SQRL not Allowed recovery account!!!']); //This means that the account as hardlocked by SQRL Client that not Allowed recovery password by email or personal questions
    }
}
```

# Example Instalation
<strong>First you need to have followed the Install topic before continuing with this topic.</strong>

Make sure to follow these steps so that you can install this example.
To start run the following command.

```
php artisan vendor:publish --provider="DestruidorPT\LaravelSQRLAuth\LaravelSQRLAuthServiceProvider"
```

The next step is to copy the routes below to `routes/api.php`.

```
Route::group(['namespace'=>'\DestruidorPT\LaravelSQRLAuth\App\Http\Controllers'], function() {
    Route::get('/sqrl', 'SQRL\SQRLControllerAPI@checkIfisReady');       # Route to check if the nonce is verified
    Route::post('/sqrl', 'SQRL\SQRLControllerAPI@sqrl');                # Route of API SQRL
});
```

Finally to finish the installation, you just have to copy the following routes to `routes/web.php`.

```
Route::get('/', function () {
    return redirect('login');
});

Route::get('/login', 'LaravelSQRLAuthExemples\ExempleController@getAuthPage')->name('login');
Route::post('/login', 'LaravelSQRLAuthExemples\ExempleController@login');
Route::post('/logout', 'LaravelSQRLAuthExemples\ExempleController@logout')->name('logout');

Route::get('/dashboard', 'LaravelSQRLAuthExemples\ExempleController@getDashboardPage')->name('dashboard');

Route::post('/transfer', 'LaravelSQRLAuthExemples\ExempleController@getTransferConfirmation');

Route::get('/resetpw', 'LaravelSQRLAuthExemples\ExempleController@getResetPWPage')->name('resetpw');
Route::post('/resetpw', 'LaravelSQRLAuthExemples\ExempleController@resetPW');

Route::post('/newlogin', 'LaravelSQRLAuthExemples\ExempleController@newlogin');
Route::post('/newaccount', 'LaravelSQRLAuthExemples\ExempleController@newAcc');
```

# Classes And Data
Here is all the information saved in the database and the classes used. 

The list of classes:
  - [Sqrl_nonce](#sqrl_nonce-destruidorptlaravelsqrlauthappsqrl_nonce)
  - [Sqrl_pubkey](#sqrl_pubkey-destruidorptlaravelsqrlauthappsqrl_pubkey)

### Sqrl_nonce (DestruidorPT\LaravelSQRLAuth\App\Sqrl_nonce)
The Class Sqrl_nonce contains all the information needed to create a point to start a communicacion between SQRL Server and SQRL Client.
Below you can find all the data: 

| Field Name |  Type Value  |  Observation  |
| --- | --- | --- |
|  id |  bigint(20) | ID |
|  nonce | varchar(255) |  Nonce Can be used to communicate between SQRL Client and SQRL Server |
|  type | enum |  Possible values is 'auth' and 'question' |
|  ip_address | varchar(45) |  IP Address of the request made when this nonce was created |
|  url |  longtext |  URL to redirect when nonce was verified successful |
|  can |  longtext |  URL to redirect when user cancel authentication or the question  |
|  verified |  tinyint(4) |  Values possible is '0' or '1', '0' not verified and '1' verified |
|  orig_nonce |  varchar(255) |  Is the same value of field 'nonce' when was created |
|  question |  longtext |  All the informacion to create question on the SQRL Client  |
|  btn_answer |  tinyint(4) |  Response of user on the question, values possible is '0' to “OK” button, '1' to the first button and '2' to the secound button |
|  sqrl_pubkey_id |  bigint(20) |  Is the Sqrl_pubkey id |
|  created_at |  timestamp |  Date when was created |
|  updated_at |  timestamp |  Last modified date |

### Sqrl_pubkey (DestruidorPT\LaravelSQRLAuth\App\Sqrl_pubkey)
The Class Sqrl_pubkey contains all the information needed to know what SQRL Client is related to the user, in other words it's where all the information about the SQRL Client keys and the user related exists.
Once again you can find the data below:

| Name |  Type Value  |  Observation  |
| --- | --- | --- |
|  id |  bigint(20) |  ID |
|  user_id |  bigint(20) |  Is the User ID |
|  public_key |  varchar(255) |  This is the user's SQRL ID which uniquely identifies them to the site, is called IDK in SQRL Documentation |
|  vuk |  varchar(255) |  Is the Server Unlock Key in SQRL Documentation  |
|  suk |  varchar(255) |  Is the Verify Unlock Key in SQRL Documentation  |
|  disabled |  tinyint(4) |  Values possible is '0' or '1', '0' Enable Sqrl_pubkey and '1' Disable Sqrl_pubkey |
|  sqrl_only_allowed |  tinyint(4) |  Values possible is '0' or '1', '0' SQRL Only Autheticacion disable and '1' SQRL Only Autheticacion  enable |
|  hardlock |  tinyint(4) |  Values possible is '0' or '1', '0' hardlock disable and '1' hardlock enable |
|  created_at |  timestamp |  Date when was created |
|  updated_at |  timestamp |  Last modified date |

# Functions Availables
List of all available features for the implementation of all available SQRL features.

- [SQRLController](#sqrlcontroller-destruidorptlaravelsqrlauthapphttpcontrollerssqrlsqrlcontroller)
  - [Function to Generate Authenticated Nonce](#function-to-generate-authenticated-nonce)
  - [Function to Generate Question Nonce](#function-to-generate-question-nonce)
  - [Function to Check If User Can Auth By Normal Login](#function-to-check-if-user-can-auth-by-normal-login)
  - [Function to Check If User Can Use Recover Password](#function-to-check-if-user-can-use-recover-password)
  - [Function to Check If User Can Auth By SQRL](#function-to-check-if-user-can-auth-by-sqrl)
  - [Function to Check If is Ready the Nonce](#function-to-check-if-is-ready-the-nonce)
  - [Function to Get User By Original Nonce If Can Be Authenticated](#function-to-get-user-by-original-nonce-if-can-be-authenticated)
- [SQRLControllerAPI](#sqrlcontrollerapi-destruidorptlaravelsqrlauthapphttpcontrollerssqrlsqrlcontrollerapi)
  - [API Function to SQRL](#api-function-to-sqrl)
  - [API Function to Check If is Ready the Nonce](#api-function-to-check-if-is-ready-the-nonce)

### SQRLController (DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\SQRLController)
Only use this controller on your own server.

#### Function to Generate Authenticated Nonce
To generate the Authenticated Nonce, you need to call the function below.
```
SQRLController::getNewAuthNonce();
```
This function will return this array: 
```
[
    'nonce',                  <-- Nonce value,
    'check_state_on',         <-- Route to check if nonce was verified
    'url_login_sqrl',         <-- Url for sqrl client to receive
    'encoded_url_login_sqrl', <-- Endoded Base 64 URL(url for sqrl client to receive)
]
```

#### Function to Generate Question Nonce
To generate the Question Nonce, you need to call the function below.
```
SQRLController::getNewQuestionNonce($url, $can, $question, $btn1 = null, $url1 = null, $btn2 = null, $url2 = null);
```
This function need these values: 
```
(
    $url,       <-- Url to Rederect user if he press ok
    $can,       <-- Url to Rederect user if he press cancel
    $question,  <-- String the question you want
    $btn1,      <-- Name of the first button opcion for user (is opcional, only requered if url1 is not null)
    $url1,      <-- Url to Rederect user if he press btn1 (is opcional)
    $btn2,      <-- Name of the secound button opcion for user (is opcional, only requered if url2 is not null)
    $url2       <-- Url to Rederect user if he press btn2 (is opcional)
)
```
This function will return this array: 
```
[
    'nonce',                     <-- Nonce value
    'check_state_on',            <-- Route to check if nonce was verified
    'url_question_sqrl',         <-- Url for sqrl client to receive
    'encoded_url_question_sqrl', <-- Endoded Base 64 URL(url for sqrl client to receive)
]
```

#### Function to Check If User Can Auth By Normal Login
To check if an user can login normally, you need to call the function below.
```
SQRLController::checkIfUserCanAuthByNormalLogin($user_id);
```
This function need this value: 
```
(
    $user_id <-- The user id you want to check, if this is null or not number > 0 function will return false
)
```
This function will return this boolean: 
```
    true    <-- Can do a normal login (username and password)
    false   <-- SQRL Authentication Only Allowed
```

#### Function to Check If User Can Use Recover Password
To check if the user can recover his password, you need to call the function below.
```
SQRLController::checkIfUserCanUseRecoverPassword($user_id);
```
This function need these values: 
```
(
    $user_id <-- The user id you want to check, if this is null or not number > 0 function will return false
)
```
This function will return this boolean: 
```
    true    <-- Can do a normal login (username and password)
    false   <-- SQRL Authentication Only Allowed
```

#### Function to Check If User Can Auth By SQRL
To check if the user can make am SQRL Authentication, you need to call the function below.
```
SQRLController::checkIfUserCanAuthBySQRL($user_id);
```
This function need this value: 
```
(
    $user_id <-- The user id you want to check, if this is null or not number > 0 function will return false
)
```
This function will return this boolean: 
```
    true    <-- Can do a normal login (username and password)
    false   <-- SQRL Authentication Only Allowed
```

#### Function to Check If the Nonce is Ready
This function is necessary for when the user uses SQRL Client Mobile or some SQRL Client that cannot redirect to the user browser, when that happens the user browser needs to check from time to time the nonce, and when the nonce is valid and is of type authentication you need to call the function [Get User By Original Nonce If Can Be Authenticated](#function-to-get-user-by-original-nonce-if-can-be-authenticated).
The function name is below.
```
SQRLController::checkIfisReady($nut);
```
This function need this value: 
```
(
    $nut       <-- Is the nonce you want to validate
)
```
This function will return null if the nonce is null or empty, if nonce is valid it will return this array: 
```
[
    'isReady',  <-- False mean that nonce was not valid or verified
    'msg',      <-- This is msg of error and successful, and can be one of this one:
                    'Time out, reload nonce!' -- When this happen, you need to create new nonce because this exceed is valid time;
                    'IP Doesnt Match!' -- This happen when IP Adress of the nonce was created for, is not equal to the IP Address of the request made;
                    'Not Ready!' -- This happen when nonce is valid but still no SQRL Client checked the nonce;
                    'SQRL is disable for this user!' -- This happen when user was SQRL disable;
                    'Can be authenticated!' -- Successful authenticated message;
                    'The button selected is '.<--Here is number of button selected-->.'!' -- Successful question answer message;
                    'Button is invalid!' -- Successful question answer message but invalid button was received;
    'nextPage', <-- Url to Next Page (Only appear for Authenticated Nonces, when 'isReady' is true and when 'msg' is 'Can be authenticated!')
    'btn'      <-- the selected button question (Only appear for Question Nonces, when 'isReady' is true and when 'msg' is 'The button selected is <?>!' or 'Button is invalid!')
                   The text value of the btn parameter will be the character ‘0’ to “OK” button, ‘1’ to the first button or ‘2’ to the secound button.
] 
```

#### Function to Get User By Original Nonce If Can Be Authenticated
This function is to get the user if the nonce is valid.
The function name is below.
```
SQRLController::getUserByOriginalNonceIfCanBeAuthenticated($orig_nonce);
```
This function need this value: 
```
(
    $orig_nonce <-- The nonce value given on from function getNewAuthNonce()
)
```
This function will return one of these values: 
```
    null                <-- If nonce is no longer valid or doesn't exist
    Class Sqrl_pubkey   <-- Return the Class Sqrl_pubkey this happen when no user is associated to the Sqrl_pubkey, means is maybe a new user or he need to   associate is user account to this Sqrl_pubkey
    Int user_id         <-- Return the user id when Sqrl_pubkey was found associated to a user
```

### SQRLControllerAPI (DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\SQRLControllerAPI)
This controller is able to integrate other sites with your SQRL Server and for the SQRL client to communicate.


#### API Function to SQRL
This function is the most important and it's required to use, without this function the SQRL Server will not work, to make the configuration you need to create a route API and put the route in the file `.env` in the variable `SQRL_ROUTE_TO_SQRL_AUTH`. Then all communication from SQRL Client will go to this API Function, to see what happens check the log, you can see more information in [Configure Log System](#configure-log-system).
```
SQRLController::sqrl();
```
This function will return this array: 
```
[
    'nonce',                  <-- Nonce value,
    'check_state_on',         <-- Route to check if nonce was verified
    'url_login_sqrl',         <-- Url for sqrl client to receive
    'encoded_url_login_sqrl', <-- Endoded Base 64 URL(url for sqrl client to receive)
]
```

#### API Function to Check If the Nonce is Ready
This function is necessary for when the user uses SQRL Client Mobile or some SQRL Client the cannot redirect the user browser, when that happens the user browser need to check from time to time the nonce, and when the nonce is valid and is a nonce of type authetication you need to call function [Get User By Original Nonce If Can Be Authenticated](#function-to-get-user-by-original-nonce-if-can-be-authenticated).
The function name is below.
```
SQRLController::checkIfisReady();
```
This function need this value: 
```
(
    $nut       <-- Is the nonce you want to validate, you pass this value by adding to url `?nut=<nonce_value>`
)
```
This function will return 404 if the $_GET["nut"] is null or empty, if the nonce is valid it will return this array: 
```
[
    'isReady',  <-- False mean that nonce was not valid or verified
    'msg',      <-- This is msg of error and successful, and can be one of this one:
                    'Time out, reload nonce!' -- When this happen, you need to create new nonce because this exceed is valid time;
                    'IP Doesnt Match!' -- This happen when IP Adress of the nonce was created for, is not equal to the IP Address of the request made;
                    'Not Ready!' -- This happen when nonce is valid but still no SQRL Client checked the nonce;
                    'SQRL is disable for this user!' -- This happen when user was SQRL disable;
                    'Can be authenticated!' -- Successful authenticated message;
                    'The button selected is '.<--Here is name and the number of button selected-->.'!' -- Successful question answer message;
                    'Button is invalid!' -- Successful question answer message but invalid button was received;
    'nextPage', <-- Url to Next Page, appears when authentication was successfull and the url to the button was selected if was configurade
    'btn'      <-- the selected button question (Only appear for Question Nonces, when 'isReady' is true and when 'msg' is 'The button selected is <?>!' or 'Button is invalid!')  
] 
```


# Demo
[Click here to go to the video on YouTube](https://www.youtube.com/watch?v=pGqNG7wat_A)

[![Click here to go to the video on YouTube](http://img.youtube.com/vi/pGqNG7wat_A/0.jpg)](http://www.youtube.com/watch?v=pGqNG7wat_A "Installation package 'Laravel-SQRL-Auth' and demo")


# Contacts
- Elton Pastilha: <br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Email: eltonpatilha@gmail.com<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;GitHub: [DestruidorPT](https://github.com/DestruidorPT)<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;WebSite: [destruidor.com](https://www.destruidor.com)<br>
- João Ricardo: <br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Email: joao_pricardo@hotmail.com<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;GitHub: [kratezo](https://github.com/kratezo)<br>
- Vladyslav Adamovych: <br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Email: vladyslav.adamovych@ipleiria.pt<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;GitHub: [nekkiii](https://github.com/nekkiii)<br>
