<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$dir?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php
    echo "<title>$title</title>";

    foreach ($meta as $tag) echo $tag;

    foreach ($scripts as $s) if (isUrl($s)) {
      echo "<script src=\"$s\"></script>";
    } else {
      echo "<script>$s</script>";
    }

    foreach ($styles as $s) if (isUrl($s)) {
      echo "<link rel=\"stylesheet\" href=\"$s\">";
    } else {
      echo "<style>$s</style>";
    }
  ?>
</head>
<body>