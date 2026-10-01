<?php
session_start();
include 'config.php';

if(isset($_SESSION['login'])){
    header("Location:index.php");
    exit;
}

$error="";

if(isset($_POST['login'])){

    $username = mysqli_real_escape_string($conn,$_POST['username']);
    $password = mysqli_real_escape_string($conn,$_POST['password']);

    $query = mysqli_query(
        $conn,
        "SELECT * FROM simfm_user
        WHERE username='$username'
        AND password='$password'
        AND status='aktif'
        LIMIT 1"
    );

    if(mysqli_num_rows($query) > 0){

        $data = mysqli_fetch_assoc($query);

        $_SESSION['login'] = true;
        $_SESSION['id_user'] = $data['id'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['nama'] = $data['nama_lengkap'];
        $_SESSION['level'] = $data['level'];

        header("Location:index.php");
        exit;

    }else{
        $error = "Username atau password salah";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login SIMFM</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background:#f4f6f9;">

<div class="container">
    <div class="row justify-content-center mt-5">

        <div class="col-md-4">

            <div class="card shadow">

                <div class="card-header text-center">
                    <h4>SIMFM LOGIN</h4>
                </div>

                <div class="card-body">

                    <?php if($error!=""){ ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                    <?php } ?>

                    <form method="POST">

                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text"
                                   name="username"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password"
                                   name="password"
                                   class="form-control"
                                   required>
                        </div>

                        <button type="submit"
                                name="login"
                                class="btn btn-primary w-100">
                            Login
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>
</div>

</body>
</html>
