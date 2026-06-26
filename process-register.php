<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('connect.php');

if(isset($_POST['firstname_account']) && isset($_POST['lastname_account']) && isset($_POST['email_account']) && isset($_POST['password_account1']) && isset($_POST['password_account2'])){

    // ✅ ชื่อ/นามสกุล/อีเมล — escape ปกติ (ไม่ต้องใช้ htmlspecialchars สำหรับข้อมูลที่จะเก็บลง DB)
    $firstname_account = mysqli_real_escape_string($connect, trim($_POST['firstname_account']));
    $lastname_account  = mysqli_real_escape_string($connect, trim($_POST['lastname_account']));
    $email_account     = mysqli_real_escape_string($connect, trim($_POST['email_account']));

    // ✅ password รับค่าตรงๆ ห้ามใช้ htmlspecialchars/escape ก่อน hash
    $password_account1 = $_POST['password_account1'];
    $password_account2 = $_POST['password_account2'];

    if(empty($firstname_account)){
        die(header('Location: form-register.php')); // คุณไม่ได้กรอกชื่อ
    }elseif(empty($lastname_account)){
        die(header('Location: form-register.php')); // คุณไม่ได้กรอกนามสกุล
    }elseif(empty($email_account)){
        die(header('Location: form-register.php')); // คุณไม่ได้กรอกอีเมล
    }elseif(empty($password_account1)){
        die(header('Location: form-register.php')); // คุณไม่ได้กรอกรหัสผ่าน
    }elseif(empty($password_account2)){
        die(header('Location: form-register.php')); // คุณไม่ได้กรอกการยืนยันรหัสผ่าน
    }elseif($password_account1 !== $password_account2){
        die(header('Location: form-register.php')); // รหัสผ่านไม่ตรงกัน
    }else{
        $query_check_email_account = "SELECT email_account FROM account_user WHERE email_account = '$email_account'";

        $call_back_query_check_email_account = mysqli_query($connect, $query_check_email_account);

        if(!$call_back_query_check_email_account){
            die("Query Error: " . mysqli_error($connect));
        }

        if(mysqli_num_rows($call_back_query_check_email_account) > 0){
            die(header('Location: form-register.php')); // มีผู้ใช้อีเมลนี้แล้ว
        }else{
            $length       = random_int(97, 128);
            $salt_account = bin2hex(random_bytes($length)); // สร้างค่าเกลือ

            $password_to_hash = $password_account1 . $salt_account; // ต่อรหัสผ่านกับเกลือ

            $password_user = password_hash($password_to_hash, PASSWORD_DEFAULT);

            // ✅ escape hash ก่อน INSERT (hash ปลอดภัยอยู่แล้ว แต่ทำไว้เป็นมาตรฐาน)
            $password_user_escaped = mysqli_real_escape_string($connect, $password_user);
            $salt_escaped          = mysqli_real_escape_string($connect, $salt_account);

            $query_create_account = "INSERT INTO account_user 
                (firstname_account, lastname_account, email_account, password_user, role_account, profile_images, salt_account) 
                VALUES 
                ('$firstname_account', '$lastname_account', '$email_account', '$password_user_escaped', 'Member', 'default_images_account.jpg', '$salt_escaped')";

            $call_back_create_account = mysqli_query($connect, $query_create_account);
            if($call_back_create_account){
                die(header('Location: form-login.php')); // สร้างบัญชีเสร็จแล้ว
            }else{
                die("Insert Error: " . mysqli_error($connect)); // แสดง error จริงระหว่าง dev
            }
        }
    }

}else{
    die(header('Location: form-register.php')); // ไม่มีข้อมูล POST
}
?>