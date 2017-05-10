<?php
echo "<pre>";



print_r($_COOKIE);
if (isset($_COOKIE["snsuser"]))
  echo "Welcome " . $_COOKIE["snsuser"] . "!<br />";
else
  echo "Welcome guest!<br />";
?>
