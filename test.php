<?php
include('simpleCNAB.php');

$simpleCNAB = new simpleCNAB();
$simpleCNAB->pathFile = 'cnabTest.rem';
$simpleCNAB->optionType = 'readRemittance'; ////readRemittance | writeRemittance | readReturn | writeReturn

?>

<html>
<head>
	<meta charset="utf-8">
</head>
<body>
<?php
echo (($simpleCNAB->getCNABFile()));
?>
</body>
</html>
