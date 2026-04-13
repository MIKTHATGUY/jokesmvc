<!DOCTYPE html>
<html lang="en">
  <head>
    <title><?= $title ?? 'Default Title' ?></title>
    <link rel="stylesheet" href="/css/style.css">
  </head>

  <body>
    <nav>
      <a href="/">Home</a>
      <a href="<?= '/' //TODO ?>">Back</a>
    </nav>

    <main>
      <h1>Error <?= $code ?></h1>
      <p><?= $message ?></p>
    </main>

    <footer>
      <p>&copy; 2024 My Website. All rights reserved.</p>
    </footer>
  </body>
</html>
