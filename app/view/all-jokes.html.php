<h1> <?= $title ?> </h1>
<?php
  foreach ($jokes as $joke) {
    echo '<p>' . htmlspecialchars($joke) . '</p>';
  }
?>
