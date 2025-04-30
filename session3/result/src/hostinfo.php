<?php

function hostinfo()
{

    $mysqli = new mysqli("mariadb", "myuser", "mY-s3cr3t", "mydb");

    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    echo $mysqli->host_info . "\n";
}
