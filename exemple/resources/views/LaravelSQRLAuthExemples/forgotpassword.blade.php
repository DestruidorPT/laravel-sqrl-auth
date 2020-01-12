<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Reset Password</title>

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
            <div class="card mx-auto my-auto" style="max-width: 540px;">
                <div class="row no-gutters">
                    <div class="col-sm mx-2 my-3">
                        <div class="card-body">
                            @if($errors->first())<div class="alert alert-danger" role="alert">{{$errors->first()}}</div>@endif
                            <form method="post" action="/resetpw">
                                {{ csrf_field() }}
                                <div class="form-group">
                                    <label for="email">E-Mail</label>
                                    <input name="email" type="email" class="form-control" id="email">
                                </div>
                                <button type="submit" class="btn btn-dark">Reset Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.0/js/bootstrap.min.js" integrity="sha384-3qaqj0lc6sV/qpzrc1N5DC6i1VRn/HyX4qdPaiEFbn54VjQBEU341pvjz7Dv3n6P" crossorigin="anonymous"></script>
</html>
