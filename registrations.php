<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPmailer/Exception.php';
require 'PHPmailer/PHPMailer.php';
require 'PHPmailer/SMTP.php';

include 'database.php';

$conn = mysqli_connect($server, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_query($conn, "SET NAMES 'utf8'");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $agree = isset($_POST['agree']) ? 1 : 0;
    $requiredFields = ['name', 'sueno', 'palabras_clave_sueno','ofrezco', 'palabras_clave_ofrezco', 'necesito', 'palabras_clave_necesito', 'document_type', 'document_number', 'phone', 'email', 'city', 'country', 'age', 'gender', 'disease_type', 'shirt_size', 'eps', 'blood_type', 'modalidad_participacion', 'observaciones', 'password', 'agree'];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            die("Error: El campo $field es obligatorio.");
        }
    }

    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        die("Error: La dirección de correo electrónico no es válida.");
    }
    
    // Verificar si el correo electrónico ya está registrado
    $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
    $checkEmailStmt = mysqli_prepare($conn, $checkEmailQuery);
    mysqli_stmt_bind_param($checkEmailStmt, "s", $_POST['email']);
    mysqli_stmt_execute($checkEmailStmt);
    mysqli_stmt_store_result($checkEmailStmt);

    if (mysqli_stmt_num_rows($checkEmailStmt) > 0) {
        die("Error: El correo electrónico ya está registrado.");
    }

    mysqli_stmt_close($checkEmailStmt);

    // Verificar si la contraseña y su confirmación coinciden
    if ($_POST['password'] !== $_POST['confirm_password']) {
        die("Error: Las contraseñas no coinciden.");
    }

    if (!$agree) {
        die("Error: El campo agree es obligatorio.");
    }

    $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $sueno = mysqli_real_escape_string($conn, $_POST['sueno']);
    $palabrasClaveSueno = mysqli_real_escape_string($conn, $_POST['palabras_clave_sueno']);

    $ofrezco = mysqli_real_escape_string($conn, $_POST['ofrezco']);
    $palabrasClaveOfrezco = mysqli_real_escape_string($conn, $_POST['palabras_clave_ofrezco']);
    
    $necesito = mysqli_real_escape_string($conn, $_POST['necesito']);
    $palabrasClaveNecesito = mysqli_real_escape_string($conn, $_POST['palabras_clave_necesito']);

    $documentType = mysqli_real_escape_string($conn, $_POST['document_type']);
    $documentNumber = mysqli_real_escape_string($conn, $_POST['document_number']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $country = mysqli_real_escape_string($conn, $_POST['country']);
    $age = mysqli_real_escape_string($conn, $_POST['age']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $diseaseType = mysqli_real_escape_string($conn, $_POST['disease_type']);
    $shirtSize = mysqli_real_escape_string($conn, $_POST['shirt_size']);
    $eps = mysqli_real_escape_string($conn, $_POST['eps']);
    $bloodType = mysqli_real_escape_string($conn, $_POST['blood_type']);
    $modalidadParticipacion = mysqli_real_escape_string($conn, $_POST['modalidad_participacion']);
    $observaciones = mysqli_real_escape_string($conn, $_POST['observaciones']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $agree = mysqli_real_escape_string($conn, $_POST['agree']);

    $query = "INSERT INTO users (name, sueno, palabras_clave_sueno, ofrezco, palabras_clave_ofrezco, necesito, palabras_clave_necesito, document_type, document_number, phone, email, city, country, age, gender, disease_type, shirt_size, eps, blood_type, modalidad_participacion, observaciones, password, agree) 
    VALUES ('$name', '$sueno', '$palabrasClaveSueno', '$ofrezco', '$palabrasClaveOfrezco', '$necesito', '$palabrasClaveNecesito', '$documentType', '$documentNumber', '$phone', '$email', '$city', '$country', '$age', '$gender', '$diseaseType', '$shirtSize', '$eps', '$bloodType', '$modalidadParticipacion', '$observaciones', '$hashedPassword', '$agree')";

    if (mysqli_query($conn, $query)) {
        // Registro insertado con éxito
        $lastId = mysqli_insert_id($conn);
        $enlaceUnico = strtolower(str_replace(' ', '', $name)) . '-' . $lastId;

        $updateQuery = "UPDATE users SET enlace_unico = '$enlaceUnico' WHERE id = $lastId";
        mysqli_query($conn, $updateQuery);

        // Redirigir según la modalidad de participación
        if ($modalidadParticipacion == 'donativo') {
            header("Location: https://checkout.wompi.co/l/qv5TrG");
            exit;
        } elseif ($modalidadParticipacion == 'kit') {
            header("Location: https://checkout.wompi.co/l/NtItl0");
            exit;
        } elseif ($modalidadParticipacion == 'kit-padrino') {
            header("Location: https://checkout.wompi.co/l/TagzqI");
            exit;
        } elseif ($modalidadParticipacion == 'kit-agenda') {
            header("Location: https://checkout.wompi.co/l/SGmCeP");
            exit;
        }


        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'mail.lapapaya.org';  // Reemplaza con tu servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'admin@lapapaya.org';
        $mail->Password = '';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
    
        $mail->setFrom('no-reply@lapapaya.org', 'www.lapapaya.org');
        $mail->addAddress($_POST['email']);
        $mail->Subject = '¡Bienvenido a nuestro sitio! Confirma tu correo electrónico';
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <title>Confirmación de correo electrónico</title>
            </head>
            <body>
                <p>Hola ". $_POST['name'] . ",</p>
                <p>Gracias por registrarte. Para confirmar tu cuenta, por favor haz clic en el siguiente enlace:</p>
                <a href='http://www.lapapaya.org'>Confirmar cuenta</a>
            </body>
            </html>
        ";
        $mail->isHTML(true);

        echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Cuenta Creada</title>
                <style>
                    /* Estilos generales para centrar y ajustar el contenido */
                    .columns {
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        background-color: #f5f5f5;
                    }

                    /* Estilo del mensaje */
                    p {
                        font-size: 1.5rem;
                        color: #333;
                        margin-bottom: 20px;
                    }

                    /* Estilo del botón */
                    .btn {
                        padding: 10px 20px;
                        font-size: 1.2rem;
                        text-decoration: none;
                        border: 2px solid #007bff;
                        border-radius: 5px;
                        color: #007bff;
                        transition: background-color 0.3s, color 0.3s;
                    }

                    .btn:hover {
                        background-color: #007bff;
                        color: white;
                    }

                    /* Estilo del contenedor del código QR */
                    #qrcode-container {
                        margin-top: 20px;
                        width: 150px;
                        height: 150px;
                        background-color: #ddd;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                    }
                </style>
            </head>
            <body>

                <div class='columns is-centered'>
                    <p>Tu cuenta ha sido creada</p>
                    <a class='btn btn-outline-primary' href='login.html' role='button'>Login</a>
                    <div id='qrcode-container'>
                        <!-- Aquí se generaría el código QR -->
                    </div>
                </div>

            </body>
            </html>";
    } else {
        echo "<div class='alert alert-danger mt-4' role='alert'>
                <p>Ocurrió un error al crear la cuenta. Por favor, inténtalo de nuevo más tarde.</p>
              </div>";
    }
}

mysqli_close($conn);
?>