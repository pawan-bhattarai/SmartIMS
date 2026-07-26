<?php

include "config/database.php";

if ($conn) {
    echo "<h2 style='color:green;'>✅ SmartIMS Database Connected Successfully!</h2>";
} else {
    echo "<h2 style='color:red;'>❌ Database Connection Failed!</h2>";
}

?>