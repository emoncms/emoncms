<?php

	$directory = "Modules/dashboard/Views/js/widgets/image/";

	//get all image files with a .jpg extension.
	$jpgs = glob("" . $directory . "*.jpg");
	$gifs = glob("" . $directory . "*.gif");
	$png = glob("" . $directory . "*.png");
	$bmp = glob("" . $directory . "*.bmp");
	$result = array_merge($jpgs, $gifs,$png,$bmp);
	//var_dump($result);
	$imgs = '';
	// create array
	foreach($result as $image){ 
		$imgs[] = "$image"; 
		}
?>
<script>
    var imageArray = <?php echo json_encode($imgs); ?>;
	var path = <?php echo json_encode($path); ?>;
</script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgets/image/image_render.js"></script>
