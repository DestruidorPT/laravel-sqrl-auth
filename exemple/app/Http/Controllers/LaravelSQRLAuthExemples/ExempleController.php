<?php

namespace App\Http\Controllers\LaravelSQRLAuthExemples;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\User;
use DestruidorPT\LaravelSQRLAuth\App\Sqrl_pubkey;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\SQRLController;

class ExempleController extends Controller
{
    /**
     * Function to get the login page and check if given by paramer GET["nut"] is passed
     * if $_GET["nut"] exist will check if the nonce is valid and return object type Sqrl_pubkey if the
     * no SQRL Client registed is not associated to any user, if returns a number means is ID of the user 
     * associated and then proceds to authenticate that user
     * 
     * @GET string nut
     * 
     * @return View
     */ 
    public function getAuthPage() 
    {
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
        if (Auth::check()) {
            // The user is logged in...
            return redirect()->intended('dashboard');
        }
        return view('LaravelSQRLAuthExemples.login', SQRLController::getNewAuthNonce());
    }

    /**
     * Function to post credentials from login page and check if user exist and are valid credentials,
     * But will check first if the user can login by normal login  given by paramer GET["nut"] is passed
     * if $_GET["nut"] exist will check if the nonce is valid and return object type Sqrl_pubkey if the
     * no SQRL Client registed is not associated to any user, if returns a number means is ID of the user 
     * associated and then proceds to authenticate that user
     * 
     * @GET string nut
     * 
     * @return View
     */ 
    public function login(Request $request)
    {
        if (Auth::check()) {
            // The user is logged in...
            return redirect()->intended('dashboard');
        }
        $validatedData = $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);
        $user = User::where("email", "=", $validatedData["email"])->first();
        if(isset($user)){ //Check if the user exists
            if(!SQRLController::checkIfUserCanAuthByNormalLogin($user->id)) { //Check if the user can not be authentication by normal login authentication
                return redirect()->intended('login')->withErrors(['SQRL Only Allowed!!!']);//If returned false then the user only can authenticate by SQRL
            }
        }
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            // Authentication passed...
            return redirect()->intended('dashboard');
        }
        return redirect()->intended('login')->withErrors(['Login Fail!!!']);
    }
    
    /**
     * Function to post the new user information and can associate the new user 
     * to the SQRL Client Public Key that was not previously associated to any user
     * 
     * @GET string nut
     * @param Request $request
     * 
     * @return View
     */ 
    public function newAcc(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'email' => 'required|unique:users,email',
            'password' => 'required|string',
        ]);
        $validatedData['password'] = Hash::make($validatedData['password']);

        
        $user = new User($validatedData);
        $user->save();
        //dd($user);
        if(isset($user)) { // Check if user is not null
            if(isset($_GET["nut"]) && !empty($_GET["nut"])) {  // Check if the nut exist or if it's past on URL https://site.test?nut=<nonce value>
                $object = SQRLController::getUserByOriginalNonceIfCanBeAuthenticated($_GET["nut"]); //Get the user by the original nonce
                if(isset($object)) { //Will be null if the nonce expired or is invalid
                    if($object instanceof Sqrl_pubkey) { // This only happen when no SQRL Client is associated to the user, then Sqrl_pubkey from SQRL CLient is returned
                        //new user
                        $object->user_id = $user->id; // So the user was created then lets associate to the new user
                        $object->save();
                    }
                }
            }
            Auth::loginUsingId($user->id);
        }
        if (Auth::check()) {
            // Authentication passed...
            return redirect()->intended('dashboard');
        }
        return redirect()->intended('login');
    }
        
    /**
     * Function to post the new credentials of the user login and for associate the user 
     * to the SQRL Client Public Key that was not previously associated to any user
     * 
     * @GET string nut
     * @param Request $request
     * 
     * @return View
     */ 
    public function newlogin(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) { // Do a login of the user
            // Authentication passed...
            $object = SQRLController::getUserByOriginalNonceIfCanBeAuthenticated($_GET["nut"]); //Get the user by the original nonce
            if(isset($object)) { //Will be null if the nonce expired or is invalid
                if($object instanceof Sqrl_pubkey) { // This only happen when no SQRL Client is associated to the user, then Sqrl_pubkey from SQRL CLient is returned
                    //new user
                    $object->user_id = Auth::id(); // So the user was created then lets associate to the user already existing
                    $object->save();
                }
            }
            return redirect()->intended('dashboard');
        }
        return redirect()->intended('login');
    }
  
    /**
     * Function to logout user
     * 
     * @param Request $request
     * 
     * @return View
     */ 
    public function logout(Request $request)
    {
        Auth::logout();
        return redirect()->intended('login');
    }

    /**
     * Function to get the dashboard page
     * 
     * 
     * @return View
     */ 
    public function getDashboardPage() 
    {
        if (Auth::check()) {
            // The user is logged in...
            return view('LaravelSQRLAuthExemples.dashboard', ["user_name" => Auth::user()->name]);
        }
        return redirect()->intended('login');
    }

    /**
     * Function to post the new question to the user by SQRL application, in this exemple is a question if the user confer a transaction of money
     * 
     * @param Request $request
     * 
     * @return View
     */ 
    public function getTransferConfirmation(Request $request) 
    {
        $validatedData = $request->validate([
            'money' => 'required|numeric|min:1',
            'btn1' => 'nullable|string|required_with:url1',
            'url1' => 'nullable|string',
            'btn2' => 'nullable|string|required_with:url2',
            'url2' => 'nullable|string',
        ]);
        $data = SQRLController::getNewQuestionNonce(env('SQRL_URL_LOGIN', 'https://sqrl.test/login'), env('SQRL_URL_LOGIN', 'https://sqrl.test/login'), "Do you confirm ".$validatedData["money"]."$ tranfering?", $validatedData["btn1"], $validatedData["url1"], $validatedData["btn2"], $validatedData["url2"]);
        $data["money"] = $validatedData["money"];
        return view('LaravelSQRLAuthExemples.transfer', $data);
    }
    
    /**
     * Function to get the page to reset password
     * 
     * @param Request $request
     * 
     * @return View
     */ 
    public function getResetPWPage() 
    {
        return view('LaravelSQRLAuthExemples.forgotpassword');
    }

    /**
     * Function to post the information of user to reset the password,
     * but only will reset the password if user account allowed
     * 
     * @param Request $request
     * 
     * @return View
     */ 
    public function resetPW(Request $request) 
    {
        $validatedData = $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'string',
        ]);
        $user = User::where("email", "=", $validatedData["email"])->first();
        if(isset($user)){ //Check if the user exists
            if(!SQRLController::checkIfUserCanUseRecoverPassword($user->id)) { //Check if the user can not recovery the password
                return redirect()->intended('resetpw')->withErrors(['SQRL not Allowed recovery account!!!']); //This means that the account as hardlocked by SQRL Client that not Allowed recovery password by email or personal questions
            } else if(isset($validatedData["password"]) && !empty($validatedData["password"])) {
                $user->password = $validatedData["password"];
                $user->save();
            }
        } 
        return view('LaravelSQRLAuthExemples.newpassword', ['email' => $validatedData["email"]]);
    }
    
}
