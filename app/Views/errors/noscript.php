<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error noscript</title>
</head>
<body>
<br><br><br><center style="color:red; font-size:30px">Javascript must be enabled in this browser in order to use this application</center>
<script>
    window.location = '<?php if (!empty($_SERVER['HTTP_REFERER'])) echo $_SERVER['HTTP_REFERER']; else echo CONFIG['base_url']; ?>/';
</script>
</body>
</html>
