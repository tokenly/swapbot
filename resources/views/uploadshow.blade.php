<html>
<head>
</head>
<body>

<h1>Showing Image</h1>

<div>
<label for="">Full</label><br>
<img src="<?= $image->image->url() ?>" >
</div>

<div>
<label for="">Thumb</label><br>
<img src="<?= $image->image->url('thumb') ?>" >
</div>


</body>
</html>
