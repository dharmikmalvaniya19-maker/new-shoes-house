<?php
session_start();
session_regenerate_id(true);
session_unset();
session_destroy();
header("Location: http://localhost/new%20shoes%20house/admin/index.php");
exit();
?>
