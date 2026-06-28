<?php
session_start();
session_destroy();
header("Location: ingreso_secreto_dueno.php");
exit;
