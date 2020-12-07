<?php
foreach ($scripts as $s) if (isUrl($s)) {
  echo "<script src=\"$s\"></script>";
} else {
  echo "<script>$s</script>";
}
?>
</body>
</html>