<!DOCTYPE html>

<html lang="en">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login Page</title>

    <style>
        body {


            background-color: #f2f2f2;

            margin: 0;

            padding: 0;
            background-image: url('../public/image/managing.jpeg');
            background-repeat: no-repeat;
            background-size: cover;

        }



        .container {

            max-width: 400px;

            margin: 50px auto;

            background-color: #fff;

            padding: 20px;

            border-radius: 5px;

            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);

            text-align: center;

        }



        h2 {

            margin-bottom: 20px;

        }



        .btn {

            background-color: #4CAF50;

            color: #fff;

            border: none;

            padding: 10px 20px;

            border-radius: 3px;

            cursor: pointer;

            text-decoration: none;

        }



        .btn:hover {

            background-color: #45a049;

        }
    </style>

</head>



<body>

    <div class="container">

        <h2>Login</h2>

        <p>Click below to login:</p>

        <a href="{{url('login')}}" class="btn">Login</a>

    </div>

</body>



</html>