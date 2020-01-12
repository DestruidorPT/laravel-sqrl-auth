<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.0/css/bootstrap.min.css" integrity="sha384-SI27wrMjH3ZZ89r4o+fGIJtnzkAnFs3E4qz9DIYioCQ5l9Rd/7UAa8DHcaL8jkWt" crossorigin="anonymous">
        <!-- Styles -->
        <style>
           body {
                height: 90vh;
                width: 100%;
                background: linear-gradient(to bottom, rgba(255,255,255,0.15) 0%, rgba(0,0,0,0.15) 100%), radial-gradient(at top center, rgba(255,255,255,0.40) 0%, rgba(0,0,0,0.40) 120%) #989898; 
                background-blend-mode: multiply,multiply;
           }
           .container {
                height: 100%;
                width: 100%;
                margin-top: 10vh;
           }
           .card {
                background-image: linear-gradient(120deg, #f6d365 0%, #fda085 100%);
           }
           .sqrl-logo {
                height: 100px !important;
                width: 100px !important;
           }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card mx-auto my-auto" style="max-width: 540px">
                <div class="row no-gutters">
                    <div class="col-md-4 mx-auto">
                        <a class="mx-auto" id="sqrl" href="{{$url_login_sqrl}}" onclick="sqrlLinkClick(this);return true;" encoded-sqrl-url="{{$encoded_url_login_sqrl}}" tabindex="-1">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SQRL_icon_vector_outline.svg/1200px-SQRL_icon_vector_outline.svg.png" style="margin-left:30px;margin-top:30px" class="card-img sqrl-logo" border="0" alt=" SQRL Code - Click to authenticate your SQRL identity ">
                            <p style="margin-left:30px;margin-top:20px"> {!! QrCode::size(100)->generate($url_login_sqrl); !!} </p>
                        </a>
                        <div class="alert alert-danger" role="alert" id="ErroMessage" hidden></div>
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <h5 class="card-title">Login</h5>
                            @if($errors->first())<div class="alert alert-danger" role="alert">{{$errors->first()}}</div>@endif
                            <form method="post" action="/login">
                                {{ csrf_field() }}
                                <div class="form-group">
                                    <label for="email">Username</label>
                                    <input name="email" type="text" class="form-control" id="email">
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input name="password" type="password" class="form-control" id="password">
                                </div>
                                <button type="submit" class="btn btn-dark">Login</button>
                                <a href="/resetpw" class="btn btn-link text-dark">Forgot Password</a></br>
                            </form>                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                                document.location.href = response.nextPage;
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
    </body>
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.0/js/bootstrap.min.js" integrity="sha384-3qaqj0lc6sV/qpzrc1N5DC6i1VRn/HyX4qdPaiEFbn54VjQBEU341pvjz7Dv3n6P" crossorigin="anonymous"></script>
</html>
